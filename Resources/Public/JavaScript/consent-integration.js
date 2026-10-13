import { diffConsent, needsReload, resolveServiceId, toConsentMap } from './consent-decisions.js'

const CONSENT_EVENT = 'lia-usercentrics:consent'
const LOG_PREFIX = '[lia-usercentrics]'

const listeners = new Map()
const usedServiceIds = new Set()
const reportedProblems = new Set()
let services = null
let consent = null
let reloading = false

window.liaUsercentrics = {
  isGranted(templateId) {
    return consent === null ? null : consent[templateId] === true
  },
  subscribe(templateId, listener) {
    if (!listeners.has(templateId)) {
      listeners.set(templateId, new Set())
    }
    listeners.get(templateId).add(listener)
    if (consent !== null) {
      notifyListener(templateId, listener)
    }

    return () => listeners.get(templateId).delete(listener)
  },
}

const configuration = readConfiguration()

window.addEventListener('UC_CONSENT', (event) => applyConsent(event.detail))
window.addEventListener('load', () => {
  if (!window.__ucCmp) {
    reportProblem('The Usercentrics CMP (window.__ucCmp) is not available, all embeds stay blocked.')
  }
})
document.addEventListener('click', handleClick)

if (window.__ucCmp) {
  window.__ucCmp.getConsentDetails().then(applyConsent)
}

function readConfiguration() {
  const element = document.getElementById('lia-usercentrics-config')
  if (element === null) {
    throw new Error(`${LOG_PREFIX} The configuration element #lia-usercentrics-config is missing.`)
  }

  return JSON.parse(element.textContent)
}

function applyConsent(details) {
  if (reloading) {
    return
  }

  const next = toConsentMap(details.services)
  const isFirstState = consent === null
  const { granted, revoked } = diffConsent(consent, next)
  if (needsReload(revoked, usedServiceIds)) {
    reloading = true
    window.location.reload()
    return
  }
  if (!isFirstState && granted.length === 0 && revoked.length === 0) {
    return
  }

  services = details.services
  consent = next
  updateIframes()
  updateContainers()

  const changedServiceIds = isFirstState ? [...listeners.keys()] : [...granted, ...revoked]
  changedServiceIds.forEach((templateId) => {
    listeners.get(templateId)?.forEach((listener) => notifyListener(templateId, listener))
  })
  document.dispatchEvent(new CustomEvent(CONSENT_EVENT, { detail: { services: { ...consent } } }))
}

function notifyListener(templateId, listener) {
  const isGranted = consent[templateId] === true
  if (isGranted) {
    usedServiceIds.add(templateId)
  }
  listener(isGranted)
}

function updateIframes() {
  document.querySelectorAll('iframe[uc-src][data-usercentrics]').forEach((iframe) => {
    const serviceName = iframe.dataset.usercentrics
    const templateId = resolveServiceId(serviceName, services)
    if (templateId === null) {
      reportProblem(`Unknown service "${serviceName}" in data-usercentrics, the iframe stays blocked. Known services: ${knownServiceNames()}.`)
      return
    }
    if (consent[templateId] !== true) {
      return
    }

    iframe.setAttribute('src', iframe.getAttribute('uc-src'))
    iframe.removeAttribute('uc-src')
    usedServiceIds.add(templateId)
  })
}

function updateContainers() {
  Object.entries(configuration.overlays).forEach(([templateId, target]) => {
    const isKnown = templateId in services
    if (!isKnown) {
      reportProblem(`The template id "${templateId}" of customOverlayIntegration is not a Usercentrics service, "${target}" stays blocked. Known template ids: ${Object.keys(services).join(', ')}.`)
    }

    document.querySelectorAll(`[data-uc="${CSS.escape(target)}"]`).forEach((container) => {
      if (consent[templateId] === true) {
        grantContainer(container)
        return
      }
      blockContainer(container, isKnown ? templateId : null)
    })
  })
}

function grantContainer(container) {
  container.dataset.liaConsent = 'granted'
  container.querySelector(':scope > [data-lia-consent-template-id]')?.remove()
}

function blockContainer(container, templateId) {
  container.dataset.liaConsent = 'blocked'
  if (templateId === null || container.querySelector(':scope > [data-lia-consent-template-id]') !== null) {
    return
  }

  container.prepend(createPlaceholder(templateId))
}

function createPlaceholder(templateId) {
  const template = document.getElementById('lia-usercentrics-placeholder')
  if (template === null) {
    throw new Error(`${LOG_PREFIX} The placeholder template #lia-usercentrics-placeholder is missing.`)
  }

  const placeholder = template.content.firstElementChild.cloneNode(true)
  placeholder.dataset.liaConsentTemplateId = templateId
  placeholder.querySelector('[data-lia-consent-service]').textContent = services[templateId].name

  return placeholder
}

function handleClick(event) {
  const button = event.target.closest('[data-lia-consent-accept], [data-lia-consent-details]')
  const placeholder = button?.closest('[data-lia-consent-template-id]')
  if (!placeholder) {
    return
  }

  const templateId = placeholder.dataset.liaConsentTemplateId
  if (button.hasAttribute('data-lia-consent-accept')) {
    acceptService(templateId)
    return
  }
  window.__ucCmp.showServiceDetails(templateId)
}

async function acceptService(templateId) {
  await window.__ucCmp.updateServicesConsents([{ id: templateId, consent: true }])
  await window.__ucCmp.saveConsents()
  applyConsent(await window.__ucCmp.getConsentDetails())
}

function knownServiceNames() {
  return Object.values(services).map((service) => service.name).join(', ')
}

function reportProblem(message) {
  if (reportedProblems.has(message)) {
    return
  }
  reportedProblems.add(message)
  console.error(`${LOG_PREFIX} ${message}`)
}

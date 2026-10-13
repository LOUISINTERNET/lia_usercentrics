export function resolveServiceId(name, services) {
  const entry = Object.entries(services).find(([, service]) => service.name === name)

  return entry ? entry[0] : null
}

export function toConsentMap(services) {
  return Object.fromEntries(
    Object.entries(services).map(([templateId, service]) => [templateId, service.consent?.given === true]),
  )
}

export function diffConsent(previous, next) {
  const granted = Object.keys(next).filter((templateId) => next[templateId] === true && previous?.[templateId] !== true)
  const revoked = previous === null
    ? []
    : Object.keys(previous).filter((templateId) => previous[templateId] === true && next[templateId] !== true)

  return { granted, revoked }
}

export function needsReload(revoked, usedServiceIds) {
  return revoked.some((templateId) => usedServiceIds.has(templateId))
}

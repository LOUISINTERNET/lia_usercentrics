import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import {
  diffConsent,
  needsReload,
  resolveServiceId,
  toConsentMap,
} from '../../Resources/Public/JavaScript/consent-decisions.js'

const services = {
  'BJz7qNsdj-7': { name: 'YouTube Video', category: 'marketing', essential: false, consent: { given: false, type: 'IMPLICIT' } },
  S1pcEj_jZX: { name: 'Google Maps', category: 'functional', essential: false, consent: { given: true, type: 'EXPLICIT' } },
  BJ59EidsWQ: { name: 'Google Tag Manager', category: 'essential', essential: true, consent: { given: true, type: 'IMPLICIT' } },
}

describe('resolveServiceId', () => {
  it('returns the template id of a known service name', () => {
    assert.equal(resolveServiceId('YouTube Video', services), 'BJz7qNsdj-7')
  })

  it('returns null for an unknown service name', () => {
    assert.equal(resolveServiceId('Vimeo', services), null)
  })
})

describe('toConsentMap', () => {
  it('maps every template id to its given consent', () => {
    assert.deepEqual(toConsentMap(services), { 'BJz7qNsdj-7': false, S1pcEj_jZX: true, BJ59EidsWQ: true })
  })
})

describe('diffConsent', () => {
  it('treats every granted service of the first state as granted', () => {
    assert.deepEqual(diffConsent(null, { a: true, b: false }), { granted: ['a'], revoked: [] })
  })

  it('reports a service that changes from granted to denied as revoked', () => {
    assert.deepEqual(diffConsent({ a: true }, { a: false }), { granted: [], revoked: ['a'] })
  })

  it('reports nothing when the consent stays the same', () => {
    assert.deepEqual(diffConsent({ a: false }, { a: false }), { granted: [], revoked: [] })
  })
})

describe('needsReload', () => {
  it('reloads when a used service is revoked', () => {
    assert.equal(needsReload(['a'], new Set(['a'])), true)
  })

  it('does not reload when only an unused service is revoked', () => {
    assert.equal(needsReload(['a'], new Set(['b'])), false)
  })

  it('does not reload without a revoked service', () => {
    assert.equal(needsReload([], new Set(['a'])), false)
  })
})

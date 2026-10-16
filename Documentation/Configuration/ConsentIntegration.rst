:navigation-title: Consent integration

.. _consent-integration-configuration:

===================
Consent integration
===================

..  versionadded:: 1.2.0

The consent integration blocks iframes and custom integrations until the
visitor consents to the service. It replaces the Smart Data Protector: it does
not intercept network requests and it does not reload the page when the
visitor consents. It reads the consent from the API of the Usercentrics CMP
(``window.__ucCmp`` and the event ``UC_CONSENT``).

Enable the consent integration
==============================

Set the constant ``useConsentIntegration`` to ``1`` and keep the Smart Data
Protector disabled.

..  code-block:: typoscript
    :caption: EXT:site_package/Configuration/TypoScript/Extensions/LiaUsercentrics/Constants.typoscript

    plugin.tx_liausercentrics {
      settings {
        activate = 1
        useSmartDataProtector = 0
        useConsentIntegration = 1
        customOverlayIntegration = BJz7qNsdj-7:youtube, S1pcEj_jZX:gMaps
        reloadOnOptIn =
        reloadOnOptOut =
      }
    }

..  warning::
    ``useConsentIntegration`` and ``useSmartDataProtector`` cannot be enabled
    at the same time. ``reloadOnOptIn`` and ``reloadOnOptOut`` only work with
    the Smart Data Protector. The extension throws an exception for both
    configurations. ``whitelistedSDPServices`` is ignored.

The extension adds these parts to every page with Usercentrics:

*   the configuration
    ``<script type="application/json" id="lia-usercentrics-config">``
*   the module
    ``EXT:lia_usercentrics/Resources/Public/JavaScript/consent-integration.js``
*   the stylesheet
    ``EXT:lia_usercentrics/Resources/Public/Css/consent-placeholder.css``
*   the placeholder template ``<template id="lia-usercentrics-placeholder">``

The module also handles content that is inserted after the page load, for
example by AJAX or a JavaScript component.

Markup
======

Iframes
-------

The YouTube and Vimeo renderers of this extension output the blocked markup
with the service names ``YouTube Video`` and ``Vimeo``. Other iframes use the
same attributes: ``uc-src`` instead of ``src`` and the service name from
Usercentrics in ``data-usercentrics``.

..  code-block:: html

    <div data-uc="youtube">
        <iframe uc-src="https://www.youtube-nocookie.com/embed/VIDEO_ID"
                data-usercentrics="YouTube Video"></iframe>
    </div>

With consent, the iframe gets its ``src``. The value of ``data-usercentrics``
must match the service name in the Usercentrics admin interface. An unknown
name keeps the iframe blocked and writes an error with the list of known
services to the browser console. For the renderers of this extension, name the
services ``YouTube Video`` and ``Vimeo`` in Usercentrics.

On pages without Usercentrics, for example pages in ``excludedPages`` or
requests with the ``ucQueryParameter``, the renderers output the regular
iframe with ``src``. The same applies when neither the Smart Data Protector
nor the consent integration is enabled, because nothing would unblock the
iframe.

In Fluid, wrap the media ViewHelper into the container and place the caption
after it:

..  code-block:: html
    :caption: EXT:site_package/Resources/Private/Extensions/News/Partials/Detail/MediaVideo.html

    <div class="video" data-uc="youtube">
        <f:media file="{mediaElement}" width="560" height="315"/>
    </div>
    <f:if condition="{mediaElement.description}">
        <p class="video__caption">{mediaElement.description}</p>
    </f:if>

Placeholder containers
----------------------

A container with ``data-uc`` shows a placeholder while the consent is
missing. The constant ``customOverlayIntegration`` maps the template ID of the
service to the value of ``data-uc``: ``<TemplateID>:<target>``, several
entries separated by commas. The template ID is shown in the details of the
service in the Usercentrics admin interface, the JavaScript API uses the same
ID. Template ID and target may contain the characters
``A-Z``, ``a-z``, ``0-9``, ``_`` and ``-``. An invalid entry throws an
exception. The container gets the attribute
``data-lia-consent="blocked"`` or ``data-lia-consent="granted"``.

A blocked iframe outside of a ``data-uc`` container stays empty and shows no
placeholder.

Put only the embed into the container. The placeholder covers the whole
container, so text inside it, for example a caption, is hidden while the
consent is missing. Place captions after the container.

..  note::
    Children of a custom element with a shadow root and without a slot are not
    rendered. Put ``data-uc`` on a wrapping element, not on the custom element.

Scripts
-------

JavaScript configured in ``jsInline`` and ``jsFiles`` is rendered with
``type="text/plain"`` and ``data-usercentrics``, and the Usercentrics CMP
unblocks it. The consent integration does not change this, see
:ref:`block-javaScript`.

JavaScript API
==============

Use the API for services that your own code loads, for example a map in a
custom element.

``window.liaUsercentrics.isGranted(templateId)``
    Returns ``true`` or ``false``. Returns ``null`` before the CMP reports the
    first consent state.

``window.liaUsercentrics.subscribe(templateId, listener)``
    Calls ``listener(isGranted)`` with the current state and after every
    change of this service. Returns a function that removes the listener.

Event ``lia-usercentrics:consent``
    Is dispatched on ``document`` when the CMP reports the first consent state
    and after every change.
    ``event.detail.services`` maps every template ID to ``true`` or
    ``false``.

The module is a deferred module script. Code that can run earlier checks if
``window.liaUsercentrics`` exists and otherwise waits for the event. Use
``subscribe()`` to wait for the consent: only a listener that received
``true`` marks the service as used, see :ref:`consent-integration-revocation`.

This reference implementation for TypeScript projects, for example Nuxt,
contains the types of the API and a function that waits for the consent:

..  code-block:: typescript
    :caption: nuxt/composables/Consent/useServiceConsent.ts

    const CONSENT_EVENT = 'lia-usercentrics:consent'

    export interface LiaUsercentricsApi {
      isGranted: (templateId: string) => boolean | null
      subscribe: (templateId: string, listener: (isGranted: boolean) => void) => () => void
    }

    declare global {
      interface Window {
        liaUsercentrics?: LiaUsercentricsApi
      }
    }

    function readApi(): LiaUsercentricsApi {
      const api = window.liaUsercentrics
      if (api === undefined) {
        throw new Error(`window.liaUsercentrics is missing although "${CONSENT_EVENT}" was dispatched`)
      }

      return api
    }

    export function waitForConsent(templateId: string): Promise<void> {
      return new Promise((resolve) => {
        const subscribe = () => {
          readApi().subscribe(templateId, (isGranted) => {
            if (isGranted) {
              resolve()
            }
          })
        }

        if (window.liaUsercentrics !== undefined) {
          subscribe()
          return
        }
        document.addEventListener(CONSENT_EVENT, subscribe, { once: true })
      })
    }

Usage, for example before a Google map is initialized:

..  code-block:: typescript

    await waitForConsent('S1pcEj_jZX')
    initMap()

In plain JavaScript, remove the types.

..  _consent-integration-revocation:

Revocation
==========

When the visitor revokes a service that the page already uses, the page
reloads once. A service is in use when one of its iframes was unblocked or a
listener received ``true`` for it. A loaded script, for example the Google
Maps API, cannot be unloaded. A revoked service that the page does not use
causes no reload.

Override the placeholder
========================

The placeholder is rendered from the Fluid template ``ConsentPlaceholder.html``.
Add your own template root path with a higher key:

..  code-block:: typoscript
    :caption: EXT:site_package/Configuration/TypoScript/Extensions/LiaUsercentrics/Setup.typoscript

    plugin.tx_liausercentrics.view.templateRootPaths.10 = EXT:site_package/Resources/Private/Templates/LiaUsercentrics/

Your template must keep these parts:

*   the element ``<template id="lia-usercentrics-placeholder">`` with exactly
    one root element
*   an element with ``data-lia-consent-service``, it receives the service name
*   a button with ``data-lia-consent-accept``, it accepts the service
*   a button with ``data-lia-consent-details``, it opens the service details
    of the CMP

The labels are in
``EXT:lia_usercentrics/Resources/Private/Language/locallang.xlf`` (English and
German).

Styling
=======

The stylesheet reads CSS custom properties. Set them in your own stylesheet,
for example on ``:root``.

..  list-table::
    :header-rows: 1

    *   - Custom property
        - Default
    *   - ``--lia-consent-placeholder-background``
        - ``#f3f3f3``
    *   - ``--lia-consent-placeholder-color``
        - ``#1a1a1a``
    *   - ``--lia-consent-placeholder-button-color``
        - ``#1a1a1a``
    *   - ``--lia-consent-placeholder-button-text-color``
        - ``#fff``
    *   - ``--lia-consent-placeholder-button-radius``
        - ``0``
    *   - ``--lia-consent-placeholder-focus-color``
        - ``#005fcc``
    *   - ``--lia-consent-placeholder-font-family``
        - ``inherit``
    *   - ``--lia-consent-placeholder-font-size``
        - ``1rem``
    *   - ``--lia-consent-placeholder-gap``
        - ``0.75rem``
    *   - ``--lia-consent-placeholder-padding``
        - ``1.5rem``
    *   - ``--lia-consent-placeholder-z-index``
        - ``1``

The placeholder covers its container. The container keeps its own size, so
give it a height, for example with an aspect ratio.

Migration from the Smart Data Protector
=======================================

#.  Set ``useSmartDataProtector = 0`` and ``useConsentIntegration = 1``.
#.  Empty ``reloadOnOptIn`` and ``reloadOnOptOut``. ``whitelistedSDPServices``
    is no longer used.
#.  Check every third-party integration that only the Smart Data Protector
    blocked. Iframes need ``uc-src`` and ``data-usercentrics``. Scripts need
    ``type="text/plain"`` and ``data-usercentrics``, or a configuration in
    ``jsInline`` or ``jsFiles``. Your own integrations use the JavaScript API.
#.  Keep ``customOverlayIntegration``. Move ``data-uc`` from a custom element
    to a wrapping element.
#.  Remove calls of the Smart Data Protector object ``uc`` from your code, for
    example ``uc.reloadOnOptIn()``. The object does not exist without the
    Smart Data Protector. Check listeners of the
    :ref:`AddCustomLibrarySettingsEvent <events>` as well, they often add
    such calls.
#.  Test every page type without consent, after consent and after a
    revocation. Check the browser console for messages that start with
    ``[lia-usercentrics]``.

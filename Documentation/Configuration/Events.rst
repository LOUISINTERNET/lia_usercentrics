:navigation-title: Events

..  _events:

======
Events
======

The extension dispatches two PSR-14 events. Register a listener with the
attribute ``#[AsEventListener]``.

GetServerRequestEvent
=====================

``LIA\LiaUsercentrics\Events\GetServerRequestEvent`` is dispatched when
``$GLOBALS['TYPO3_REQUEST']`` is not set, for example in a CLI command or in
a custom rendering context. A listener can provide the request with
``setRequest()``.

Without a request, the extension treats the context as not frontend:

*   Usercentrics is not integrated.
*   The YouTube and Vimeo renderers output regular iframes with ``src``.

The extension integrates Usercentrics only for frontend requests. A frontend
request must have the attribute ``frontend.page.information``, otherwise the
extension throws the exception ``1791905168``.

..  code-block:: php
    :caption: EXT:site_package/Classes/EventListener/ProvideServerRequest.php

    use LIA\LiaUsercentrics\Events\GetServerRequestEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    #[AsEventListener]
    final class ProvideServerRequest
    {
        public function __invoke(GetServerRequestEvent $event): void
        {
            $event->setRequest($this->createRequest());
        }
    }

AddCustomLibrarySettingsEvent
=============================

``LIA\LiaUsercentrics\Events\AddCustomLibrarySettingsEvent`` is dispatched on
every page with Usercentrics, after the extension added its scripts. Use it to
add your own Usercentrics settings or scripts.

``getPageRenderer()`` / ``setPageRenderer()``
    The page renderer of the current page.

``getAssetCollector()`` / ``setAssetCollector()``
    The asset collector of the current page.

``getUsercentricsSettings()``
    The settings ``plugin.tx_liausercentrics.settings``.

..  warning::
    The Smart Data Protector object ``uc`` exists only with
    ``useSmartDataProtector = 1``. A listener that adds calls like
    ``uc.reloadOnOptIn()`` breaks with the
    :ref:`consent integration <consent-integration-configuration>`.

..  code-block:: php
    :caption: EXT:site_package/Classes/EventListener/AddUsercentricsSettings.php

    use LIA\LiaUsercentrics\Events\AddCustomLibrarySettingsEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    #[AsEventListener]
    final class AddUsercentricsSettings
    {
        public function __invoke(AddCustomLibrarySettingsEvent $event): void
        {
            $event->getAssetCollector()->addInlineJavaScript(
                'site-usercentrics-settings',
                'window.UC_UI_SUPPRESS_CMP_DISPLAY = true;',
                [],
                ['priority' => true]
            );
        }
    }

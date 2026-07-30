:navigation-title: Smart Data Protector configuration
.. _smart-data-protector-configuration:

==================================
Smart Data Protector configuration
==================================

You can also configure and restrict the Smart Data Protector.

.. seealso::
    `Here <https://docs.usercentrics.com/#/smart-data-protector?id=smart-data-protector>`__ is the official documentation of the Smart Data Protector.

Restrict the Smart Data Protector
=================================

The Smart Data Protector scans for integrated services each time a page is called up and blocks them if it recognizes and
the cookie for this service is not set. To limit the list of services, you can enter the Template IDs in the constant `whitelistedSDPServices` separated by a comma.

.. warning::
    The settings `whitelistedSDPServices`, `customOverlayIntegration`, `reloadOnOptIn` and `reloadOnOptOut`
    are only applied when `useSmartDataProtector = 1`. Otherwise, the settings are ignored.

You can find the template IDs when configuring the services in Usercentrics.

`Here <https://docs.usercentrics.com/#/smart-data-protector?id=currently-supported-technologies>`__ you will also find all the services known to the Smart Data Protector.

.. note::
    In this example, the Smart Data Protector is limited to Google Maps, Google Tag Manager, YouTube Video and Vimeo.


.. code-block:: typoscript
    :caption: EXT:site_package/Configuration/TypoScript/Extensions/LiaUsercentrics/Constants.typoscript

    plugin.tx_liausercentrics {
      srcUrl = https://web.cmp.usercentrics.eu/ui/loader.js
      settingsId =
      ucQueryParameter =
      settings {
        activate = 0
        useSmartDataProtector = 1
        footerLink = 0
        excludedPages =
        whitelistedSDPServices = S1pcEj_jZX, BJ59EidsWQ, Hko_qNsui-Q, BJz7qNsdj-7, HyEX5Nidi-m
        customOverlayIntegration =
      }
    }


Overlay for Custom service integration
======================================

If you integrate a service using your own logic, e.g. your own JavaScript, you can add a data attribute to the container where the content of
the service is displayed and configure this in the TypoScript. The overlay is then inserted by the Smart Data Protector.

Add this attribute `data-uc=“gMaps”` to the container and enter the Template Id of the service followed by a colon and the value of the data attribute. `S1pcEj_jZX:gMaps`.
You can add several configurations here. These only need to be separated by a comma.

.. code-block:: typoscript
    :caption: EXT:site_package/Configuration/TypoScript/Extensions/LiaUsercentrics/Constants.typoscript

    plugin.tx_liausercentrics {
      srcUrl = https://web.cmp.usercentrics.eu/ui/loader.js
      settingsId =
      ucQueryParameter =
      settings {
        activate = 0
        useSmartDataProtector = 1
        footerLink = 0
        excludedPages =
        whitelistedSDPServices = S1pcEj_jZX, BJ59EidsWQ, Hko_qNsui-Q, BJz7qNsdj-7, HyEX5Nidi-m
        customOverlayIntegration = S1pcEj_jZX:gMaps
      }
    }

The container would then look like this.

.. code-block:: html

    <div data-uc="gMaps"></div>

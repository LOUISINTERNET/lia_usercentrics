:navigation-title: Block JavaScript
.. _block-javaScript:

================
Block JavaScript
================

If you have JavaScript integrations that you need to block, then create a `Setup.typoscript` in the same folder in which you created the `Constants.typoscript`.
This file is used to configure the various JavaScript, both inline and as a file.
This file must also be loaded in the `setup.typoscript` of your provider extension.
To load this file add an import in it.
`@import 'EXT:site_package/Configuration/TypoScript/Extensions/LiaUsercentrics/Setup.typoscript'`


Blocking of files
=================

In this example you can see a configuration of an inline JavaScript for the integration of the Google tag manager.

.. code-block:: typoscript
    :caption: EXT:site_package/Configuration/TypoScript/Extensions/LiaUsercentrics/Setup.typoscript

    plugin.tx_liausercentrics.jsFile {
        10 {
            file = EXT:yourext/Resources/Public/JavaScript/GoogleTagManagerIntegration.js
            dataProcessingService = Google Tag Manager
        }
    }


If you already have an inline integration of the GTM at another location, you can also assign this to the value.
However, this should also be emptied afterwards so that you do not have a duplicate integration.

.. code-block:: typoscript
    :caption: EXT:site_package/Configuration/TypoScript/Extensions/LiaUsercentrics/Setup.typoscript

    plugin.tx_liausercentrics.jsFile {
        10 {
            fiel < page.includeJS.googleTagManager
            dataProcessingService = Google Tag Manager
        }
    }

    [usercentricsIsActive('{$plugin.tx_liausercentrics.settings.activate}', '{$plugin.tx_liausercentrics.settingsId}')]
        page.includeJS.googleTagManager >
    [global]

Blocking of inline JavaScript
=============================

In this example you can see a configuration of an inline JavaScript for the integration of the Google tag manager.

.. code-block:: typoscript
    :caption: EXT:site_package/Configuration/TypoScript/Extensions/LiaUsercentrics/Setup.typoscript

    plugin.tx_liausercentrics.jsInline {
        10 {
            value (
                // create dataLayer
                window.dataLayer = window.dataLayer || [];
                function gtag() {
                    dataLayer.push(arguments);
                }

                // set „denied" as default for both ad and analytics storage, as well as ad_user_data and ad_personalization,
                gtag("consent", "default", {
                    ad_user_data: "denied",
                    ad_personalization: "denied",
                    ad_storage: "denied",
                    analytics_storage: "denied",
                    wait_for_update: 2000 // milliseconds to wait for update
                });

                // Enable ads data redaction by default [optional]
                gtag("set", "ads_data_redaction", true);

                // Google Tag Manager
                (function(w, d, s, l, i) {
                    w[l] = w[l] || [];
                    w[l].push({
                        'gtm.start': new Date().getTime(),
                        event: 'gtm.js'
                    });
                    var f = d.getElementsByTagName(s)[0],
                        j = d.createElement(s),
                        dl = l != 'dataLayer' ? '&l=' + l : '';
                    j.async = true;
                    j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                    f.parentNode.insertBefore(j, f);
                })(window, document, 'script', 'dataLayer', 'GTM-XXXXXXX');
            )
            dataProcessingService = Google Tag Manager
        }
    }


If you already have an inline integration of the GTM at another location, you can also assign this to the value.
However, this should also be emptied afterwards so that you do not have a duplicate integration.

.. code-block:: typoscript
    :caption: EXT:site_package/Configuration/TypoScript/Extensions/LiaUsercentrics/Setup.typoscript

    plugin.tx_liausercentrics.jsInline {
        10 {
            value < page.FooterData.googleTagManager.value
            dataProcessingService = Google Tag Manager
        }
    }

    [usercentricsIsActive('{$plugin.tx_liausercentrics.settings.activate}', '{$plugin.tx_liausercentrics.settingsId}')]
        page.FooterData.googleTagManager >
    [global]

:navigation-title: Basic configuration
.. _basic-configuration:

===================
Basic configuration
===================

After the extension is installed first you have to load the typoscript into your static template.

.. warning::
    If it is not loaded you will get an exception in your frontend


Basic configuration
===================

After the typoscript is loaded you have to override the constants of this extension and set them to load the
usercentrics Library on you page.


Best Practice
~~~~~~~~~~~~~

Create a `Constants.typoscript` file in your provider extension to override the default constants. You can
create it in subfolder like this: `my_extension/Configuration/TypoScript/Extensions/LiaUsercentrics/Constants.typoscript`.
Make sure that this file is also loaded in your `constants.typoscript` file. To load this file add an import in it.
`@import 'EXT:site_package/Configuration/TypoScript/Extensions/LiaUsercentrics/Constants.typoscript'`

Now place this code in it and set the constants to your need.


.. code-block:: typoscript
    :caption: EXT:site_package/Configuration/TypoScript/Extensions/LiaUsercentrics/Constants.typoscript

    plugin.tx_liausercentrics {
      srcUrl = https://web.cmp.usercentrics.eu/ui/loader.js
      settingsId =
      ucQueryParameter =
      settings {
        activate = 0
        tcfEnabled = 0
        useSmartDataProtector = 0
        footerLink = 0
        excludedPages =
        whitelistedSDPServices =
        customOverlayIntegration =
      }
    }


To load Usercentrics, enter your Usercentrics ID in settingsId and set the constant activate to 1.
If you want to use the Smart Data Protector, set the constant
the constant useSmartDataProtector to 1.


.. note::
  The Smart Data Protector scans your system for known services
  and blocks them automatically. It also offers the option of using the overlays from Usercentrics. So you don't have to build your own.


Language
~~~~~~~~

The current language is passed to Usercentrics automatically by getting the language code from the current locale:

.. code-block:: typoscript
    :caption: EXT:site_package/Configuration/TypoScript/Extensions/LiaUsercentrics/Setup.typoscript

    plugin.tx_liausercentrics {
      ...
      language = TEXT
      language.data = siteLanguage:locale:languageCode
      ...
    }

The language variable accepts plain strings and content objects like TEXT.


Footer link
~~~~~~~~~~~

You can also toggle the banner with a link.
All you have to do is add this snippet where you would like it.
You can integrate it so that it is dependent on the `footerLink` constant.
This extension adds the constants `active` and `footerLink` to the settings of the page object.
This means that they are available on all pages in the settings via `settings.usercentrics.active` or `settings.usercentrics.footerLink`.

.. warning::
   Please note that when you set the settings of the page object, you must observe the loading order of the TypoScripts.
   If your configuration is loaded after the setup of this extension, the array with the two constants added by this extension is no longer included.


.. code-block:: html

    <f:if condition="{settings.usercentrics.active} && {settings.usercentrics.footerLink}">
        <a href="#" onClick="  window.__ucCmp.showSecondLayer()">Cookie-Management</a>
    </f:if>


TCF enabled
~~~~~~~~~~~

Set the constant ``tcfEnabled`` to ``1`` to add the ``data-tcf-enabled`` attribute to the Usercentrics script tag.
This activates the IAB Transparency & Consent Framework (TCF) mode in Usercentrics.

.. seealso::
    `IAB TCF documentation <https://usercentrics.com/knowledge-hub/iab-tcf/>`__ on the Usercentrics website.

.. code-block:: typoscript
    :caption: EXT:site_package/Configuration/TypoScript/Extensions/LiaUsercentrics/Constants.typoscript

    plugin.tx_liausercentrics {
      settings {
        tcfEnabled = 1
      }
    }

This renders the following script tag in the page header:

.. code-block:: html

    <script id="usercentrics-cmp" src="..." data-settings-id="..." data-language="..." data-tcf-enabled></script>

:navigation-title: Upgrade

..  _upgrade:

=======
Upgrade
=======

Upgrade to 1.2.0
================

Version 1.2.0 adds the
:ref:`consent integration <consent-integration-configuration>` and fixes
settings that had no effect before. Check these points when you update an
existing project.

``excludedPages`` takes effect
    Before version 1.2.0, the constant was ignored. Pages in this list are now
    rendered without Usercentrics: no banner, no blocking, YouTube and Vimeo
    videos load directly. Clear the constant if your project relied on the
    previous behavior.

``ucQueryParameter`` takes effect
    A request with ``?uc=<value>`` now renders the page without Usercentrics.
    Keep the value secret or clear the constant. Requests with ``uc`` bypass
    the page cache. Add ``uc`` to
    ``$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters']``.

YouTube and Vimeo iframes are only blocked with a blocking mechanism
    The renderers output ``uc-src`` only when ``useSmartDataProtector`` or
    ``useConsentIntegration`` is enabled. Before, videos never loaded when
    both were disabled.

YouTube and Vimeo iframes keep their attributes
    When Usercentrics is active, the iframes now keep attributes like
    ``width``, ``height`` and ``allow``. Before, the attributes were dropped.
    Check the video layout, especially CSS that compensated for the missing
    attributes.

Invalid ``customOverlayIntegration`` entries throw an exception
    Each entry must have the format ``<TemplateID>:<target>`` with the
    characters ``A-Z``, ``a-z``, ``0-9``, ``_`` and ``-``. Before, the Smart
    Data Protector skipped invalid entries silently.

TypoScript values are type-checked
    A value of the extension with the wrong type, for example an array instead
    of a string, throws an exception instead of being ignored.

See :ref:`basic-configuration` for ``excludedPages`` and ``ucQueryParameter``.

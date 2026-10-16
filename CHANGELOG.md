# Changelog

All notable changes to this project are documented in this file. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.2.0] - 2026-10-16

### Upgrade notes

Check these points when you update an existing project to 1.2.0:

- `excludedPages` now takes effect. Before, the constant was ignored. Pages in this list are rendered without
  Usercentrics: no banner, no blocking, YouTube and Vimeo videos load directly. Clear the constant if you relied on
  the previous behavior.
- `ucQueryParameter` now takes effect. A request with `?uc=<value>` renders the page without Usercentrics. Keep the
  value secret or clear the constant. Requests with `uc` bypass the page cache; add `uc` to
  `$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters']`.
- YouTube and Vimeo iframes keep their attributes (`width`, `height`, `allow`, …) when Usercentrics is active.
  Before, the attributes were dropped. Check the video layout, especially CSS that compensated for the missing
  attributes.
- Invalid entries in `customOverlayIntegration` throw an exception, also with the Smart Data Protector. Each entry
  must have the format `<TemplateID>:<target>` with the characters `A-Z`, `a-z`, `0-9`, `_` and `-`. Before, the Smart
  Data Protector skipped invalid entries silently.
- TypoScript values of the extension are type-checked. A value with the wrong type, for example an array instead of
  a string, throws an exception instead of being ignored.

### Added

- Consent integration (`useConsentIntegration`) as replacement for the Smart Data Protector: unblocks `uc-src`
  iframes and `data-uc` containers via the Usercentrics CMP API without page reload, placeholder template,
  JavaScript API `window.liaUsercentrics`
- Middleware `BypassCacheMiddleware` disables the page cache for requests with the query parameter `uc`
- Consent integration handles iframes and `data-uc` containers inserted after the page load (MutationObserver)
- Documentation of the PSR-14 events `GetServerRequestEvent` and `AddCustomLibrarySettingsEvent` and an upgrade page

### Changed

- YouTube and Vimeo renderers output regular iframes on pages without Usercentrics (excluded pages,
  `ucQueryParameter`)

### Fixed

- Smart Data Protector and consent integration parse `customOverlayIntegration` the same way
- `excludedPages` had no effect (wrong setting key)
- `ucQueryParameter` had no effect (read from wrong level)
- YouTube and Vimeo renderers output `uc-src` without Smart Data Protector or consent integration, so the videos
  never loaded
- YouTube and Vimeo renderers dropped the iframe attributes
- phpstan level 9 errors
- Documentation of the `jsFiles` examples in "Block JavaScript"
- Documentation example for the JavaScript API used the event only, so a revocation did not reload the page

# Changelog
All notable changes to **oEmbed Manager** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **oEmbed Manager** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.0] - 2020-11-23

### Added
- New Site Health "info" section about shared memory.
- Compatibility with WordPress 5.6.

### Changed
- Improvement in the way roles are detected.
- The names of integrated plugins have been updated.
- The positions of PerfOps menus are pushed lower to avoid collision with other plugins (thanks to [Loïc Antignac](https://github.com/webaxones)).
- Improved layout for language indicator.
- Admin notices are now set to "don't display" by default.
- Improved IP detection  (thanks to [Ludovic Riaudel](https://github.com/lriaudel)).
- Improved changelog readability.
- The integrated markdown parser is now [Markdown](https://github.com/cebe/markdown) from Carsten Brandt.
- Prepares PerfOps menus to future 5.6 version of WordPress.

### Fixed
- [SEC001] User may be wrongly detected in XML-RPC or Rest API calls.
- The remote IP can be wrongly detected when behind some types of reverse-proxies.
- With Firefox, some links are unclickable in the Control Center (thanks to [Emil1](https://wordpress.org/support/users/milouze/)).
- When site is in english and a user choose another language for herself/himself, menu may be stuck in english.
- Some typos in `CHANGELOG.md`.

### Removed
- Parsedown as integrated markdown parser.

## [2.1.2] - 2020-06-29

### Changed
- Full compatibility with PHP 7.4.
- Automatic switching between memory and transient when a cache plugin is installed without a properly configured Redis / Memcached.

### Fixed
- When used for the first time, settings checkboxes may remain checked after being unchecked.

## [2.1.1] - 2020-05-04

### Fixed
- There's an error while activating the plugin when the server is Microsoft IIS with Windows 10.
- With Microsoft Edge, some layouts may be ugly.

## [2.1.0] - 2020-04-12

### Added
- Compatibility with [DecaLog](https://wordpress.org/plugins/decalog/) early loading feature.

### Changed
- The tool page is now called "oEmbed Cache Management".
- In site health "info" tab, the boolean are now clearly displayed.

### Fixed
- Some strings are not translatable.

### Removed
- Wrong libraries references in the "about" tab.

## [2.0.0] - 2020-03-17

### Added
- New tools to list, clear and update/create oEmbed cached items.
- Full integration with PerfOps.One suite.
- Full compatibility with [APCu Manager](https://wordpress.org/plugins/apcu-manager/).
- New menus (in the left admin bar) for accessing features: "PerfOps Settings" and "PerfOps Tools".

### Changed
- Cached content may now have a 600K size.
- Replacement texts now allow full HTML.
- Timeout can now be increased up to 1 minute.
- The license of the plugin is now GPLv3.
- New logo for the plugin.

### Removed
- Compatibility with WordPress versions prior to 5.2.

## [1.2.5] - 2020-02-15

### Changed
- WordPress 5.4 compatibility.

### Fixed
- The GDPR Cookie Consent plugin is not fully detected (thanks to [@marcoboom](https://wordpress.org/support/users/marcoboom/)).

## [1.2.9] - 2019-09-29

### Changed
- `p`, `span` and `div` tags now allow classes and styles (thanks to [@peexy](https://profiles.wordpress.org/peexy/)).
- WordPress 5.3 compatibility.

### Fixed
- Typos in "Purge Cache" explanation.

## [1.2.6-7-8] - 2019-08-23

### Changed
- Development workflow now based on GitHub.

## [1.2.5] - 2019-04-28

### Changed
- WordPress 5.2 compatibility.

## [1.2.4] - 2019-02-26

### Fixed
- Typos in version matching.

## [1.2.3] - 2019-02-25

### Changed
- WordPress 5.1 compatibility.

## [1.2.2] - 2018-11-02

### Changed
- Full compatibility with WordPress 5.0.

## [1.2.1] - 2018-09-18
### Changed
- Better support for integrations needing cookie evaluation.

## [1.2.0] - 2018-08-15

### Added
- Now integrated with *Cookie Consent* plugin.
- Now integrated with *GDPR Cookie Compliance* plugin.
- Now integrated with *GDPR Cookie Consent* plugin.

### Fixed
- There are (again and again) some typos in localizable strings.

## [1.1.0] - 2018-08-11

### Added
- Now integrated with *EU Cookie Law* plugin.

### Changed
- Better support for JetPack embedded videos.

### Fixed
- There are (again) some typos in localizable strings.

## [1.0.1] - 2018-08-07

### Changed
- Better `readme.txt` file.

### Fixed
- There are many typos in localizable strings.

## [1.0.0] - 2018-08-07

Initial release



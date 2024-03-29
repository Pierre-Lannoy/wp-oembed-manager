# oEmbed Manager
[![version](https://badgen.net/github/release/Pierre-Lannoy/wp-oembed-manager/)](https://wordpress.org/plugins/oembed-manager/)
[![php](https://badgen.net/badge/php/7.2+/green)](https://wordpress.org/plugins/oembed-manager/)
[![wordpress](https://badgen.net/badge/wordpress/5.2+/green)](https://wordpress.org/plugins/oembed-manager/)
[![license](https://badgen.net/github/license/Pierre-Lannoy/wp-oembed-manager/)](/license.txt)

__oEmbed Manager__ is a WordPress plugin to manage oEmbed capabilities of your website and to take a new step in the GDPR compliance of your embedded content.

See [WordPress directory page](https://wordpress.org/plugins/oembed-manager/) or [official website](https://perfops.one/oembed-manager).

It helps you to:
- allow/disallow other websites to embed your content;
- conditionally allow/disallow the display of embedded content on your site;
- fine tune the way oEmbed operates in the WordPress core;
- list, clear and update/create oEmbed cached items.

To conditionally allow/disallow the display of embedded content, __oEmbed Manager__ fully integrates with:

- [Cookie Consent](https://wordpress.org/plugins/uk-cookie-consent/)
- [Cookie Notice for GDPR](https://wordpress.org/plugins/cookie-notice/)
- [Do Not Track Stats](https://github.com/Pierre-Lannoy/wp-do-not-track-stats)
- [EU Cookie Law](https://wordpress.org/plugins/eu-cookie-law/)
- [GDPR](https://wordpress.org/plugins/gdpr/)
- [GDPR Cookie Compliance](https://wordpress.org/plugins/gdpr-cookie-compliance/)
- [GDPR Cookie Consent](https://wordpress.org/plugins/cookie-law-info/)

> __oEmbed Manager__ is part of [PerfOps One](https://perfops.one/), a suite of free and open source WordPress plugins dedicated to observability and operations performance.
> 
> __The development of The PerfOps One plugins suite is sponsored by [Hosterra - Ethical & Sustainable Internet Hosting](https://hosterra.eu/).__

__oEmbed Manager__ is a free and open source plugin for WordPress. It integrates many other free and open source works (as-is or modified). Please, see 'about' tab in the plugin settings to see the details.

## Hooks

__oEmbed Manager__ introduces some filters and actions to allow plugin customization. Please, read the [hooks reference](HOOKS.md) to learn more about them.

## Installation

### WordPress method (recommended)

1. From your WordPress dashboard, visit _Plugins | Add New_.
2. Search for 'oEmbed Manager'.
3. Click on the 'Install Now' button.

You can now activate __oEmbed Manager__ from your _Plugins_ page.

### Git method
1. Just clone the repository in your `/wp-content/plugins/` directory:
```bash
cd ./wp-content/plugins
git clone https://github.com/Pierre-Lannoy/wp-oembed-manager.git oembed-manager
```

You can now activate __oEmbed Manager__ from your _Plugins_ page.
 
## Contributions

If you find bugs, have good ideas to make this plugin better, you're welcome to submit issues or PRs in this [GitHub repository](https://github.com/Pierre-Lannoy/wp-oembed-manager).

Before submitting an issue or a pull request, please read the [contribution guidelines](CONTRIBUTING.md).

> ⚠️ The `master` branch is the current development state of the plugin. If you want a stable, production-ready version, please pick the last official [release](https://github.com/Pierre-Lannoy/wp-oembed-manager/releases).

## Smoke tests
[![WP compatibility](https://plugintests.com/plugins/oembed-manager/wp-badge.svg)](https://plugintests.com/plugins/oembed-manager/latest)
[![PHP compatibility](https://plugintests.com/plugins/oembed-manager/php-badge.svg)](https://plugintests.com/plugins/oembed-manager/latest)
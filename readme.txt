=== oEmbed Manager ===
Contributors: PierreLannoy
Tags: oembed, embed, privacy, gdpr, manager
Requires at least: 4.9
Tested up to: 5.3
Requires PHP: 7.1
Stable tag: 1.2.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://support.laquadrature.net/

Manage oEmbed capabilities of your website and take a new step in the GDPR compliance of your embedded content.

== Description ==

oEmbed Manager helps you to:

* allow/disallow other websites to embed your content;
* conditionally allow/disallow the display of embedded content on your site;
* fine tune the way oEmbed operates in the WordPress core.

To conditionally allow/disallow the display of embedded content, oEmbed Manager fully integrates with:

* [Cookie Consent](https://wordpress.org/plugins/uk-cookie-consent/)
* [Cookie Notice for GDPR](https://wordpress.org/plugins/cookie-notice/)
* [Do Not Track Stats](https://wordpress.org/plugins/do-not-track-stats/)
* [EU Cookie Law](https://wordpress.org/plugins/eu-cookie-law/)
* [GDPR](https://wordpress.org/plugins/gdpr/)
* [GDPR Cookie Compliance](https://wordpress.org/plugins/gdpr-cookie-compliance/)
* [GDPR Cookie Consent](https://wordpress.org/plugins/cookie-law-info/)

If you use one of these plugins, you can set oEmbed to display embedded content only when a visitor has agreed your cookie or privacy policy, or when she/he has not set the Do Not Track flag of her/his browser.

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'.
2. Search for 'oEmbed Manager'.
3. Click on the 'Install Now' button.
4. Activate oEmbed Manager.

= From WordPress.org =

1. Download oEmbed Manager.
2. Upload the `oembed-manager` directory to your `/wp-content/plugins/` directory, using your favorite method (ftp, sftp, scp, etc...).
3. Activate oEmbed Manager from your Plugins page.

= Once Activated =

1. Visit 'oEmbed' in the 'Settings' menu of your WP Admin to adjust settings.
2. Enjoy!

== Frequently Asked Questions ==

= What are the requirements for this plugin to work? =

You need **WordPress 4.9** and at least **PHP 7.1**.

= What are the cases where this plugin does not work? =

oEmbed Manager works for all embedded content based on core WordPress features. This excludes exotic things like embedded content in JetPack comments and other plugins not relying on core WordPress features.

= Where can I get support? =

Support is provided via the official [support page](https://wordpress.org/support/plugin/oembed-manager).

= Where can I report a bug? =

You can report bugs and suggest ideas via the official [support page](https://wordpress.org/support/plugin/oembed-manager).

== Changelog ==

= 1.2.9 =

Release Date: September 29th, 2019

* Improvement: `p`, `span` and `div` tags now allow classes and styles (thanks to [@peexy](https://profiles.wordpress.org/peexy/)).
* Improvement: WordPress 5.3 compatibility.
* Bug fix: typos in "Purge Cache" explanation.

= 1.2.6-7-8 =

Release Date: August 23rd, 2019

* Improvement: development workflow now based on GitHub.

= 1.2.5 =

Release Date: April 28th, 2019

* Improvement: WordPress 5.2 compatibility.

= 1.2.4 =

Release Date: February 26th, 2019

* Bug fix: typos in version matching.

= 1.2.3 =

Release Date: February 25th, 2019

* Improvement: WordPress 5.1 compatibility.

= 1.2.2 =

Release Date: November 2nd, 2018

* Improvement: full compatibility with WordPress 5.0.

= 1.2.1 =

Release Date: September 18th, 2018

* Improvement: better support for integrations needing cookie evaluation.

= 1.2.0 =

Release Date: August 15th, 2018

* New: now integrated with *Cookie Consent* plugin.
* New: now integrated with *GDPR Cookie Compliance* plugin.
* New: now integrated with *GDPR Cookie Consent* plugin.
* Bug fix: there are (again and again) some typos in localizable strings.

= 1.1.0 =

Release Date: August 11th, 2018

* New: now integrated with *EU Cookie Law* plugin.
* Improvement: better support for JetPack embedded videos.
* Bug fix: there are (again) some typos in localizable strings.

= 1.0.1 =

Release Date: August 7th, 2018

* Improvement: better `readme.txt` file.
* Bug fix: there are many typos in localizable strings.

= 1.0.0 =

Release Date: August 7th, 2018

* First public version

== Upgrade Notice ==

= 1.0.X =
Initial version

== Screenshots ==

1. Consumer Settings
2. Producer Settings
=== Ezoic ===
Contributors: ezoic
Author URI: https://ezoic.com/
Plugin URL: https://wordpress.org/plugins/ezoic-integration/
Tags: ezoic, optimization, monetization, seo, site speed, cdn, caching, cache, cloud
Requires at least: 5.2.0
Tested up to: 5.7
Requires PHP: 5.4
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Ezoic plugin provides a simple and intuitive way to integrate and connect with the entire Ezoic technology platform.

== Description ==

The Ezoic plugin provides WordPress with features and settings for Ezoic on their website. This includes:

* **Website performance optimizations included in Ezoic Leap**
* **Ezoic caching and CDN settings**
* **Detection of conflicting plugins or WordPress theme settings**
* **Platform integration (if a site is not Cloud integrated)**

Ezoic is a technology platform designed to help publishers improve every visitor session by using artificial intelligence to streamline ad revenue growth, testing, website performance SEO, and content.

Ezoic is used by everyone from independent website owners to major media brands.

Fore more information on the Ezoic platform and to use it on your website, visit [https://www.ezoic.com/](https://www.ezoic.com/).

== Installation ==

The plugin can be added by downloading it from our WordPress listing or by searching directly in the plugin directory from your WordPress admin dashboard.

= Using The Plugin For Initial Ezoic Integration: =

The Ezoic plugin can be used on it’s own for initial Ezoic integration. It is **NOT recommended** as a long-term integration method.

Sites can follow setup directions inside their Ezoic dashboard upon login to finish setting up Ezoic after integration is complete.

= Ezoic recommends switching to Cloud integration =

Ezoic’s plugin is best utilized alongside Cloud integration (via Cloudflare or by changing your nameservers at your host or registrar)

How to change from WordPress integration to Cloud integration:

[https://support.ezoic.com/kb/article/switching-from-wordpress-integration-to-ezoic-name-server-integration?id=switching-from-wordpress-integration-to-ezoic-name-server-integration](https://support.ezoic.com/kb/article/switching-from-wordpress-integration-to-ezoic-name-server-integration?id=switching-from-wordpress-integration-to-ezoic-name-server-integration)

---

= Leap optimization: =

It is recommended you install the Ezoic plugin to maximize performance while using Leap.

Cloud Integration is required to ensure the best performance when using Ezoic Leap. Without Cloud integration in place most of Leap’s features and the plugin’s benefits will not be available.

*Note: Installing and activating the Ezoic Plugin will not change your integration method if you are already Cloud Integrated.*

* **Step 1:** Download the Ezoic Plugin found here: [https://wordpress.org/plugins/ezoic-integration](https://wordpress.org/plugins/ezoic-integration/)

* **Step 2:** Navigate to the “Plugins” section of your WordPress dashboard

* **Step 3:** Complete the setup by activating the Plugin

---

= Using the super-fast Ezoic Cloud for caching and page loading: =

You will need to enable API access to the Ezoic Cloud to utilize caching features inside your site’s WordPress admin. This is located in the settings tab of the Ezoic user dashboard.

*Note: WordPress integrated sites are unable to use Ezoic Cloud caching unless they are Cloud Integrated.*


Once you have your API key from your Ezoic dashboard, it can be added to your Ezoic plugin settings inside the site’s Wordpress admin dashboard under Ezoic > CDN settings.

== Changelog ==

= 1.6.8 =
* Better CDN purging detection, error notifications

= 1.6.7 =
* Theme switch notification warning

= 1.6.6 =
* Added hook to clear cache when a comment is approved

= 1.6.5 =
* Fix Cloud Integration detection

= 1.6.4 =
* Automatically disable Ezoic WordPress caching when Cloud Integrated

= 1.6.3 =
* Add hooks for CDN cache clearing when updating plugin and theme
* Improve compatibility check with WPEngine
* Add integration status icon; Additional debugging tools

= 1.6.2 =
* Improve Ezoic Cloud Integration check

= 1.6.1 =
* Improve clarity of advanced settings

= 1.6 =
* Add advanced caching

= 1.5.2 =
* Default CDN to enabled
* Add new comment hooks for CDN plugin

= 1.5 =
* Add in CDN Manager functionality

= 1.4 =
* Send compatibility debug data

= 1.3.15 =
* Improve incompatibility checks/messages

= 1.3.14 =
* Update warning messages

= 1.3.13 =
* Minor Bug Fixes

= 1.3.12 =
* Improve compatibility detection

= 1.3.11 =
* Minor message changes

= 1.3.10 =
* Minor Bug fixes

= 1.3.9 =
* Bug fixes

= 1.3.8 =
* Display plugin compatibilities

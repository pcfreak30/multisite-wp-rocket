=== Plugin Name ===

Contributors: pcfreak30
Donate link: http://www.paypal.me/pcfreak30
Tags: wp-rocket, multisite
Requires at least: 4.2.0
Tested up to: 5.3.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin to enable WP-Rocket to be managed in multisite

This is NOT an official addon to WP-Rocket!

== Description ==

This plugin will cause all settings to be stored network wide and create one config file for the whole network. If using subdomains, or domains for subsites, a config file for each will be generated.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/multisite-wp-rocket` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress Network
4. Use WP-Rocket settings under Network Settings

== Changelog ==

### 0.1.7 ###

* Enhancement: Use rocket_container filter to get service container and dom't build new instances ourselves

### 0.1.6 ###

* Bug: Fix bug introduced in 0.1.5 due to incorrect use of API when building setting page

### 0.1.5 ###

* Compatibility: API compatibility change with wp-rocket 3.3 in regards to setting page

== Changelog ==

### 0.1.4 ###

* Compatibility: API compatibility change with wp-rocket 3.2 in regards to setting page

### 0.1.3 ###

* Bug: Ensure the update option hook runs after wp-rockets hooks

### 0.1.2 ###

* Improvement: Add support for subdomain multisite
* General Change: Handle wp-rockets OOP code refactor with compatibility fallback to the old render function

### 0.1.1 ###

* Bug: Fix hook name for updating option

### 0.1.0 ###

* Initial version

=== KO - WooCommerce User Orders Admin ===
Contributors: ko
Tags: woocommerce, users, admin, orders, customer
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds WooCommerce order details to WordPress user profile screens and helpful order-related columns to the Users admin list.

== Description ==

KO - WooCommerce User Orders Admin adds:

- A WooCommerce Orders section on each WordPress user profile screen
- An Orders column on Users > All Users
- A Revenue column on Users > All Users
- A Last Order column on Users > All Users
- Clickable Orders values that jump to the related customer orders screen
- A simple settings screen under Tools > User Order Admin
- Column display mode options for Order Count or Yes / No
- A profile order limit option
- A custom plugin meta link that changes Visit plugin site to Contact KO

Version 1.2.0 adds the requested list-screen enhancements while preserving the working Orders sort behavior.

== Installation ==

1. Upload the plugin ZIP in WordPress under Plugins > Add New > Upload Plugin.
2. Activate the plugin.
3. Go to Tools > User Order Admin to configure display settings.
4. Go to Users > All Users to view the Orders, Revenue, and Last Order columns.
5. Go to an individual user profile to see recent WooCommerce orders.

== Frequently Asked Questions ==

= Does this count guest orders? =

No. This plugin is built to show orders attached to the WordPress user account.

= Where is the settings screen? =

Go to Tools > User Order Admin.

= Who can access the settings screen? =

Only Administrators or other roles with the manage_options capability.

= What happens in Yes / No mode? =

The Orders column displays Yes or No, and users with at least one order still get a clickable link to their orders.

== Changelog ==

= 1.2.0 =
* Added Revenue and Last Order columns to Users > All Users.
* Made the Orders column clickable to jump to the related customer orders screen.
* Added sortable Revenue and Last Order columns.
* Replaced the default Visit plugin site row meta with Contact KO.

= 1.1.6 =
* Fixed Users screen sorting so the Orders column sorts by the actual WooCommerce order count instead of username.

= 1.1.5 =
* Fixed Users list data so the Orders column uses actual order counts again.
* Updated sorting to use direct order-count queries for both HPOS and legacy WooCommerce order storage.
* Keeps the settings page under Tools.

= 1.1.4 =
* Fixed Users list sorting so the Orders column sorts by WooCommerce order count instead of defaulting back to username.

= 1.1.3 =
* Fixed Orders column sorting on Users > All Users so the list no longer disappears.
* Improved sorting to use WooCommerce customer lookup order counts.
* Keeps the settings page under Tools.

= 1.1.2 =
* Moved settings page under Tools.

= 1.1.1 =
* Sorting fix release.

= 1.1.0 =
* Added settings screen for column mode and profile order limit.

= 1.0.0 =
* Initial release.

=== Role Based Content Hider ===
Contributors: rbcontenthider
Tags: roles, visibility, hide content, menu visibility, widget visibility
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hide menus, widgets, and content based on WordPress user roles.

== Description ==

Role Based Content Hider allows you to control the visibility of menus, widgets, and content based on user roles. Perfect for membership sites, intranets, and any site that needs role-based content control.

= Features =

* **Menu Visibility** — Hide menu items from specific user roles
* **Widget Visibility** — Hide widgets from specific user roles
* **Shortcode** — Use `[rb_hide]` to conditionally show/hide content
* **Guest Support** — Target non-logged-in users
* **Admin Panel** — Overview of all restricted elements
* **Developer Friendly** — Filters and hooks for extensibility

= Shortcode Usage =

Hide content from specific roles:

`[rb_hide roles="subscriber,guest"]This is hidden from subscribers and guests.[/rb_hide]`

Show content only to specific roles:

`[rb_hide show_to="administrator,editor"]Only admins and editors see this.[/rb_hide]`

= Available Roles =

* `guest` — Not logged in users
* `subscriber`
* `contributor`
* `author`
* `editor`
* `administrator`

== Installation ==

1. Upload the `rb-content-hider` folder to `/wp-content/plugins/`
2. Run `composer install` in the plugin directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings > Content Hider for configuration

== Frequently Asked Questions ==

= Can administrators always see hidden content? =

By default, yes. You can change this in Settings > Content Hider > Settings by enabling "Apply to administrators".

= Does it work with custom roles? =

Yes, all registered WordPress roles are automatically detected.

= Can I nest shortcodes? =

Yes, `[rb_hide]` supports nested shortcodes.

== Screenshots ==

1. Menu item visibility settings
2. Widget visibility settings
3. Settings page overview
4. Usage guide tab

== Changelog ==

= 1.0.0 =
* Initial release
* Menu item visibility by role
* Widget visibility by role
* Conditional shortcode [rb_hide]
* Admin settings page with overview, guide, and settings tabs

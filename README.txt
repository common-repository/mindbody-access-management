=== MZ MBO Access ===
Contributors: mikeill, sunspotswifi
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=A95ZEELLHGECE
Tags: mindbody, schedule, calendar, yoga, MBO, mindbodyonline, gym, access, restrict
Requires at least: 3.0.1
Tested up to: 5.7.1
Stable tag: 2.1.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Restrict wordpress content based on client Mindbody account details.
Create two access levels based on MBO membership details.
Achieved via shortcode(s) on the page, with MBO login form.
Option for a form which redirects clients to one of three pages: Level 1, Level 2, No Access.
Until later in 2020, requires access to MBOs v5 (not v6) API.

== Description ==

Install and you can limit content based on user MBO memberships:

[mbo-client-access access_levels="1, 2"]
RESTRICTED CONTENT HERE
[/mbo-client-access]

You can also redirect users based on their access level.
Until later in 2020, requires access to MBOs v5 (not v6) API.

== Installation ==

Steps to install and configure MZ MBO Access:

1. If not already, install MZ Mindbody API plugin.
2. Upload the directory, `mz-mbo-access` to the `/wp-content/plugins/` directory
3. Set MBO credentials and access levels in Settings->MZ Mindbody Settings page.
4. Add shortcode as desired, surrounding restricted content.

== Frequently Asked Questions ==

= Coming soon, no doubt. =

== Screenshots ==

1. Admin Tab in MZ Mindbody Access Settings page
2. Mindbody Login Form
3. Welcome, Client. Access denied.
4. Welcome, Client, redirect access denied.
5. Welcome, Client, redirect access level (1 or 2).
6. Logged Out.
7. Access Granted.

== Changelog ==

= v2.1.6 =
Bypass access check for non-logged clients.

= v2.1.5 =
Remove Carbon Fields from strauss/mozart management, as need's to be in
own namespace in order to not conflict with other instances.
Return empty arrays from check_mbo methods when checked items not
present.
Bugfix: Cast "level" string to int so strict comparison passes.
Bugfix: Refactor date comparisons, see: https://stackoverflow.com/a/14084301/2223106
Simplify default login form.
Allow users to select from posts or pages for access level redirect.

= v2.1.4 =
Only populate Access Levels forms from MBO if on that page.

= v2.1.3 =
Initialize deactivation on admin_init.
Return empty arrays from get_contracts, etc by default.

= v2.1.2 =
Bugfix: Slash deactivation function.

= v2.1.1 =
Refactor use of hooks on activation, deactivation, initialization.
Add arbitrary number of levels configured by repeater field.

= v2.1.0 =
More WP Standards compliance.

= v2.0.9 =
More WP Standards compliance.
Add third access level and ability to consider MBO "Services".

= v2.0.8 =
Updates and Documention to more closely adhere with WP Standards.
Allow calls to check_ajax_referrer to die on fail.

= v2.0.7 =
Support access by Contract on top of access by Service.

= v2.0.6 =
Set access level zero as default.
Add method to retrieve client access level.
Update $_Session when client details requested.
Return full mbo_result from session in get_clients.

= v2.0.5 =
Pass client_id to methods so can make requests prior to creating $_SESSION.

= v2.0.4 =
Clear cookie on logout routing.

= v2.0.3 =
Return client details in ajax call.
Utilize Eric Mann Sessionz more effectively.

= v2.0.2 =
Remove some debug logging.
Add new method to return single client details. 

= v2.0.1 =
Update composer dependencies. 

= v2.0.0 =
Add more client details, including (limited) credit card 

= v1.0.9 =
Bugfix: Correct broken code in function that returns main plugin instance!

= v1.0.8 =
Bugfix: Correct Namespace in activator call.

= v1.0.7 =
Bugfix: Correctly echo notice when parent plugin not installed and activated.

= v1.0.6 =
Bugfix: Remove call to Deactivation hook, which returns error and isn't doing anything.

= v1.0.5 =
Update shortcode example.

= v1.0.4 =
Bugfix: Fix template path for case-sensitive support.

= v1.0.3 =
Bugfix: Vendor directory was missing.

= v1.0.2 =
Bugfix: Add missing namespace so autoload works on EMANN objects as well.

= v1.0.1 =
Add cache-busting to script.
Add support for overriding buttons.
Add password reset request button.
Include server check for SOAP installed.

= v1.0 =
Initial release.

== Upgrade Notice ==

= v1.0.1 =

== Notes ==

None yet. Hopefully will work well.


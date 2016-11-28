=== BuddyPress XProfile Field Activity ===
Contributors: offereins
Tags: buddypress, xprofile, activity, field
Requires at least: WP 4.6, BP 2.5
Tested up to: WP 4.7, BP 2.7
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Record activity items for BuddyPress' Extended Profile field updates.

== Description ==

Use this plugin to record activity when members change data for selected profile fields. You can enable this feature on a per-field basis, through the new Activity field metabox. Additionally, you can toggle whether the updates are displayed in the site-wide activity stream.

The plugin uses BuddyPress' profile update throttle time, which limits activity recording for changes to the same field within a window of two hours (default time). For simplicity's sake, profile field update activity items do not have their own activity filter, but are listed with the 'Profile Updates' stream filter.

=== Developers ===

For developers several filters are available to alter the behavior or this plugin:

* `'bp_xprofile_field_activity_pre_record'` to short-circuit activity recording
* `'bp_xprofile_field_activity_is_private'` modifies whether the update's field data is considered private and thus not recorded
* `'bp_xprofile_field_activity_is_enabled'` to determine whether the field has activity recording enabled
* `'bp_xprofile_field_activity_content_field_types'` to list which profile field types have data to list as activity content (defaults to `array( 'textarea' )`)
* `'bp_xprofile_field_activity_with_content'` to determine whether the field has data to list as activity content

The plugin further emulates native BuddyPress functions and filters to return identical values like BuddyPress does in corresponding places. The main example is the usage of `bp_get_the_profile_field_value()` and its inherent filtering in order to get the proper field's display value for use in the activity item.

== Installation ==

If you download BP XProfile Field Activity manually, make sure it is uploaded to "/wp-content/plugins/bp-xprofile-field-activity/".

Activate BP XProfile Field Activity in the "Plugins" admin panel using the "Activate" link. If you're using WordPress Multisite, you can choose to activate BP XProfile Field Activity network wide for full integration with all of your sites.

This plugin is not hosted in the official WordPress repository. Instead, updating is supported through use of the [GitHub Updater](https://github.com/afragen/github-updater/) plugin by @afragen and friends.

== Changelog ==

= 1.0.0 =
* Initial release
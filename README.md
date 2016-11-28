# BP XProfile Field Activity #

Record activity items for BuddyPress' Extended Profile field updates.

## Description ##

> This WordPress plugin requires at least [WordPress](https://wordpress.org) 4.6 and [BuddyPress](https://buddypress.org) 2.7.

Use this plugin to record activity when members change data for selected profile fields. You can enable this feature on a per-field basis, through the new Activity field metabox. Additionally, you can toggle whether the updates are displayed in the site-wide activity stream.

The plugin uses BuddyPress' profile update throttle time, which limits activity recording for changes to the same field within a window of two hours (default time). For simplicity's sake, profile field update activity items do not have their own activity filter, but are listed with the 'Profile Updates' stream filter.

You would use this when you want your community to know when certain profile fields were changed by your members. For example:

* Changing the professional contact details in an intranet community
* Changing the relationship status in a dating community
* Changing the personal address in a friendship community

### Developers ###

For developers several filters are available to alter the behavior of this plugin:

* `'bp_xprofile_field_activity_pre_record'` to short-circuit activity recording
* `'bp_xprofile_field_activity_is_private'` modifies whether the profile field is considered private and should therefore not record activity
* `'bp_xprofile_field_activity_is_enabled'` to determine whether the field has activity recording enabled
* `'bp_xprofile_field_activity_content_field_types'` to list which profile field types have data to list as activity content - defaults to `array( 'textarea' )`
* `'bp_xprofile_field_activity_with_content'` to determine whether the field has data to list as activity content

The plugin further emulates native BuddyPress functions and filters to return identical values like BuddyPress does in corresponding places. The main example is the usage of `bp_get_the_profile_field_value()` and its inherent filtering in order to get the proper field's display value for use in the activity item.

## Installation ##

If you download BP XProfile Field Activity manually, make sure it is uploaded to "/wp-content/plugins/bp-xprofile-field-activity/".

Activate BP XProfile Field Activity in the "Plugins" admin panel using the "Activate" link. If you're using WordPress Multisite, you can choose to activate BP XProfile Field Activity network wide for full integration with all of your sites.

## Updates ##

This plugin is not hosted in the official WordPress repository. Instead, updating is supported through use of the [GitHub Updater](https://github.com/afragen/github-updater/) plugin by @afragen and friends.

## Contributing ##

You can contribute to the development of this plugin by [opening a new issue](https://github.com/lmoffereins/bp-xprofile-field-activity/issues/) to report a bug or request a feature in the plugin's GitHub repository.

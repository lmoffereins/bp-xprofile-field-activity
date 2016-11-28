<?php

/**
 * BP XProfile Field Activity Functions
 *
 * @package BP XProfile Field Activity
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return whether the profile field has activity updates enabled
 *
 * @since 1.0.0
 *
 * @param BP_XProfile_Field|int $field Profile field object or ID
 * @return bool Has profile field activity enabled?
 */
function bp_xprofile_field_activity_is_enabled( $field = 0 ) {

	// Bail when the field does not exist
	if ( ! $field = xprofile_get_field( $field ) )
		return false;

	// Get the field meta
	$enabled = (bool) bp_xprofile_get_meta( $field->id, 'field', 'activity_updates' );

	return (bool) apply_filters( 'bp_xprofile_field_activity_is_enabled', $enabled, $field );
}

/**
 * Return the formatted profile field value outside of the profile
 * template context, which uses global values.
 *
 * @see bp_get_the_profile_field_value()
 *
 * @since 1.0.0
 *
 * @param int $field Profile field object or ID
 * @param mixed $value Profile field value
 * @return string Profile field value
 */
function bp_xprofile_field_activity_get_value( $field, $value ) {

	// Bail when the field does not exist
	if ( ! $field = xprofile_get_field( $field ) )
		return $value;

	/**
	 * Get the profile field display value, by filtering it like in
	 * {@see bp_get_the_profile_field_value()} so we return the value as
	 * it would be displayed in the member's profile.
	 *
	 * In order to do so, the `$field` global should be available, where
	 * the filterable value exists in its `data` property. Since we want
	 * to provide a custom value we fill in the data before setting the
	 * global.
	 *
	 * Results from metadata are unserialized by default, so serialize again.
	 */
	$field->data = $field->get_field_data();
	$field->data->value = maybe_serialize( $value );

	// Switch the existing global
	$_field = isset( $GLOBALS['field'] ) ? $GLOBALS['field'] : null;
	$GLOBALS['field'] = $field;

	// Get the field's filtered display value
	$value = bp_get_the_profile_field_value();

	// Reset the global
	if ( $_field ) {
		$GLOBALS['field'] = $_field;
	} else {
		unset( $GLOBALS['field'] );
	}

	return $value;
}

/**
 * Return whether the profile field is with activity content
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'bp_xprofile_field_activity_content_field_types'
 * @uses apply_filters() Calls 'bp_xprofile_field_activity_with_content'
 *
 * @param BP_XProfile_Field|int $field Profile field object or ID
 * @return bool Is profile field with activity content?
 */
function bp_xprofile_field_activity_with_content( $field ) {

	// Bail when the field does not exist
	if ( ! $field = xprofile_get_field( $field ) )
		return false;

	// Enable filtering on field types that have activity content
	$field_types  = (array) apply_filters( 'bp_xprofile_field_activity_content_field_types', array( 'textarea' ) );
	$with_content = in_array( $field->type, $field_types, true );

	return (bool) apply_filters( 'bp_xprofile_field_activity_with_content', $with_content, $field );
}

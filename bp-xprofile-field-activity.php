<?php

/**
 * The BuddyPress XProfile Field Activity Plugin
 *
 * @package BP XProfile Field Activity
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Plugin Name:       BP XProfile Field Activity
 * Description:       Record activity items for BuddyPress' Extended Profile field updates
 * Plugin URI:        https://github.com/lmoffereins/bp-xprofile-field-activity/
 * Version:           1.0.0
 * Author:            Laurens Offereins
 * Author URI:        https://github.com/lmoffereins/
 * Text Domain:       bp-xprofile-field-activity
 * Domain Path:       /languages/
 * GitHub Plugin URI: lmoffereins/bp-xprofile-field-activity
 */

if ( ! class_exists( 'BP_XProfile_Field_Activity' ) ) :
/**
 * The main plugin class
 *
 * @since 1.0.0
 */
final class BP_XProfile_Field_Activity {

	/**
	 * Holds whether the current field value is really updated.
	 *
	 * @since 1.0.0
	 * @var boolean
	 */
	protected $value_updated = false;

	/**
	 * Setup and return the singleton pattern
	 *
	 * @since 1.0.0
	 *
	 * @uses BP_XProfile_Field_Activity::setup_globals()
	 * @uses BP_XProfile_Field_Activity::setup_actions()
	 * @return The single BP XProfile Field Activity
	 */
	public static function instance() {

		// Store instance locally
		static $instance = null;

		if ( null === $instance ) {
			$instance = new BP_XProfile_Field_Activity;
			$instance->setup_globals();
			$instance->includes();
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Prevent the plugin class from being loaded more than once
	 */
	private function __construct() { /* Nothing to do */ }

	/** Private methods *************************************************/

	/**
	 * Setup default class globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		/** Versions **********************************************************/

		$this->version      = '1.0.0';

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file         = __FILE__;
		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );

		// Languages
		$this->lang_dir     = trailingslashit( $this->plugin_dir . 'languages' );

		/** Misc **************************************************************/

		$this->extend       = new stdClass();
		$this->domain       = 'bp-xprofile-field-activity';
	}

	/**
	 * Include the required files
	 *
	 * @since 1.0.0
	 */
	private function includes() {
		require( $this->plugin_dir . 'functions.php' );
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Load textdomain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 20 );

		// Require both XProfile and Activity components, bail otherwise
		if ( ! bp_is_active( 'xprofile' ) || ! bp_is_active( 'activity' ) )
			return;

		// Register activity action(s)
		add_action( 'bp_register_activity_actions',     array( $this, 'register_activity_actions' )        );
		add_action( 'bp_after_activity_get_parse_args', array( $this, 'profile_filter_actions'    )        );
		add_action( 'bp_ajax_querystring',              array( $this, 'ajax_querystring'          ), 90, 2 );
		add_filter( 'bp_activity_get',                  array( $this, 'activity_get'              )        );

		// Field data update
		add_action( 'xprofile_data_before_save', array( $this, 'profile_check_update'    ) );
		add_action( 'xprofile_data_after_save',  array( $this, 'profile_record_activity' ) );

		// Record filters
		add_filter( 'bp_xprofile_field_activity_pre_record', array( $this, 'field_activity_is_private' ), 8, 3 );

		// Admin
		add_action( 'xprofile_field_after_sidebarbox', array( $this, 'admin_add_metabox'  ) );
		add_action( 'xprofile_fields_saved_field',     array( $this, 'admin_save_metabox' ) );
	}

	/** Plugin **********************************************************/

	/**
	 * Load the translation file for current language. Checks the languages
	 * folder inside the plugin first, and then the default WordPress
	 * languages folder.
	 *
	 * Note that custom translation files inside the plugin folder will be
	 * removed on plugin updates. If you're creating custom translation
	 * files, please use the global language folder.
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'plugin_locale' with {@link get_locale()} value
	 * @uses load_textdomain() To load the textdomain
	 * @uses load_plugin_textdomain() To load the textdomain
	 */
	public function load_textdomain() {

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/bp-xprofile-field-activity/' . $mofile;

		// Look in global /wp-content/languages/bp-xprofile-field-activity folder
		load_textdomain( $this->domain, $mofile_global );

		// Look in local /wp-content/plugins/bp-xprofile-field-activity/languages/ folder
		load_textdomain( $this->domain, $mofile_local );

		// Look in global /wp-content/languages/plugins/
		load_plugin_textdomain( $this->domain );
	}

	/** Public methods **************************************************/

	/**
	 * Register custom activity actions
	 *
	 * @since 1.0.0
	 */
	public function register_activity_actions() {

		// Updated Field action
		bp_activity_set_action(
			buddypress()->profile->id,
			'updated_profile_field',
			__( 'Updated a profile field', 'bp-xprofile-field-activity' ),
			array( $this, 'updated_profile_field_action' ),
			false,  // No filter label
			array() // No filter context, but listed along with 'updated_profile' action
		);
	}

	/**
	 * Act when the activity AJAX querystring is setup
	 *
	 * @since 1.0.0
	 *
	 * @param string $qs Query string
	 * @param string $object Activity object/component context
	 * @return stringn Query string
	 */
	public function ajax_querystring( $qs, $object ) {

		// Doing an activity query
		if ( 'activity' === $object ) {

			/**
			 * Set up the cookies passed on this AJAX request. Store a local var
			 * to avoid conflicts.
			 *
			 * @see bp_legacy_theme_ajax_querystring()
			 */
			if ( ! empty( $_POST['cookie'] ) ) {
				$_BP_COOKIE = wp_parse_args( str_replace( '; ', '&', urldecode( $_POST['cookie'] ) ) );
			} else {
				$_BP_COOKIE = &$_COOKIE;
			}

			// Read cookie data to determine filter type
			if ( isset( $_BP_COOKIE['bp-activity-filter'] ) && 'updated_profile' === $_BP_COOKIE['bp-activity-filter'] ) {

				/**
				 * Set filter flag. This is immediately cleared after parsing the
				 * activity query arguments.
				 */
				$this->activity_profile_filter = true;
			}
		}

		return $qs;
	}

	/**
	 * Modify the parsed activity query arguments
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Parsed arguments
	 * @return array Arguments
	 */
	public function profile_filter_actions( $args ) {

		// This is an 'updated_profile' action query
		if ( isset( $this->activity_profile_filter ) && $this->activity_profile_filter ) {

			// Add 'updated_profile_field' to the profile filter query
			$action   = (array) $args['filter']['action'];
			$action[] = 'updated_profile_field';
			$args['filter']['action'] = $action;

			/**
			 * Clear the filter flag. Doing so ensures the above query
			 * modifcation is only applied at the very first query after
			 * the activity AJAX query string was defined.
			 */
			$this->activity_profile_filter = false;
		}

		return $args;
	}

	/**
	 * Modify the activity query results
	 *
	 * @since 1.0.0
	 *
	 * @param array $activity Activity query results
	 * @return array Activity query results
	 */
	public function activity_get( $activity ) {

		if ( ! empty( $activity['activities'] ) ) {
			foreach ( $activity['activities'] as $k => $_activity ) {

				// Skip when this is not a profile field update
				if ( 'updated_profile_field' !== $_activity->type )
					continue;

				// Skip when profile field does not exist
				if ( ! $field = xprofile_get_field( $_activity->item_id ) )
					continue;

				// Store the profile field's display value
				if ( bp_xprofile_field_activity_with_content( $field ) ) {
					$_activity->content = bp_xprofile_field_activity_get_value( $field, $_activity->content );
				}

				// Rewrite item
				$activity['activities'][ $k ] = $_activity;
			}
		}

		return $activity;
	}

	/**
	 * Format the 'updated_profile_field' activity action
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Static activity action
	 * @param BP_Activity_Activity $activity Activity object
	 * @return string Formatted activity action
	 */
	public function updated_profile_field_action( $action, $activity ) {

		// Define action parts
		$profile_url  = trailingslashit( bp_core_get_user_domain( $activity->user_id ) . bp_get_profile_slug() );
		$profile_link = '<a href="' . $profile_url . '">' . bp_core_get_user_displayname( $activity->user_id ) . '</a>';
		$field        = xprofile_get_field( $activity->item_id );
		$value        = bp_activity_get_meta( $activity->id, 'profile_field_value' );

		// Data was updated
		if ( ! empty( $value ) || ! empty( $activity->content ) ) {

			// This field type has activity content
			if ( bp_xprofile_field_activity_with_content( $field ) ) {
				$action = __( '%1$s updated %2$s in their profile', 'bp-xprofile-field-activity' );

			// Other field types
			} else {

				/**
				 * Get the stored field value, filter it like in {@see bp_get_the_profile_field_value()}
				 * so we display the value which it would have in the member's profile.
				 */
				$value = bp_xprofile_field_activity_get_value( $field, $value );

				$action = __( '%1$s updated %2$s in their profile to %3$s', 'bp-xprofile-field-activity' );
				$before = '<span class="profile-field-value">';
				$after  = '</span>';

				// Wrap in quotes, handle array values (does this occur?)
				if ( is_array( $value ) ) {
					$value = array_map( function( $i ) use ( $before, $after ) { return $before . $i . $after; }, $value );
					$value = wp_sprintf_l( '%l', $value );
				} else {
					$value = $before . $value . $after;
				}
			}

		// Data was removed
		} else {
			$action = __( '%1$s removed the data for %2$s in their profile', 'bp-xprofile-field-activity' );
			$value  = '';
		}

		// Construct action string
		$action	= sprintf( $action, $profile_link, '<span class="profile-field-name">' . $field->name . '</span>', $value );

		return $action;
	}

	/**
	 * Check whether the field value really is updated
	 *
	 * This method's hook is always fired before the activity item is posted.
	 * 
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'xprofile_data_value_before_save'
	 * @param BP_XProfile_FieldData $field_data
	 */
	public function profile_check_update( $field_data ) {

		// Get the profile field
		$field = xprofile_get_field( $field_data->field_id );

		// Bail when activity updates are not enabled
		if ( ! $field || ! bp_xprofile_field_activity_is_enabled( $field ) )
			return;

		/**
		 * Get the current value, filter it like in BP so we compare the
		 * identically filtered values.
		 *
		 * @see BP_XProfile_ProfileData::save()
		 */
		$current_value = apply_filters( 'xprofile_data_value_before_save', $field->data->value, $field_data->id, true, $field_data );

		// Empty arrays may not be serialized yet
		if ( is_array( $current_value ) && empty( $current_value ) ) {
			$current_value = serialize( $current_value );
		}

		// Compare values
		$updated = ( $field_data->value != $current_value );

		// Set updated flag
		$this->value_updated = (bool) $updated;
	}

	/**
	 * Record an activity item for a profile field update
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'bp_xprofile_field_activity_pre_record'
	 * @uses apply_filters() Calls 'bp_xprofile_updated_profile_activity_throttle_time'
	 * @param BP_XProfile_FieldData $field_data
	 */
	public function profile_record_activity( $field_data ) {

		// Get the profile field
		$field = xprofile_get_field( $field_data->field_id );
		
		// Bail when value is not updated or activity updates are not enabled
		if ( ! $field || ! $this->value_updated || ! bp_xprofile_field_activity_is_enabled( $field ) )
			return;

		// Bail when this field update should not be recorded
		if ( true === apply_filters( 'bp_xprofile_field_activity_pre_record', false, $field, $field_data ) )
			return;

		// Define new activity item default
		$activity_id = false;

		/**
		 * Apply activity posting throttle time for this field.
		 *
		 * @see `bp_xprofile_updated_profile_activity()`
		 */
		$existing = bp_activity_get( array(
			'max'    => 1,
			'filter' => array(
				'user_id'    => $field_data->user_id,
				'object'     => buddypress()->profile->id,
				'primary_id' => $field->id,
				'action'     => 'updated_profile_field',
			),
		) );

		// Default throttle time is 2 hours. Filter to change (in seconds).
		if ( ! empty( $existing['activities'] ) ) {
			$throttle_period = apply_filters( 'bp_xprofile_updated_profile_activity_throttle_time', HOUR_IN_SECONDS * 2 );
			$then            = strtotime( $existing['activities'][0]->date_recorded );
			$now             = bp_core_current_time( true, 'timestamp' );

			// Update existing item when throttled.
			if ( ( $now - $then ) < $throttle_period ) {
				$activity_id = $existing['activities'][0]->id;
			}
		}

		// Item link
		$profile_link = trailingslashit( bp_core_get_user_domain( $field_data->user_id ) . bp_get_profile_slug() );

		// Data is displayed as content
		$with_content = bp_xprofile_field_activity_with_content( $field );

		// Record the activity
		$new_activity_id = bp_activity_add( array(
			'id'                => $activity_id,
			'component'         => buddypress()->profile->id,
			'content'           => $with_content ? $field_data->value : '',
			'type'              => 'updated_profile_field',
			'primary_link'      => $profile_link,
			'user_id'           => $field_data->user_id,
			'item_id'           => $field->id,
			'secondary_item_id' => false,
			'hide_sitewide'     => ! (bool) bp_xprofile_get_meta( $field->id, 'field', 'activity_sitewide' ),
		) );

		// Bail when the activity wasn't added properly
		if ( ! $new_activity_id || is_wp_error( $new_activity_id ) )
			return;

		/**
		 * Add profile field value in activity meta. Storing value in activity->content
		 * results in garbage activity items when the activity type is not registered
		 * or this plugin is inactive.
		 */
		if ( ! $with_content && ! empty( $field_data->value ) ) {

			/**
			 * Updating metadata serializes already serialized data by default, see
			 * {@see maybe_serialize()}, so make sure we send unserialized data in.
			 */
			bp_activity_update_meta( $new_activity_id, 'profile_field_value', maybe_unserialize( $field_data->value ) );
		}
	}

	/**
	 * Modify whether to record field activity when the given field is private
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'bp_xprofile_field_activity_is_private'
	 *
	 * @param bool $retval Whether to bail recording
	 * @param BP_XProfile_Field $field Profile field object
	 * @param BP_XProfile_FieldData $field_data Field data object
	 * @return bool
	 */
	public function field_activity_is_private( $retval, $field, $field_data ) {

		// Respect custom visibility. When changing is disabled, recording is
		// explicitly activated for any visibility type in the field's settings.
		if ( isset( $field->allow_custom_visibility ) && 'disabled' !== $field->allow_custom_visibility ) {

			// Only record for public profile fields
			if ( 'public' !== xprofile_get_field_visibility_level( $field->id, $field_data->user_id ) ) {
				$retval = true;
			}
		}

		return apply_filters( 'bp_xprofile_field_activity_is_private', $retval, $field, $field_data );
	}

	/** Admin ***********************************************************/

	/**
	 * Display the plugin's profile field metabox
	 *
	 * @since 1.0.0
	 *
	 * @param BP_XProfile_Field $field
	 */
	public function admin_add_metabox( $field ) {

		// Get the field meta
		$activity_updates  = (bool) bp_xprofile_get_meta( $field->id, 'field', 'activity_updates'  );
		$activity_sitewide = (bool) bp_xprofile_get_meta( $field->id, 'field', 'activity_sitewide' );

		?>

		<div id="field-activity-div" class="postbox">
			<h2><?php _e( 'Activity', 'buddypress' ); ?></h2>
			<div class="inside">
				<ul>
					<li>
						<label for="field-activity-updates">
							<input name="field-activity-updates" id="field-activity-updates" type="checkbox" value="1" <?php checked( $activity_updates ); ?>/>
							<?php _e( "Record a member's updates to this field", 'bp-xprofile-field-activity' ); ?>
						</label>
					</li>
					<li>
						<label for="field-activity-sitewide">
							<input name="field-activity-sitewide" id="field-activity-sitewide" type="checkbox" value="1" <?php checked( $activity_sitewide ); ?>/>
							<?php _e( "Display updates in the list of site-wide activities", 'bp-xprofile-field-activity' ); ?>
						</label>
					</li>
				</ul>
			</div>

			<input type="hidden" name="has-field-activity" value="1" />
		</div>

		<?php
	}

	/**
	 * Save the contents of the plugin's profile field metabox
	 *
	 * @since 1.0.0
	 *
	 * @param BP_XProfile_Field $field
	 */
	public function admin_save_metabox( $field ) {

		// Bail when the metabox was not submitted
		if ( ! isset( $_POST['has-field-activity'] ) )
			return;

		// Walk field activity options
		foreach ( array(
			'field-activity-updates'  => 'activity_updates',
			'field-activity-sitewide' => 'activity_sitewide'
		) as $option => $key ) {

			// Define meta value
			$value = (int) ( isset( $_POST[ $option ] ) && $_POST[ $option ] );

			// Update field meta
			bp_xprofile_update_meta( $field->id, 'field', $key, $value );
		}
	}
}

/**
 * Return single instance of this main plugin class
 *
 * @since 1.0.0
 * 
 * @return BP XProfile Field Activity
 */
function bp_xprofile_field_activity() {
	return BP_XProfile_Field_Activity::instance();
}

// Initiate plugin on bp_loaded
add_action( 'bp_loaded', 'bp_xprofile_field_activity' );

endif; // class_exists

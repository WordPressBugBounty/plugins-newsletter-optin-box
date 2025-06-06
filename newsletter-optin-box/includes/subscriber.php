<?php
/**
 * Subscriber API: Subscriber functions
 *
 * Contains functions for manipulating Noptin subscribers
 *
 * @since   1.2.7
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Queries the subscribers database.
 *
 * @param array $args Query arguments.
 * @param string $return See Hizzle\Noptin\DB\Main::query for allowed values.
 * @return int|array|\Hizzle\Noptin\DB\Subscriber[]|\Hizzle\Store\Query|WP_Error
 */
function noptin_get_subscribers( $args = array(), $return = 'results' ) {
	return noptin()->db()->query( 'subscribers', $args, $return );
}

/**
 * Fetch a subscriber by subscriber ID.
 *
 * @param int|string|\Hizzle\Noptin\DB\Subscriber $subscriber Subscriber ID, email, confirm key, or object.
 * @return \Hizzle\Noptin\DB\Subscriber Subscriber object.
 */
function noptin_get_subscriber( $subscriber = 0 ) {

	// If subscriber is already a subscriber object, return it.
	if ( $subscriber instanceof \Hizzle\Noptin\DB\Subscriber ) {
		$subscriber = $subscriber->get_id();
	}

	// WP_User.
	if ( is_object( $subscriber ) && $subscriber instanceof \WP_User ) {
		$subscriber = $subscriber->user_email;
	}

	// Array.
	if ( is_array( $subscriber ) && isset( $subscriber['email'] ) ) {
		$subscriber = $subscriber['email'];
	}

	// Email or confirm key.
	if ( is_string( $subscriber ) && ! is_numeric( $subscriber ) ) {

		if ( is_email( $subscriber ) ) {
			$subscriber = get_noptin_subscriber_id_by_email( $subscriber );
		} else {
			$subscriber = get_noptin_subscriber_id_by_confirm_key( $subscriber );
		}
	}

	// Fetch subscriber.
	if ( ! is_numeric( $subscriber ) ) {
		$subscriber = 0;
	}

	$subscriber = noptin()->db()->get( (int) $subscriber );
	return is_wp_error( $subscriber ) ? noptin()->db()->get( 0 ) : $subscriber;
}

/**
 * Fetch subscriber id by confirm key.
 *
 * @param string $confirm_key Subscriber confirm key.
 * @return int|false Subscriber id if found, false otherwise.
 */
function get_noptin_subscriber_id_by_confirm_key( $confirm_key ) {
	return noptin()->db()->get_id_by_prop( 'confirm_key', $confirm_key, 'subscribers' );
}

/**
 * Retrieve subscriber meta field for a subscriber.
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   string $meta_key      The meta key to retrieve. By default, returns data for all keys.
 * @param   bool   $single        If true, returns only the first value for the specified meta key. This parameter has no effect if $key is not specified.
 * @return  mixed                 Will be an array if $single is false. Will be value of meta data field if $single is true.
 * @access  public
 * @since   1.0.5
 */
function get_noptin_subscriber_meta( $subscriber_id = 0, $meta_key = '', $single = false ) {
	return noptin()->db()->get_record_meta( $subscriber_id, $meta_key, $single );
}

/**
 * Adds subscriber meta field for a subscriber.
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   string $meta_key      The meta key to update.
 * @param   mixed  $meta_value   Metadata value. Must be serializable if non-scalar.
 * @param   mixed  $unique   Whether the same key should not be added.
 * @return  int|false  Meta ID on success, false on failure.
 * @access  public
 * @since   1.0.5
 */
function add_noptin_subscriber_meta( $subscriber_id, $meta_key, $meta_value, $unique = false ) {
	return noptin()->db()->add_record_meta( $subscriber_id, $meta_key, $meta_value, $unique );
}

/**
 * Updates subscriber meta field for a subscriber.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the same key and subscriber ID.
 *
 * If the meta field for the subscriber does not exist, it will be added and its ID returned.
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   string $meta_key      The meta key to update.
 * @param   mixed  $meta_value   Metadata value. Must be serializable if non-scalar.
 * @param   mixed  $prev_value   Previous value to check before updating.
 * @return  mixed  The new meta field ID if a field with the given key didn't exist and was therefore added, true on successful update, false on failure.
 * @access  public
 * @since   1.0.5
 */
function update_noptin_subscriber_meta( $subscriber_id, $meta_key, $meta_value, $prev_value = '' ) {
	return noptin()->db()->update_record_meta( $subscriber_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Deletes a subscriber meta field for the given subscriber ID.
 *
 * You can match based on the key, or key and value. Removing based on key and value, will keep from removing duplicate metadata with the same key. It also allows removing all metadata matching the key, if needed.
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   string $meta_key      The meta key to delete.
 * @param   mixed  $meta_value   Metadata value. Must be serializable if non-scalar.
 * @return  bool  True on success, false on failure.
 * @access  public
 * @since   1.0.5
 */
function delete_noptin_subscriber_meta( $subscriber_id, $meta_key, $meta_value = '' ) {
	return noptin()->db()->delete_record_meta( $subscriber_id, $meta_key, $meta_value );
}

/**
 * Deletes all meta values for the given meta key.
 *
 * @param   string $meta_key The meta key to delete.
 * @access  public
 * @since   2.0.0
 */
function delete_noptin_subscriber_meta_by_key( $meta_key ) {
	return noptin()->db()->delete_all_meta_by_key( $meta_key );
}

/**
 * Determines if a meta field with the given key exists for the given noptin subscriber ID.
 *
 * @param int    $subscriber_id  ID of the subscriber metadata is for.
 * @param string $meta_key       Metadata key.
 *
 */
function noptin_subscriber_meta_exists( $subscriber_id, $meta_key ) {
	return noptin()->db()->record_meta_exists( $subscriber_id, $meta_key );
}

/**
 * Logs whenever a subscriber opens an email
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   string $campaign_id    The opened email campaign.
 * @access  public
 * @since   1.2.0
 * @return  void
 */
function log_noptin_subscriber_campaign_open( $subscriber_id, $campaign_id ) {
	$subscriber = noptin_get_subscriber( $subscriber_id );

	if ( $subscriber->exists() ) {
		$subscriber->record_opened_campaign( $campaign_id );
	}
}

/**
 * Logs whenever a subscriber clicks on a link in an email
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   string $campaign_id    The email campaign.
 * @param   string $link    The clicked link.
 * @access  public
 * @since   1.2.0
 */
function log_noptin_subscriber_campaign_click( $subscriber_id, $campaign_id, $link ) {

	$subscriber = noptin_get_subscriber( $subscriber_id );

	if ( $subscriber->exists() ) {
		$subscriber->record_clicked_link( $campaign_id, $link );
	}
}

/**
 * Retrieves all the campaigns a given subscriber has clicked on a link in
 *
 * @deprecated 2.0.0
 * @access  public
 * @since   1.2.0
 */
function get_noptin_subscriber_clicked_campaigns() {
	_deprecated_function( __FUNCTION__, '2.0.0' );
	return array();
}

/**
 * Checks whether a subscriber clicked on a link in a given campaign
 *
 * @deprecated 2.0.0
 * @access  public
 * @since   1.2.0
 */
function did_noptin_subscriber_click_campaign() {
	_deprecated_function( __FUNCTION__, '2.0.0' );
	return false;
}

/**
 * Retrieve subscriber merge fields.
 *
 * @param   int $subscriber_id  Subscriber ID.
 * @access  public
 * @since   1.2.0
 */
function get_noptin_subscriber_merge_fields( $subscriber_id ) {
	$subscriber = noptin_get_subscriber( $subscriber_id );
	return $subscriber->get_data();
}

/**
 * Retrieves the URL to the subscribers page
 *
 * @return  string   The subscribers page url
 * @param   int $page the page to load.
 * @access  public
 * @since   1.0.5
 */
function get_noptin_subscribers_overview_url( $page = 1 ) {
	$url = admin_url( 'admin.php?page=noptin-subscribers' );
	return add_query_arg( 'paged', $page, $url );
}

/**
 * Counts the subscribers database.
 *
 * @param array $args Query arguments.
 * @return int
 */
function get_noptin_subscribers_count( $args = array() ) {
	return (int) noptin_get_subscribers( $args, 'count' );
}

/**
 * Prepares subscriber source fields.
 *
 * @access public
 * @since  1.0.5
 * @return array Prepared fields.
 */
function prepare_noptin_subscriber_source_fields( $source, $fields = array() ) {

	// Set default values.
	$listener = \Hizzle\Noptin\Forms\Main::$listener;

	// Loop through all custom fields.
	foreach ( get_noptin_multicheck_custom_fields() as $field ) {

		// Skip if no options.
		if ( empty( $field['options'] ) ) {
			continue;
		}

		if ( ! empty( $listener ) && ! is_null( $listener->processed_form ) ) {
			$values = $listener->get_cached( $field['merge_tag'] );

			// Maybe try with the noptin_ prefix.
			if ( empty( $values ) ) {
				$values = $listener->get_cached( 'noptin_' . $field['merge_tag'] );
			}

			if ( ! empty( $values ) ) {
				$fields[ $field['merge_tag'] ] = array_unique(
					array_merge(
						array_diff( array_filter( noptin_parse_list( $values, true ) ), array( '-1' ) ),
						( isset( $fields[ $field['merge_tag'] ] ) && is_array( $fields[ $field['merge_tag'] ] ) ) ? $fields[ $field['merge_tag'] ] : array()
					)
				);
			}
		} elseif ( ! empty( $source ) ) {

			if ( is_numeric( $source ) ) {

				// The user subscribed via an opt-in form.
				$form          = noptin_get_optin_form( $source );
				$default_value = $form->__get( $field['merge_tag'] );

			} else {

				// The user subscribed via other means.
				$default_value = get_option(
					sprintf(
						'%s_default_%s',
						$source,
						$field['merge_tag']
					),
					'-1'
				);

			}

			if ( '-1' !== $default_value && '' !== $default_value ) {
				$fields[ $field['merge_tag'] ] = array_unique(
					array_merge(
						array_diff( array_filter( noptin_parse_list( $default_value, true ) ), array( '-1' ) ),
						( isset( $fields[ $field['merge_tag'] ] ) && is_array( $fields[ $field['merge_tag'] ] ) ) ? $fields[ $field['merge_tag'] ] : array()
					)
				);
			}
		}
	}

	if ( ! isset( $fields['tags'] ) ) {

		if ( ! empty( $listener ) && ! is_null( $listener->processed_form ) ) {
			$tags = array_filter( noptin_parse_list( $listener->get_cached( 'tags' ), true ) );

			if ( ! empty( $tags ) ) {
				$fields['tags'] = $tags;
			}
		} elseif ( ! empty( $fields['source'] ) ) {

			if ( is_numeric( $fields['source'] ) ) {

				// The user subscribed via an opt-in form.
				$form = noptin_get_optin_form( $fields['source'] );
				$tags = $form->__get( 'tags' );

			} else {

				// The user subscribed via other means.
				$tags = get_option(
					sprintf(
						'%s_default_tags',
						$fields['source']
					),
					'-1'
				);

			}

			$tags = array_diff( array_filter( noptin_parse_list( $tags, true ) ), array( '-1' ) );

			if ( ! empty( $tags ) ) {
				$fields['tags'] = array_unique(
					array_merge(
						$tags,
						( isset( $fields['tags'] ) && is_array( $fields['tags'] ) ) ? $fields['tags'] : array()
					)
				);
			}
		}
	}

	return $fields;
}

/**
 * Inserts a new subscriber into the database
 *
 * This function returns the subscriber id if the subscriber exists.
 * It does not update the subscriber though unless the $update_existing argument is set to true.
 *
 * @access  public
 * @since   1.0.5
 * @return int|string Subscriber id on success, error on failure.
 */
function add_noptin_subscriber( $fields ) {

	if ( empty( $fields['language'] ) && noptin_is_multilingual() ) {
		$fields['language'] = sanitize_text_field( get_locale() );
	}

	// Ensure an email address is provided and it doesn't exist already.
	if ( empty( $fields['email'] ) || ! is_email( $fields['email'] ) ) {
		return __( 'Please provide a valid email address', 'newsletter-optin-box' );
	}

	// Sanitize the email.
	$fields['email'] = sanitize_email( $fields['email'] );

	// Check if the subscriber already exists.
	$subscriber_id = get_noptin_subscriber_id_by_email( $fields['email'] );
	if ( ! empty( $subscriber_id ) ) {

		// Allow updating of existing subscribers.
		if ( apply_filters( 'noptin_update_existing_subscriber', ! empty( $fields['update_existing'] ), $subscriber_id, $fields ) ) {
			return update_noptin_subscriber( $subscriber_id, $fields );
		}

		return (int) $subscriber_id;
	}

	// Backwards compatibility (source).
	if ( isset( $fields['_subscriber_via'] ) && empty( $fields['source'] ) ) {
		$fields['source'] = $fields['_subscriber_via'];
		unset( $fields['_subscriber_via'] );
	}

	// Set default values.
	$fields = prepare_noptin_subscriber_source_fields( $fields['source'] ?? 'manual', $fields );

	// Get the subscriber object.
	$subscriber = noptin_get_subscriber();

	// Set the subscriber properties.
	$subscriber->set_props( wp_unslash( $fields ) );

	// Set the confirmation key.
	$subscriber->set_confirm_key( md5( wp_generate_password( 100, true, true ) . uniqid() ) );

	// Set the subscriber status.
	if ( empty( $fields['status'] ) ) {
		$subscriber->set_status( get_noptin_option( 'double_optin', false ) ? 'pending' : 'subscribed' );
	}

	// Backwards compatibility (status).
	if ( isset( $fields['active'] ) ) {
		$subscriber->set_status( $fields['active'] ? 'subscribed' : 'pending' );
	}

	// Save the subscriber.
	$result = $subscriber->save();

	if ( is_wp_error( $result ) ) {
		return $result->get_error_message();
	}

	if ( ! $subscriber->exists() ) {
		return 'An error occurred';
	}

	$_GET['noptin_key'] = $subscriber->get_confirm_key();

	// Set cookie.
	if ( ! headers_sent() && ! apply_filters( 'noptin_disable_cookies', false ) ) {
		setcookie( 'noptin_email_subscribed', $subscriber->get_confirm_key(), time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );

		$cookie = get_noptin_option( 'subscribers_cookie' );
		if ( ! empty( $cookie ) && is_string( $cookie ) ) {
			setcookie( $cookie, '1', time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
		}
	}

	return $subscriber->get_id();
}
add_action( 'noptin_checkbox_integration_process_submission', 'add_noptin_subscriber' );

/**
 * Updates a Noptin subscriber
 *
 * @param int|string|\Hizzle\Noptin\DB\Subscriber $subscriber Subscriber ID, email, confirm key, or object.
 * @param array $to_update The subscriber fields to update.
 * @access  public
 * @since   1.2.3
 */
function update_noptin_subscriber( $subscriber_id, $to_update = array() ) {

	// Get the subscriber object.
	$subscriber = noptin_get_subscriber( $subscriber_id );

	if ( ! $subscriber->exists() ) {
		return new \WP_Error( 'noptin_invalid_subscriber', 'Invalid subscriber' );
	}

	// If we're updating an email, make sure it is unique.
	if ( ! empty( $to_update['email'] ) ) {
		$existing = get_noptin_subscriber_id_by_email( $to_update['email'] );

		if ( $existing && $existing !== $subscriber->get_id() ) {
			return new \WP_Error( 'noptin_email_exists', 'Email already exists' );
		}
	}

	// Set the subscriber properties.
	$subscriber->set_props( wp_unslash( $to_update ) );

	// Save the subscriber.
	return $subscriber->save();
}

/**
 * Marks a subscriber as confirmed (Double Opt-in)
 *
 * @param int|string $subscriber Subscriber ID or email.
 * @since   1.3.2
 */
function confirm_noptin_subscriber_email( $subscriber ) {

	// Fetch subscriber.
	$subscriber = noptin_get_subscriber( $subscriber );

	if ( ! $subscriber->exists() || $subscriber->get_confirmed() ) {
		return;
	}

	// Update subscriber's IP address.
	$ip_address = noptin_get_user_ip();
	if ( ! empty( $ip_address ) && '::1' !== $ip_address ) {
		$subscriber->set_ip_address( noptin_get_user_ip() );
	}

	// Confirm them.
	$subscriber->set_confirmed( true );
	$subscriber->save();
}

/**
 * De-activates a Noptin subscriber
 *
 * @deprecated 2.0.0
 * @since      1.3.1
 */
function deactivate_noptin_subscriber( $subscriber ) {
	_deprecated_function( __FUNCTION__, '2.0.0', 'unsubscribe_noptin_subscriber' );
	unsubscribe_noptin_subscriber( $subscriber );
}

/**
 * Updates a subscriber state.
 *
 * @access public
 * @since  3.0.0
 */
function update_noptin_subscriber_status( $subscriber_id_or_email, $status, $campaign_id = 0, $callback = false ) {

	if ( is_array( $subscriber_id_or_email ) && isset( $subscriber_id_or_email['email'] ) ) {
		$subscriber_id_or_email = $subscriber_id_or_email['email'];
	}

	if ( empty( $subscriber_id_or_email ) ) {
		return;
	}

	// Fetch subscriber.
	$subscriber = noptin_get_subscriber( $subscriber_id_or_email );

	if ( 'unsubscribed' === $status && is_string( $subscriber_id_or_email ) && is_email( $subscriber_id_or_email ) ) {
		if ( ! $subscriber->exists() ) {
			$subscriber->set_email( $subscriber_id_or_email );
		}
	} elseif ( ! $subscriber->exists() || $status === $subscriber->get_status() ) {
		return;
	}

	$subscriber->set_status( $status );

	if ( ! empty( $campaign_id ) && is_numeric( $campaign_id ) && ! empty( $callback ) ) {
		$subscriber->$callback( $campaign_id );
	}

	$subscriber->save();
}

/**
 * Unsubscribes a subscriber.
 *
 * @access public
 * @since  1.3.2
 */
function unsubscribe_noptin_subscriber( $subscriber_id_or_email, $campaign_id = 0 ) {
	update_noptin_subscriber_status( $subscriber_id_or_email, 'unsubscribed', $campaign_id, 'record_unsubscribed_campaign' );
}

/**
 * Bounces a subscriber.
 *
 * @access public
 * @since  1.3.2
 */
function bounce_noptin_subscriber( $subscriber_id_or_email, $campaign_id = 0 ) {
	update_noptin_subscriber_status( $subscriber_id_or_email, 'bounced', $campaign_id, 'record_bounced_campaign' );
}

/**
 * Bounces a subscriber.
 *
 * @access public
 * @since  1.3.2
 */
function noptin_subscriber_complained( $subscriber_id_or_email, $campaign_id = 0 ) {
	update_noptin_subscriber_status( $subscriber_id_or_email, 'complained', $campaign_id, 'record_bounced_campaign' );
}

/**
 * Resubscribes a subscriber.
 *
 * @access public
 * @since  2.0.0
 */
function resubscribe_noptin_subscriber( $subscriber ) {

	// Fetch subscriber.
	$subscriber = noptin_get_subscriber( $subscriber );

	if ( ! $subscriber->exists() || $subscriber->is_active() ) {
		return;
	}

	$subscriber->set_status( 'subscribed' );
	$subscriber->save();
}

/**
 * Sync user when subscription status changes.
 *
 * @param \Hizzle\Noptin\DB\Subscriber $subscriber Subscriber object.
 */
function sync_user_on_noptin_subscription_status_change( $subscriber ) {
	$user = get_user_by( 'email', $subscriber->get_email() );

	if ( ! $user ) {
		return;
	}

	if ( 'unsubscribed' === $subscriber->get_status() ) {
		update_user_meta( $user->ID, 'noptin_unsubscribed', 'unsubscribed' );
		return;
	}

	delete_user_meta( $user->ID, 'noptin_unsubscribed' );
}
add_action( 'noptin_subscriber_status_set_to_subscribed', 'sync_user_on_noptin_subscription_status_change' );
add_action( 'noptin_subscriber_status_set_to_unsubscribed', 'sync_user_on_noptin_subscription_status_change' );

/**
 * Empties the subscriber cache.
 *
 * @deprecated 2.0.0
 * @since      1.2.8
 */
function clear_noptin_subscriber_cache() {
	_deprecated_function( __FUNCTION__, '2.0.0' );
}

/**
 * Retrieves a subscriber id by email
 *
 * @access  public
 * @param string email The email to retrieve by.
 * @since   1.2.6
 * @return int|null
 */
function get_noptin_subscriber_id_by_email( $email ) {
	return noptin()->db()->get_id_by_prop( 'email', sanitize_email( $email ), 'subscribers' );
}

/**
 * Deletes a subscriber
 *
 * @access  public
 * @param int|string|\Hizzle\Noptin\DB\Subscriber $subscriber Subscriber ID, email, confirm key, or object.
 * @since   1.1.0
 */
function delete_noptin_subscriber( $subscriber_id ) {
	$subscriber = noptin_get_subscriber( $subscriber_id );
	return $subscriber->delete();
}

/**
 * Converts a name field into the first and last name
 *
 * Simple Function, Using Regex (word char and hyphens)
 * It makes the assumption the last name will be a single word.
 * Makes no assumption about middle names, that all just gets grouped into first name.
 * You could use it again, on the "first name" result to get the first and middle though.
 *
 * @access  public
 * @since   1.0.5
 */
function noptin_split_subscriber_name( $name ) {

	$name       = trim( $name );
	$last_name  = ( strpos( $name, ' ' ) === false ) ? '' : preg_replace( '#.*\s([\w-]*)$#', '$1', $name );
	$first_name = trim( preg_replace( '#' . $last_name . '#', '', $name ) );
	return array( $first_name, $last_name );

}

/**
 * Checks whether the subscriber with a given email exists.
 *
 * @param string $email The email to check for.
 * @since 1.0.5
 * @return bool
 */
function noptin_email_exists( $email ) {
	$id = get_noptin_subscriber_id_by_email( $email );
	return ! empty( $id );
}

/**
 * Retrieves the default double opt-in email details.
 *
 * @since 1.3.3
 * @return array
 */
function get_default_noptin_subscriber_double_optin_email() {

	return array(
		'email_subject'   => __( 'Please confirm your subscription', 'newsletter-optin-box' ),
		'hero_text'       => __( 'Please confirm your subscription', 'newsletter-optin-box' ),
		'email_body'      => sprintf(
			'%s %s %s',
			__( 'Tap the button below to confirm your subscription to our newsletter.', 'newsletter-optin-box' ),
			__( 'If you have received this email by mistake, you can safely delete it.', 'newsletter-optin-box' ),
			__( "You won't be subscribed if you don't click on the button below.", 'newsletter-optin-box' )
		),
		'cta_text'        => __( 'Confirm your subscription', 'newsletter-optin-box' ),
		'after_cta_text'  => sprintf(
			"%s\n\n[[confirmation_link]]\n\n%s\n[[noptin_company]]",
			__( "If that doesn't work, copy and paste the following link in your browser:", 'newsletter-optin-box' ),
			__( 'Cheers,', 'newsletter-optin-box' )
		),
		'permission_text' => __( "You are receiving this email because we got your request to subscribe to our newsletter. If you don't want to join the newsletter, you can safely delete this email", 'newsletter-optin-box' ),
	);

}

/**
 * Whether to use custom double opt-in email.
 *
 * @return bool
 * @since 1.12.0
 */
function use_custom_noptin_double_optin_email() {
	$use_custom_email = (bool) get_noptin_option( 'disable_double_optin_email' );
	return apply_filters( 'use_custom_noptin_double_optin_email', $use_custom_email );
}

/**
 * Whether double opt-in is enabled.
 *
 * @return bool
 * @since 1.12.0
 */
function noptin_has_enabled_double_optin() {
	$has_enabled = (bool) get_noptin_option( 'double_optin', false );
	return apply_filters( 'noptin_has_enabled_double_optin', $has_enabled );
}

/**
 * Sends double optin emails.
 *
 * @param int $id The id of the new subscriber.
 * @since 1.2.4
 */
function send_new_noptin_subscriber_double_optin_email( $id, $force = false ) {

	// Don't send double opt-in emails for imported subscribers.
	if ( did_action( 'noptin_subscribers_before_import_item' ) && doing_action( 'noptin_subscriber_created' ) ) {
		return false;
	}

	// Abort if double opt-in is disabled.
	$double_optin = noptin_has_enabled_double_optin() && ! use_custom_noptin_double_optin_email();
	if ( empty( $double_optin ) && doing_action( 'noptin_subscriber_created' ) ) {
		return false;
	}

	$subscriber = noptin_get_subscriber( $id );

	// Abort if the subscriber is missing or confirmed.
	if ( ! $subscriber->exists() || $subscriber->get_confirmed() ) {
		return false;
	}

	if ( ! $force && 'pending' !== $subscriber->get_status() ) {
		return false;
	}

	$defaults = get_default_noptin_subscriber_double_optin_email();
	$content  = get_noptin_option( 'double_optin_email_body', $defaults['email_body'] );
	$content .= '<p>[[button url="[[confirmation_url]]" text="[[confirmation_text]]"]]</p>';
	$content .= get_noptin_option( 'double_optin_after_cta_text', $defaults['after_cta_text'] );

	// Handle custom merge tags.
	$url  = $subscriber->get_confirm_subscription_url();
	$link = "<a href='$url' target='_blank'>$url</a>";

	$merge_tags = array(
		'confirmation_link' => $link,
		'confirmation_url'  => $url,
		'confirmation_text' => get_noptin_option( 'double_optin_cta_text', $defaults['cta_text'] ),
	);

	foreach ( $merge_tags as $key => $value ) {

		if ( is_scalar( $key ) ) {
			$content = str_replace( "[[$key]]", wp_kses_post( $value ), $content );
		}
	}

	$args = array(
		'type'        => 'normal',
		'content'     => wpautop( trim( $content ) ),
		'template'    => get_noptin_option( 'email_template', 'paste' ),
		'heading'     => get_noptin_option( 'double_optin_hero_text', $defaults['hero_text'] ),
		'footer_text' => get_noptin_option( 'double_optin_permission_text', $defaults['permission_text'] ),
	);

	\Hizzle\Noptin\Emails\Main::init_current_email_recipient(
		array(
			'email'      => $subscriber->get_email(),
			'subscriber' => $subscriber->get_id(),
		)
	);

	do_action( 'noptin_register_temporary_merge_tags' );

	$generator     = new Noptin_Email_Generator();
	$email_body    = $generator->generate( $args );
	$email_subject = noptin_parse_email_subject_tags( get_noptin_option( 'double_optin_email_subject', $defaults['email_subject'] ) );

	do_action( 'noptin_unregister_temporary_merge_tags' );

	// Send the email.
	return noptin_send_email(
		array(
			'recipients'               => $subscriber->get_email(),
			'subject'                  => $email_subject,
			'message'                  => $email_body,
			'headers'                  => array(),
			'attachments'              => array(),
			'reply_to'                 => '',
			'from_email'               => '',
			'from_name'                => '',
			'content_type'             => 'html',
			'unsubscribe_url'          => '',
			'disable_template_plugins' => ! ( 'default' === $args['template'] ),
		)
	);
}
add_action( 'noptin_subscriber_created', 'send_new_noptin_subscriber_double_optin_email' );

/**
 * Retrieves the current user's Noptin subscriber id.
 *
 * @return  false|int Subscriber id or false on failure.
 * @access  public
 * @since   1.5.1
 */
function get_current_noptin_subscriber_id() {

	if ( did_action( 'noptin_pre_load_actions_page' ) ) {
		$subscriber = \Hizzle\Noptin\Subscribers\Actions::get_subscriber();

		if ( $subscriber ) {
			return $subscriber->get_id();
		}
	}

	// Try retrieveing subscriber key.
	$subscriber_key = '';
	if ( ! empty( $_GET['noptin_key'] ) ) {
		$subscriber_key = sanitize_text_field( urldecode( $_GET['noptin_key'] ) );
	} elseif ( ! empty( $_COOKIE['noptin_email_subscribed'] ) ) {
		$subscriber_key = sanitize_text_field( $_COOKIE['noptin_email_subscribed'] );
	}

	// If we have a subscriber key, use it to retrieve the subscriber.
	if ( ! empty( $subscriber_key ) ) {
		$subscriber_id = get_noptin_subscriber_id_by_confirm_key( $subscriber_key );

		if ( ! empty( $subscriber_id ) ) {
			return $subscriber_id;
		}
	}

	// If the user is logged in, check with their email address.
	$user_data = wp_get_current_user();
	if ( ! empty( $user_data->user_email ) ) {
		$subscriber_id = get_noptin_subscriber_id_by_email( $user_data->user_email );

		if ( ! empty( $subscriber_id ) ) {
			return $subscriber_id;
		}
	}

	return false;
}

/**
 * Checks if the currently displayed user is subscribed to the newsletter.
 *
 * @since 1.4.4
 * @return bool
 */
function noptin_is_subscriber() {
	$id = get_current_noptin_subscriber_id();

	if ( ! empty( $id ) ) {
		return true;
	}

	$cookie = get_noptin_option( 'subscribers_cookie' );
	if ( ! empty( $cookie ) && is_string( $cookie ) && ! empty( $_COOKIE[ $cookie ] ) ) {
		return true;
	}

	return false;

}

/**
 * Callback for the `[noptin-show-if-subscriber]` shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param string $content Shortcode content.
 * @since 1.4.4
 * @ignore
 * @private
 */
function _noptin_show_if_subscriber( $atts, $content ) {

	if ( noptin_is_subscriber() ) {
		return do_shortcode( $content );
	}

	return '';
}
add_shortcode( 'noptin-show-if-subscriber', '_noptin_show_if_subscriber' );

/**
 * Callback for the `[noptin-show-if-non-subscriber]` shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param string $content Shortcode content.
 * @since 1.4.4
 * @ignore
 * @private
 */
function _noptin_show_if_non_subscriber( $atts, $content ) {

	if ( ! noptin_is_subscriber() ) {
		return do_shortcode( $content );
	}

	return '';
}
add_shortcode( 'noptin-show-if-non-subscriber', '_noptin_show_if_non_subscriber' );

/**
 * Callback for the `[noptin-subscriber-count]` shortcode.
 *
 * @ignore
 * @since 1.4.4
 * @private
 */
function _noptin_show_subscriber_count() {
	return get_noptin_subscribers_count();
}
add_shortcode( 'noptin-subscriber-count', '_noptin_show_subscriber_count' );

/**
 * Callback for the `[noptin-subscriber-field]` shortcode.
 *
 * @ignore
 * @since 1.4.4
 * @param array $atts Shortcode attributes.
 * @private
 */
function _noptin_show_subscriber_field( $atts ) {

	$subscriber = noptin_get_subscriber( get_current_noptin_subscriber_id() );

	if ( empty( $atts['field'] ) || ! $subscriber->exists() || ! $subscriber->has_prop( $atts['field'] ) ) {
		return '';
	}

	$value = $subscriber->get( $atts['field'] );
	return is_scalar( $value ) ? esc_html( $value ) : esc_html( wp_json_encode( $value ) );

}
add_shortcode( 'noptin-subscriber-field', '_noptin_show_subscriber_field' );

/**
 * Returns an array of available custom fields.
 *
 * @param bool $public_only
 * @since 1.5.5
 * @return array
 */
function get_noptin_custom_fields( $public_only = false ) {

	// Fetch available fields.
	$custom_fields = get_noptin_option(
		'custom_fields',
		\Hizzle\Noptin\Fields\Main::default_fields()
	);

	// Remove birthday field.
	$custom_fields = wp_list_filter( $custom_fields, array( 'type' => 'birthday' ), 'NOT' );

	// Maybe add the locale field.
	$has_language_field = current( wp_list_filter( $custom_fields, array( 'type' => 'language' ) ) );

	if ( noptin_is_multilingual() && ! $has_language_field ) {
		$custom_fields[] = array(
			'type'       => 'language',
			'merge_tag'  => 'language',
			'label'      => __( 'Language', 'newsletter-optin-box' ),
			'visible'    => false,
			'required'   => false,
			'predefined' => true,
		);
	} elseif ( ! noptin_is_multilingual() && $has_language_field ) {
		$custom_fields = wp_list_filter( $custom_fields, array( 'type' => 'language' ), 'NOT' );
	}

	// Clean the fields.
	$custom_fields = apply_filters( 'noptin_custom_fields', $custom_fields );
	$fields        = array();

	foreach ( $custom_fields as $index => $field ) {
		$prepared_field = array(
			'field_key' => uniqid( 'noptin_' ) . $index,
		);

		// Abort if the field has no label.
		if ( empty( $field['label'] ) ) {
			continue;
		}

		// Check if we have a merge tag.
		if ( empty( $field['merge_tag'] ) ) {
			$field['merge_tag'] = strtolower( str_replace( '-', '_', sanitize_title( $field['label'] ) ) );
		}

		$field['merge_tag'] = sanitize_key( $field['merge_tag'] );

		// If the merge tag is too long, truncate it.
		if ( strlen( $field['merge_tag'] ) > 64 ) {
			$field['merge_tag'] = substr( $field['merge_tag'], 0, 64 );
		}

		// If still no merge tag, use noptin_field_{index}.
		if ( empty( $field['merge_tag'] ) ) {
			$field['merge_tag'] = 'noptin_field_' . $index;
		}

		foreach ( $field as $key => $value ) {
			if ( in_array( $key, array( 'visible', 'predefined', 'required' ), true ) ) {
				$prepared_field[ $key ] = ! empty( $value );
			} elseif ( in_array( $key, array( 'options' ), true ) ) {
				$prepared_field[ $key ] = sanitize_textarea_field( $value );
			} else {
				$prepared_field[ $key ] = noptin_clean( $value );
			}
		}

		if ( 'first_name' === $field['merge_tag'] || 'last_name' === $field['merge_tag'] ) {
			$prepared_field['predefined'] = false;
		}

		$fields[] = $prepared_field;
	}

	// Maybe return public fields only.
	if ( $public_only ) {
		$fields = wp_list_filter( $fields, array( 'visible' => true ) );
	}

	return $fields;
}

/**
 * Returns an array of available multi-checkbox fields.
 *
 * @since 2.0.0
 * @return array
 */
function get_noptin_multicheck_custom_fields() {
	$fields = wp_list_filter(
		get_noptin_custom_fields(),
		array( 'type' => 'multi_checkbox' )
	);

	return apply_filters( 'noptin_multicheck_custom_fields', $fields );
}

/**
 * Returns a single custom field.
 *
 * @since 1.5.5
 * @return array|false Array of field data or false if the field does not exist.
 */
function get_noptin_custom_field( $merge_tag ) {
	$custom_field = wp_list_filter( get_noptin_custom_fields(), array( 'merge_tag' => trim( $merge_tag ) ) );
	return current( $custom_field );
}

/**
 * Returns editable subscriber fields.
 *
 * @since 2.0.6
 * @return array
 */
function get_editable_noptin_subscriber_fields() {

	$fields     = array();
	$collection = noptin()->db()->store->get( 'subscribers' );

	if ( ! empty( $collection ) ) {
		foreach ( $collection->get_props() as $prop ) {

			// Skip activity and sent_campaigns.
			if ( $prop->readonly || in_array( $prop->name, array( 'id', 'activity', 'sent_campaigns' ), true ) ) {
				continue;
			}

			$fields[ $prop->name ] = array(
				'label'       => wp_strip_all_tags( empty( $prop->label ) ? '' : $prop->label ),
				'description' => wp_strip_all_tags( $prop->description ),
			);
		}
	}

	return apply_filters( 'editable_noptin_subscriber_fields', $fields );
}

/**
 * Returns available subscriber smart tags.
 *
 * @since 1.9.0
 * @return array
 */
function get_noptin_subscriber_smart_tags() {

	$smart_tags = array();
	$collection = noptin()->db()->store->get( 'subscribers' );

	if ( ! empty( $collection ) ) {

		foreach ( $collection->get_props() as $prop ) {

			// Skip activity and sent_campaigns.
			if ( in_array( $prop->name, array( 'activity', 'sent_campaigns' ), true ) ) {
				continue;
			}

			$smart_tag = array(
				'label'       => wp_strip_all_tags( empty( $prop->label ) ? '' : $prop->label ),
				'description' => wp_strip_all_tags( $prop->description ),
				'example'     => $prop->name . ' default=""',
				'options'     => false,
				'is_multiple' => $prop->is_meta_key && $prop->is_meta_key_multiple,
				'group'       => __( 'Subscriber', 'newsletter-optin-box' ),
			);

			if ( is_callable( $prop->enum ) ) {
				$smart_tag['options'] = call_user_func( $prop->enum );
			} elseif ( is_array( $prop->enum ) ) {
				$smart_tag['options'] = $prop->enum;
			}

			if ( isset( $prop->default ) ) {
				$smart_tag['default'] = $prop->default;
			}

			if ( $prop->is_boolean() ) {
				$smart_tag['options'] = array(
					'1' => __( 'Yes', 'newsletter-optin-box' ),
					'0' => __( 'No', 'newsletter-optin-box' ),
				);

				$smart_tag['conditional_logic'] = 'string';
			} elseif ( $prop->is_date() ) {
				$smart_tag['conditional_logic'] = 'date';
			} elseif ( $prop->is_float() || ( $prop->is_numeric() && ! $prop->is_boolean() ) ) {
				$smart_tag['conditional_logic'] = 'number';
			} else {
				$smart_tag['conditional_logic'] = 'string';
			}

			$smart_tags[ $prop->name ] = $smart_tag;
		}
	}

	return apply_filters( 'noptin_known_subscriber_smart_tags', $smart_tags );
}

/**
 * Returns a single smart tag.
 *
 * @since 2.0.0
 * @return array
 */
function get_noptin_subscriber_smart_tag( $merge_tag ) {

	$collection = noptin()->db()->store->get( 'subscribers' );

	if ( empty( $collection ) ) {
		return array();
	}

	// Fetch the prop.
	$prop = $collection->get_prop( $merge_tag );

	// If it still does not exist, return an empty array.
	if ( empty( $prop ) ) {
		return array();
	}

	$smart_tag = array(
		'label'       => wp_strip_all_tags( empty( $prop->label ) ? '' : $prop->label ),
		'description' => wp_strip_all_tags( $prop->description ),
		'example'     => $prop->name . ' default=""',
		'merge_tag'   => $prop->name,
		'options'     => false,
		'is_multiple' => $prop->is_meta_key && $prop->is_meta_key_multiple,
	);

	if ( is_callable( $prop->enum ) ) {
		$smart_tag['options'] = call_user_func( $prop->enum );
	} elseif ( is_array( $prop->enum ) ) {
		$smart_tag['options'] = $prop->enum;
	}

	if ( $prop->is_boolean() ) {
		$smart_tag['options'] = array(
			'1' => __( 'Yes', 'newsletter-optin-box' ),
			'0' => __( 'No', 'newsletter-optin-box' ),
		);

		$smart_tag['conditional_logic'] = 'string';
	} elseif ( $prop->is_date() ) {
		$smart_tag['conditional_logic'] = 'date';
	} elseif ( $prop->is_float() || ( $prop->is_numeric() && ! $prop->is_boolean() ) ) {
		$smart_tag['conditional_logic'] = 'number';
	} else {
		$smart_tag['conditional_logic'] = 'string';
	}

	return $smart_tag;
}

/**
 * Returns available subscriber filters.
 *
 * @since 1.8.0
 * @return array
 */
function get_noptin_subscriber_filters() {

	return apply_filters(
		'noptin_subscriber_filters',
		wp_list_filter(
			get_noptin_subscriber_smart_tags(),
			array( 'options' => false ),
			'NOT'
		)
	);
}

/**
 * Clears cache of known subscription sources.
 *
 * @since 2.0.0
 * @return array
 */
function noptin_clear_subscription_sources_cache() {
	delete_transient( 'noptin_subscription_sources' );
}
add_action( 'noptin_subscriber_created', 'noptin_clear_subscription_sources_cache' );
add_action( 'noptin_subscriber_updated', 'noptin_clear_subscription_sources_cache' );

/**
 * Retrieves a list of known subscription sources.
 *
 * @since 1.7.0
 * @return array
 */
function noptin_get_subscription_sources() {

	// Fetch from cache.
	$sources = get_transient( 'noptin_subscription_sources' );

	if ( $sources ) {
		return apply_filters( 'noptin_subscription_sources', $sources );
	}

	// Fetch saved sources.
	$existing = noptin()->db()->query(
		'subscribers',
		array(
			'fields' => 'source',
		)
	);

	$sources = is_array( $existing ) ? array_combine( $existing, $existing ) : array();

	// Add subscription forms.
	$forms = get_posts(
		array(
			'numberposts' => -1,
			'post_type'   => 'noptin-form',
			'post_status' => 'publish',
		)
	);

	foreach ( $forms as $form ) {
		$sources[ "{$form->ID}" ] = sanitize_text_field( $form->post_title );
	}

	// Add other known sources.
	$sources['manual']     = __( 'Manually Added', 'newsletter-optin-box' );
	$sources['shortcode']  = __( 'Subscription Shortcode', 'newsletter-optin-box' );
	$sources['users_sync'] = __( 'Users Sync', 'newsletter-optin-box' );
	$sources['import']     = __( 'Imported', 'newsletter-optin-box' );
	$sources['default']    = __( 'Default', 'newsletter-optin-box' );

	$sources = array_filter( $sources );

	// Cache.
	set_transient( 'noptin_subscription_sources', $sources, HOUR_IN_SECONDS );

	return apply_filters( 'noptin_subscription_sources', $sources );
}

/**
 * Records a subscriber's activity.
 *
 * @param string $email_address
 * @param string $activity The activity to record.
 */
function noptin_record_subscriber_activity( $email_address, $activity ) {

	// Get the subscriber.
	$subscriber = noptin_get_subscriber( $email_address );

	if ( ! $subscriber->exists() && ( ! is_string( $email_address ) || ! is_email( $email_address ) ) ) {
		return;
	}

	if ( $subscriber->exists() ) {
		$email_address = $subscriber->get_email();
	}

	do_action( 'noptin_record_subscriber_activity', $email_address, $activity, $subscriber );

	if ( $subscriber->exists() ) {
		$subscriber->record_activity( $activity );
		$subscriber->save();
	}
}

/**
 * Returns the maximum number of allowed subscribers.
 *
 * @return int Zero if unlimited.
 */
function noptin_max_allowed_subscribers() {
	return apply_filters( 'noptin_max_allowed_subscribers', 0 );
}

/**
 * Returns known subscriber statuses.
 *
 * @return array
 */
function noptin_get_subscriber_statuses() {
	return apply_filters(
		'noptin_get_subscriber_statuses',
		array(
			'pending'      => __( 'Pending', 'newsletter-optin-box' ),
			'subscribed'   => __( 'Subscribed', 'newsletter-optin-box' ),
			'unsubscribed' => __( 'Unsubscribed', 'newsletter-optin-box' ),
			'bounced'      => __( 'Bounced', 'newsletter-optin-box' ),
			'blocked'      => __( 'Blocked', 'newsletter-optin-box' ),
		)
	);
}

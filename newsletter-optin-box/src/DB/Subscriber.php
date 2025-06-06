<?php

namespace Hizzle\Noptin\DB;

/**
 * Container for a single subscriber.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Subscriber.
 */
class Subscriber extends \Hizzle\Store\Record {

	/**
	 * Returns the subscriber's full name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return trim( $this->get_first_name( $context ) . ' ' . $this->get_last_name( $context ) );
	}

	/**
	 * Sets the subscriber's full name.
	 *
	 * @param string|array $value Full name.
	 */
	public function set_name( $value ) {

		if ( empty( $value ) ) {
			return;
		}

		$parts = is_array( $value ) ? $value : explode( ' ', $value, 2 );

		$this->set_first_name( array_shift( $parts ) );

		if ( ! empty( $parts ) ) {
			$this->set_last_name( array_pop( $parts ) );
		}
	}

	/**
	 * Returns the first name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_first_name( $context = 'view' ) {
		return $this->get_prop( 'first_name', $context );
	}

	/**
	 * Sets the first name.
	 *
	 * @param string $value First name.
	 */
	public function set_first_name( $value ) {
		$this->set_prop( 'first_name', sanitize_text_field( $value ) );
	}

	/**
	 * Returns the last name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_last_name( $context = 'view' ) {
		return $this->get_prop( 'last_name', $context );
	}

	/**
	 * Sets the last name.
	 *
	 * @param string $value Last name.
	 */
	public function set_last_name( $value ) {
		$this->set_prop( 'last_name', sanitize_text_field( $value ) );
	}

	/**
	 * Returns the email address.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_email( $context = 'view' ) {
		return $this->get_prop( 'email', $context );
	}

	/**
	 * Sets the email address.
	 *
	 * @param string $value Email address.
	 */
	public function set_email( $value ) {
		if ( is_email( $value ) ) {
			$this->set_prop( 'email', sanitize_email( $value ) );
		}
	}

	/**
	 * Checks if the subscriber is active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return $this->exists() && 'subscribed' === $this->get_status();
	}

	/**
	 * Returns the status.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Sets the status.
	 *
	 * @param string $value Status.
	 */
	public function set_status( $value ) {
		if ( array_key_exists( $value, noptin_get_subscriber_statuses() ) ) {

			// If unsubscribing, record the activity.
			if ( $this->object_read && $this->is_active() && 'unsubscribed' === $value ) {
				$this->record_activity( 'Unsubscribed from the newsletter' );
			}

			// If subscribing, record the activity.
			if ( $this->object_read && 'unsubscribed' === $this->get_status() && 'subscribed' === $value ) {
				$this->record_activity( 'Re-subscribed to the newsletter' );
			}

			$this->set_prop( 'status', $value );
		}
	}

	/**
	 * Gets the subscriber source.
	 *
	 * @return string
	 */
	public function get_source( $context = 'view' ) {
		return $this->get_prop( 'source', $context );
	}

	/**
	 * Sets the subscriber source.
	 *
	 * @param string $value Source.
	 */
	public function set_source( $value ) {
		$source = is_null( $value ) ? null : sanitize_text_field( $value );
		$this->set_prop( 'source', $source );
	}

	/**
	 * Gets the subscriber ip address.
	 *
	 * @return string
	 */
	public function get_ip_address( $context = 'view' ) {
		return $this->get_prop( 'ip_address', $context );
	}

	/**
	 * Sets the subscriber ip address.
	 *
	 * @param string $value IP address.
	 */
	public function set_ip_address( $value ) {
		$ip_address = is_null( $value ) ? null : sanitize_text_field( $value );
		$this->set_prop( 'ip_address', $ip_address );
	}

	/**
	 * Gets the subscriber conversion page.
	 *
	 * @return string
	 */
	public function get_conversion_page( $context = 'view' ) {
		return $this->get_prop( 'conversion_page', $context );
	}

	/**
	 * Sets the subscriber conversion page.
	 *
	 * @param string $value Conversion page.
	 */
	public function set_conversion_page( $value ) {
		$conversion_page = is_null( $value ) ? null : esc_url_raw( $value );
		$this->set_prop( 'conversion_page', $conversion_page );
	}

	/**
	 * Gets the subscriber confirmed status.
	 *
	 * @return bool
	 */
	public function get_confirmed( $context = 'view' ) {
		return $this->get_prop( 'confirmed', $context );
	}

	/**
	 * Sets the subscriber confirmed status.
	 *
	 * @param bool $value Confirmed status.
	 */
	public function set_confirmed( $value ) {
		$value = boolval( $value );
		$this->set_prop( 'confirmed', $value );

		// If the subscriber is confirmed, set the status to subscribed.
		if ( $value && $this->object_read && $this->exists() && 'subscribed' !== $this->get_status() ) {
			$this->set_status( 'subscribed' );
			$this->record_activity( 'Confirmed email address' );
		}
	}

	/**
	 * Gets the subscriber's confirmation key.
	 *
	 * @return string
	 */
	public function get_confirm_key( $context = 'view' ) {
		$confirm_key = $this->get_prop( 'confirm_key', $context );

		if ( empty( $confirm_key ) ) {
			$confirm_key = md5( wp_generate_password( 32, false ) . uniqid() );
			$this->set_confirm_key( $confirm_key );
		}

		return $confirm_key;
	}

	/**
	 * Sets the subscriber's confirmation key.
	 *
	 * @param string $value Confirmation key.
	 */
	public function set_confirm_key( $value ) {
		$confirm_key = empty( $value ) ? md5( wp_generate_password( 32, false ) . uniqid() ) : sanitize_text_field( $value );
		$this->set_prop( 'confirm_key', $confirm_key );
	}

	/**
	 * Get the subscriber's creation date.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return \Hizzle\Store\Date_Time|null
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Set the subscriber's creation date.
	 *
	 * @param \Hizzle\Store\Date_Time|string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Get the subscriber's modified date.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return \Hizzle\Store\Date_Time|null
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Set the subscriber's modified date.
	 *
	 * @param \Hizzle\Store\Date_Time|string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Fetches the subscriber's activity.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array
	 */
	public function get_activity( $context = 'view' ) {
		$activity = $this->get_prop( 'activity', $context );

		if ( is_string( $activity ) ) {
			$activity = json_decode( $activity, true );
		}

		return is_array( $activity ) ? $activity : array();
	}

	/**
	 * Sets the subscriber's activity.
	 *
	 * @param array|string $activity Activity.
	 */
	public function set_activity( $activity ) {
		$activity = empty( $activity ) ? array() : maybe_unserialize( $activity );
		$activity = is_array( $activity ) ? wp_json_encode( $activity ) : $activity;
		$this->set_prop( 'activity', $activity );
	}

	/**
	 * Records a subscriber's activity.
	 *
	 * @param string $activity Activity.
	 */
	public function record_activity( $activity ) {
		$activities   = $this->get_activity();
		$activities[] = array(
			'time'    => time(),
			'content' => $activity,
		);

		// Only save the last 30 activities.
		if ( count( $activities ) > 30 ) {
			$activities = array_slice( $activities, -30 );
		}

		$this->set_activity( $activities );
	}

	/**
	 * Fetches the subscriber's sent email campaigns.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array
	 */
	public function get_sent_campaigns( $context = 'view' ) {
		$sent_campaigns = $this->get_prop( 'sent_campaigns', $context );

		if ( is_string( $sent_campaigns ) ) {
			$sent_campaigns = json_decode( $sent_campaigns, true );
		}

		return is_array( $sent_campaigns ) ? $sent_campaigns : array();
	}

	/**
	 * Sets the subscriber's sent email campaigns.
	 *
	 *  @param array|string $sent_campaigns Sent email campaigns.
	 */
	public function set_sent_campaigns( $sent_campaigns ) {
		$sent_campaigns = empty( $sent_campaigns ) ? array() : maybe_unserialize( $sent_campaigns );
		$sent_campaigns = is_array( $sent_campaigns ) ? wp_json_encode( $sent_campaigns ) : $sent_campaigns;
		$this->set_prop( 'sent_campaigns', $sent_campaigns );
	}

	/**
	 * Records a subscriber's sent email campaign.
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	public function record_sent_campaign( $campaign_id ) {
		$campaign_id    = (string) $campaign_id;
		$sent_campaigns = $this->get_sent_campaigns();

		if ( ! isset( $sent_campaigns[ $campaign_id ] ) ) {
			$sent_campaigns[ $campaign_id ] = array(
				'time'         => array( time() ),
				'opens'        => array(),
				'clicks'       => array(),
				'unsubscribed' => false,
				'bounced'      => false,
				'complained'   => false,
			);
		} else {
			$sent_campaigns[ $campaign_id ]['time'][] = time();
		}

		$this->set_sent_campaigns( $sent_campaigns );
		$this->save();
	}

	/**
	 * Records an opened email campaign.
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	public function record_opened_campaign( $campaign_id ) {
		$campaign_id    = (string) $campaign_id;
		$sent_campaigns = $this->get_sent_campaigns();

		if ( isset( $sent_campaigns[ $campaign_id ] ) ) {

			// Record activity.
			$this->record_activity(
				sprintf(
					// translators: %s is the campaign name.
					__( 'Opened email campaign %s', 'newsletter-optin-box' ),
					'<code>' . get_the_title( $campaign_id ) . '</code>'
				)
			);

			$sent_campaigns[ $campaign_id ]['opens'][] = time();
			$this->set_sent_campaigns( $sent_campaigns );
			$this->save();

			// Fire action.
			if ( 1 === count( $sent_campaigns[ $campaign_id ]['opens'] ) ) {
				do_action( 'log_noptin_subscriber_campaign_open', $this->get_id(), $campaign_id );
				increment_noptin_campaign_stat( $campaign_id, '_noptin_opens' );
			}
		}
	}

	/**
	 * Records a clicked link in an email campaign.
	 *
	 * @param int $campaign_id Campaign ID.
	 * @param string $url URL.
	 */
	public function record_clicked_link( $campaign_id, $url ) {
		$campaign_id    = (string) $campaign_id;
		$sent_campaigns = $this->get_sent_campaigns();

		if ( isset( $sent_campaigns[ $campaign_id ] ) ) {

			// Record activity.
			$this->record_activity(
				sprintf(
					// translators: %2 is the campaign name, #1 is the link.
					__( 'Clicked on %1$s from campaign %2$s', 'newsletter-optin-box' ),
					'<code>' . esc_url( $url ) . '</code>',
					'<code>' . get_the_title( $campaign_id ) . '</code>'
				)
			);

			if ( ! isset( $sent_campaigns[ $campaign_id ]['clicks'][ $url ] ) ) {
				$sent_campaigns[ $campaign_id ]['clicks'][ $url ] = array();
			}

			$sent_campaigns[ $campaign_id ]['clicks'][ $url ][] = time();

			$this->set_sent_campaigns( $sent_campaigns );
			$this->save();

			// Fire action.
			if ( 1 === count( $sent_campaigns[ $campaign_id ]['clicks'][ $url ] ) ) {
				do_action( 'log_noptin_subscriber_campaign_click', $this->get_id(), $campaign_id, $url );
				increment_noptin_campaign_stat( $campaign_id, '_noptin_clicks' );
			}
		}
	}

	/**
	 * Records an unsubscribed email campaign.
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	public function record_unsubscribed_campaign( $campaign_id ) {
		$campaign_id    = (string) $campaign_id;
		$sent_campaigns = $this->get_sent_campaigns();

		if ( isset( $sent_campaigns[ $campaign_id ] ) ) {
			$sent_campaigns[ $campaign_id ]['unsubscribed'] = true;
			$this->set_sent_campaigns( $sent_campaigns );
			$this->save();
		}
	}

	/**
	 * Records a bounced email campaign.
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	public function record_bounced_campaign( $campaign_id ) {
		$campaign_id    = (string) $campaign_id;
		$sent_campaigns = $this->get_sent_campaigns();

		if ( isset( $sent_campaigns[ $campaign_id ] ) ) {
			$sent_campaigns[ $campaign_id ]['bounced'] = true;
			$this->set_sent_campaigns( $sent_campaigns );
			$this->save();
		}
	}

	/**
	 * Records a complained email campaign.
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	public function record_complained_campaign( $campaign_id ) {
		$campaign_id    = (string) $campaign_id;
		$sent_campaigns = $this->get_sent_campaigns();

		if ( isset( $sent_campaigns[ $campaign_id ] ) ) {
			$sent_campaigns[ $campaign_id ]['complained'] = true;
			$this->set_sent_campaigns( $sent_campaigns );
			$this->save();
		}
	}

	/**
	 * Retrieves the subscriber's edit URL.
	 *
	 * @return string
	 */
	public function get_edit_url() {
		return add_query_arg(
			array(
				'page'          => 'noptin-subscribers',
				'hizzlewp_path' => rawurlencode(
					sprintf(
						'/noptin/subscribers/%d',
						$this->get_id()
					)
				),
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Returns the unsubscribe URL for the subscriber.
	 *
	 * @return string
	 */
	public function get_unsubscribe_url() {
		return get_noptin_action_url(
			'unsubscribe',
			noptin_encrypt(
				wp_json_encode(
					array_filter(
						array(
							'email' => $this->get_email(),
							'cid'   => empty( \Hizzle\Noptin\Emails\Main::$current_email ) ? '' : \Hizzle\Noptin\Emails\Main::$current_email->id,
						)
					)
				)
			)
		);
	}

	/**
	 * Returns the resubsribe URL for the subscriber.
	 *
	 * @return string
	 */
	public function get_resubscribe_url() {
		return get_noptin_action_url(
			'resubscribe',
			noptin_encrypt(
				wp_json_encode(
					array_filter(
						array(
							'email' => $this->get_email(),
							'cid'   => empty( \Hizzle\Noptin\Emails\Main::$current_email ) ? '' : \Hizzle\Noptin\Emails\Main::$current_email->id,
						)
					)
				)
			)
		);
	}

	/**
	 * Returns the subscription confirmation URL for the subscriber.
	 *
	 * @return string
	 */
	public function get_confirm_subscription_url() {
		return get_noptin_action_url(
			'confirm',
			noptin_encrypt(
				wp_json_encode(
					array( 'email' => $this->get_email() )
				)
			)
		);
	}

	/**
	 * Returns the manage preferences URL for the subscriber.
	 *
	 * @return string
	 */
	public function get_manage_preferences_url() {
		$url = get_noptin_option( 'manage_preferences_url' );

		if ( empty( $url ) || get_noptin_action_url( 'manage_preferences' ) === $url ) {
			$url = get_noptin_action_url(
				'manage_preferences',
				noptin_encrypt(
					wp_json_encode(
						array( 'email' => $this->get_email() )
					)
				)
			);
		} else {
			$url = add_query_arg( 'noptin_key', $this->get_confirm_key(), $url );
		}

		return $url;
	}

	/**
	 * Returns the send email URL for the subscriber.
	 *
	 * @return string
	 */
	public function get_send_email_url() {
		return get_noptin_email_recipients_url( $this->get_id(), 'noptin' );
	}

	/**
	 * Returns the avatar URL for the subscriber.
	 *
	 * @return string
	 */
	public function get_avatar_url() {
		$name = $this->get_name();

		// If doesn't have a name, generate from email.
		if ( empty( $name ) ) {
			$name = strtok( $this->get_email(), '@' );
			$name = str_replace( '.', ' ', $name );
		}

		$color = noptin_get_random_background_color();
		$args  = array(
			'default' => sprintf(
				'https://ui-avatars.com/api/%s/64/%s/%s/2',
				rawurlencode( $name ),
				$color[0],
				$color[1]
			),
			'size'    => 64,
		);

		return get_avatar_url( $this->get_email(), $args );
	}

	/**
	 * Returns the WordPress user ID.
	 *
	 * @return int
	 */
	public function get_wp_user_id() {
		$user = get_user_by( 'email', $this->get_email() );
		return $user ? $user->ID : 0;
	}

	/**
	 * Save should create or update based on object existence.
	 *
	 * @since  1.0.0
	 * @return int|\WP_Error
	 */
	public function save() {

		// Confirmation key.
		$confirm_key = $this->get_confirm_key();

		if ( empty( $confirm_key ) ) {
			$this->set_confirm_key( md5( wp_generate_password( 100, true, true ) . uniqid() ) );
		}

		// Check email.
		if ( ! is_string( $this->get_email() ) || ! is_email( $this->get_email() ) ) {
			$email = $this->get_email();

			if ( empty( $email ) || ! is_string( $email ) ) {
				return new \WP_Error( 'invalid_email', __( 'Invalid email address.', 'newsletter-optin-box' ) );
			}

			return new \WP_Error(
				'invalid_email',
				sprintf(
					/* translators: %s: email address */
					__( 'Invalid email address: %s', 'newsletter-optin-box' ),
					$email
				)
			);
		}

		// If we're creating, make sure the email doesn't already exist.
		if ( ! $this->get_id() ) {
			$subscriber = get_noptin_subscriber_id_by_email( $this->get_email() );

			if ( $subscriber ) {
				return new \WP_Error( 'email_exists', __( 'This email address is already subscribed.', 'newsletter-optin-box' ) );
			}

			// If the confirm key exists, generate a new one.
			$subscriber = get_noptin_subscriber_id_by_confirm_key( $this->get_confirm_key() );

			if ( $subscriber ) {
				$this->set_confirm_key( md5( wp_generate_password( 100, true, true ) . uniqid() ) );
			}
		}

		// Prevent blocked subscribers from being saved.
		$current_status = $this->data['status'] ?? '';
		if ( 'blocked' === $current_status && array_key_exists( 'status', $this->changes ) && ! current_user_can( get_noptin_capability() ) ) {
			unset( $this->changes['status'] );
		}

		return parent::save();
	}

	/**
	 * Returns the record's overview.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_overview() {
		$sent_emails = $this->get_sent_campaigns();
		$total       = count( $sent_emails );
		$opens       = 0;
		$clicks      = 0;

		foreach ( $sent_emails as $email ) {
			if ( ! empty( $email['opens'] ) ) {
				++$opens;
			}

			if ( ! empty( $email['clicks'] ) ) {
				++$clicks;
			}
		}

		$overview = array(
			'stat_cards' => array(
				'type'  => 'stat_cards',
				'cards' => array(
					array(
						'title' => __( 'Emails Sent', 'newsletter-optin-box' ),
						'value' => $total,
					),
					array(
						'title' => __( 'Opened', 'newsletter-optin-box' ),
						'value' => ( $opens && $total ) ? ( round( ( $opens / $total ) * 100, 2 ) . '%' ) : '&mdash;',
					),
					array(
						'title' => __( 'Clicked', 'newsletter-optin-box' ),
						'value' => ( $clicks && $total ) ? ( round( ( $clicks / $total ) * 100, 2 ) . '%' ) : '&mdash;',
					),
				),
			),
		);

		return apply_filters( 'noptin_subscriber_overview', $overview, $this );
	}

	/**
	 * Returns the record's actions.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_hizzlewp_actions() {
		$actions = parent::get_hizzlewp_actions();

		// Manage Preferences URL.
		$actions[] = array(
			'id'    => 'manage_preferences_url',
			'type'  => 'copy',
			'text'  => __( 'Manage Preferences URL', 'newsletter-optin-box' ),
			'value' => $this->get_manage_preferences_url(),
			'icon'  => 'admin-customizer',
		);

		// Add link to user profile if the subscriber is a WordPress user.
		$user_id = $this->get_wp_user_id();

		if ( ! empty( $user_id ) ) {
			$actions[] = array(
				'id'     => 'view_user_profile',
				'text'   => __( 'View User Profile', 'newsletter-optin-box' ),
				'href'   => get_edit_user_link( $user_id ),
				'icon'   => 'admin-users',
				'target' => '_blank',
			);
		}

		// Send email if the subscriber is active.
		if ( $this->is_active() ) {
			$actions[] = array(
				'id'     => 'send_email',
				'text'   => __( 'Send Email', 'newsletter-optin-box' ),
				'href'   => $this->get_send_email_url(),
				'icon'   => 'email',
				'target' => '_blank',
			);

			$actions[] = array(
				'id'    => 'unsubscribe_url',
				'type'  => 'copy',
				'text'  => __( 'Unsubscribe URL', 'newsletter-optin-box' ),
				'value' => $this->get_unsubscribe_url(),
				'icon'  => 'minus',
			);
		} elseif ( 'unsubscribed' === $this->get_status() ) {
			$actions[] = array(
				'id'    => 'resubscribe_url',
				'type'  => 'copy',
				'text'  => __( 'Resubscribe URL', 'newsletter-optin-box' ),
				'value' => $this->get_resubscribe_url(),
				'icon'  => 'plus',
			);
		}

		// Email confirmation URL.
		if ( ! $this->get_confirmed() ) {
			$actions[] = array(
				'id'         => 'send_confirmation_email',
				'type'       => 'remote',
				'text'       => __( 'Send Confirmation Email', 'newsletter-optin-box' ),
				'actionName' => 'send_confirmation_email',
				'icon'       => 'email',
			);

			$actions[] = array(
				'id'    => 'email_confirmation_url',
				'type'  => 'copy',
				'text'  => __( 'Email Confirmation URL', 'newsletter-optin-box' ),
				'value' => $this->get_confirm_subscription_url(),
				'icon'  => 'email-alt',
			);
		}

		// Conversion page.
		$conversion_page = $this->get_conversion_page();

		if ( ! empty( $conversion_page ) ) {
			$actions[] = array(
				'id'     => 'conversion_page',
				'text'   => __( 'Conversion Page', 'newsletter-optin-box' ),
				'href'   => esc_url_raw( $conversion_page ),
				'icon'   => 'external',
				'target' => '_blank',
			);
		}

		// Block/unblock subscriber.
		if ( 'blocked' !== $this->get_status() ) {
			$actions[] = array(
				'id'            => 'block_subscriber',
				'type'          => 'remote',
				'text'          => __( 'Block Subscriber', 'newsletter-optin-box' ),
				'actionName'    => 'block_subscriber',
				'icon'          => 'minus',
				'isDestructive' => true,
			);
		} else {
			$actions[] = array(
				'id'         => 'unblock_subscriber',
				'type'       => 'remote',
				'text'       => __( 'Unblock Subscriber', 'newsletter-optin-box' ),
				'actionName' => 'unblock_subscriber',
				'icon'       => 'plus',
			);
		}
		return $actions;
	}

	/**
	 * Sends the confirm subscription email.
	 *
	 * @since 1.0.0
	 * @return array|\WP_Error
	 */
	public function do_send_confirmation_email() {

		// Check if the subscriber is already confirmed.
		if ( $this->get_confirmed() ) {
			return new \WP_Error( 'already_confirmed', 'This subscriber is already confirmed.' );
		}

		if ( ! use_custom_noptin_double_optin_email() ) {
			$result = send_new_noptin_subscriber_double_optin_email( $this->get_id(), true );
		} else {
			do_action( 'noptin_subscriber_status_set_to_pending', $this, 'new' );
			$result = true;
		}

		if ( empty( $result ) ) {
			return new \WP_Error( 'failed_to_send', 'Failed to send confirmation email.' );
		}

		return array(
			'message' => 'Confirmation email sent.',
		);
	}

	/**
	 * Blocks a subscriber.
	 *
	 * @since 1.0.0
	 * @return array|\WP_Error
	 */
	public function do_block_subscriber() {

		$this->set_status( 'blocked' );
		$this->save();

		return array(
			'message' => 'Subscriber blocked.',
		);
	}

	/**
	 * Unblocks a subscriber.
	 *
	 * @since 1.0.0
	 * @return array|\WP_Error
	 */
	public function do_unblock_subscriber() {
		$this->set_status( 'subscribed' );
		$this->save();

		return array(
			'message' => 'Subscriber unblocked.',
		);
	}
}

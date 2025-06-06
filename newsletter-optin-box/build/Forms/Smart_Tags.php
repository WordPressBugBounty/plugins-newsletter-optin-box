<?php
/**
 * Forms API: Dynamic Form Tags.
 *
 * Allows users to use dynamic tags in opt-in forms.
 *
 * @since   1.6.2
 * @package Noptin
 */

namespace Hizzle\Noptin\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Allows users to use dynamic tags in newsletter forms.
 *
 * @internal
 * @access private
 * @since 1.6.2
 * @ignore
 */
class Smart_Tags extends \Noptin_Dynamic_Content_Tags {

	/**
	 * Register core hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'noptin_form_response_html', array( $this, 'replace_in_form_response' ) );
		add_filter( 'noptin_form_text', array( $this, 'replace_in_form_content' ) );
		add_filter( 'noptin_form_redirect_url', array( $this, 'replace_in_url' ) );
	}

	/**
	 * Replaces the form content to include merge tags.
	 *
	 * @param string $response
	 * @return int
	 */
	public function replace_in_form_content( $response ) {
		return $this->replace( $response, 'wp_kses_post' );
	}

	/**
	 * Replaces the subscription response to include merge tags.
	 *
	 * @param string $response
	 * @return int
	 */
	public function replace_in_form_response( $response ) {

		// We do not want an infinite loop.
		$response = str_replace( '{response}', '', $response );

		return $this->replace( $response, 'wp_kses_post' );
	}

	/**
	 * Register template tags
	 */
	public function register() {
		parent::register();

		$this->tags['response'] = array(
			'description' => __( 'Replaced with the form response (error or success messages).', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_form_response' ),
		);

		$this->tags['data'] = array(
			'description' => sprintf( __( 'Data from the URL or a submitted form.', 'newsletter-optin-box' ) ),
			'callback'    => array( $this, 'get_data' ),
			'example'     => "data key='UTM_SOURCE' default='Default Source'",
		);

		$this->tags['subscriber_count'] = array(
			'description' => __( 'Replaced with the total number of subscribers', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_subscriber_count' ),
		);
	}

	/**
	 * Returns the form response
	 *
	 * @return string
	 */
	public function get_form_response() {
		$listener = Main::$listener;
		return $listener ? $listener->get_response_html() : '';
	}

	/**
	 * Returns the number of subscribers.
	 *
	 * @return int
	 */
	public function get_subscriber_count() {
		return get_noptin_subscribers_count();
	}

	/**
	 * Gets data value from GET or POST variables.
	 *
	 * @param array $args
	 * @return string
	 */
	public function get_data( $args = array() ) {
		if ( empty( $args['key'] ) ) {
			return '';
		}

		// Prepare value.
		$default = isset( $args['default'] ) ? $args['default'] : '';
		$key     = $args['key'];
		$data    = \Hizzle\Noptin\Forms\Main::$listener ? \Hizzle\Noptin\Forms\Main::$listener->submitted : array();
		$value   = isset( $data[ $key ] ) ? wp_unslash( $data[ $key ] ) : $default;
		$value   = isset( $data['noptin_fields'][ $key ] ) ? wp_unslash( $data['noptin_fields'][ $key ] ) : $value;

		// Turn array into readable value.
		if ( is_array( $value ) ) {
			$value = array_filter( $value );
			$value = implode( ', ', $value );
		}

		return esc_html( $value );
	}

}

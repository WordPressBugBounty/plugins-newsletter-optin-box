<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with WooCommerce
 *
 * @since       1.2.6
 */
class Noptin_WooCommerce extends Noptin_Abstract_Ecommerce_Integration {

	/**
	 * @var string Slug, used as an unique identifier for this integration.
	 * @since 1.2.6
	 */
	public $slug = 'woocommerce';

	/**
	 * @var string source of subscriber.
	 * @since 1.7.0
	 */
	public $subscriber_via = 'woocommerce_checkout';

	/**
	 * @var string The product's post type in case this integration saves products as custom post types.
	 * @since 1.3.0
	 */
	public $product_post_type = array( 'product', 'product_variation' );

	/**
	 * @var string Name of this integration.
	 * @since 1.2.6
	 */
	public $name = 'WooCommerce';

	/**
	 * Setup hooks in case the integration is enabled.
	 *
	 * @since 1.2.6
	 */
	public function initialize() {

		parent::initialize();

		// Orders.
		add_action( 'woocommerce_new_order', array( $this, 'add_order_subscriber' ), 1 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'add_order_subscriber' ), 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'add_order_subscriber' ), 1 );
	}

	/**
	 * Hooks the display checkbox code.
	 *
	 * @since 1.2.6
	 * @param string $checkbox_position The checkbox position to display the checkbox.
	 */
	public function hook_show_checkbox_code( $checkbox_position ) {

		if ( $this->can_show_checkbox() ) {
			if ( 'after_email_field' === $checkbox_position ) {
				add_filter( 'woocommerce_form_field_email', array( $this, 'add_checkbox_after_email_field' ), 100, 2 );
			} else {
				add_action( $checkbox_position, array( $this, 'output_checkbox' ), 20 );
			}

			// hooks for when using WooCommerce Checkout Block.
			add_action( 'woocommerce_init', array( $this, 'add_checkout_block_field' ) );

			if ( (bool) get_noptin_option( $this->get_autotick_checkbox_option_name() ) ) {
				add_filter(
					'woocommerce_get_default_value_for_noptin/optin',
					function ( $value ) {
						return '1';
					}
				);
			}

			if ( ! is_admin() ) {
				add_action(
					'woocommerce_set_additional_field_value',
					function ( $key, $value, $group, $wc_object ) {
						if ( 'noptin/optin' === $key ) {
							$wc_object->update_meta_data( 'noptin_opted_in', $value, true );
						}
					},
					10,
					4
				);
			}
		}

		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_woocommerce_checkout_checkbox_value' ) );
		add_filter( 'noptin_woocommerce_integration_subscription_checkbox_attributes', array( $this, 'add_woocommerce_class_to_checkbox' ) );
	}

	/**
	 * Returns an array of subscription checkbox positions.
	 *
	 * @since 1.2.6
	 * @return array
	 */
	public function checkbox_positions() {
		return apply_filters(
			'noptin_woocommerce_integration_subscription_checkbox_positions',
			array(
				'after_email_field'                       => __( 'After email field', 'newsletter-optin-box' ),
				'woocommerce_checkout_billing'            => __( 'After billing details', 'newsletter-optin-box' ),
				'woocommerce_checkout_shipping'           => __( 'After shipping details', 'newsletter-optin-box' ),
				'woocommerce_checkout_after_customer_details' => __( 'After customer details', 'newsletter-optin-box' ),
				'woocommerce_review_order_before_payment' => __( 'After order review', 'newsletter-optin-box' ),
				'woocommerce_review_order_before_submit'  => __( 'Before submit button', 'newsletter-optin-box' ),
				'woocommerce_after_order_notes'           => __( 'After order notes', 'newsletter-optin-box' ),
			)
		);
	}

	/**
	 * Did the user check the email subscription checkbox?
	 *
	 * @param WC_Order $order
	 */
	public function save_woocommerce_checkout_checkbox_value( $order ) {
		if ( $this->checkbox_was_checked() ) {
			$order->update_meta_data( 'noptin_opted_in', 1 );
		} else {
			$order->delete_meta_data( 'noptin_opted_in' );
		}
	}

	/**
	 * Was integration triggered?
	 *
	 * @param int $order_id Order id being executed.
	 * @return bool
	 */
	public function triggered( $order_id = null ) {

		$order = wc_get_order( $order_id );

		if ( empty( $order ) ) {
			return false;
		}

		// This is processed later.
		if ( 'checkout-draft' === $order->get_status() && ! doing_action( 'woocommerce_store_api_checkout_order_processed' ) ) {
			return false;
		}

		if ( $this->auto_subscribe() ) {
			return true;
		}

		// Shortcode checkout.
		$checked = $order->get_meta( 'noptin_opted_in', true );

		return ! empty( $checked );
	}

	public function add_checkout_block_field() {
		// for compatibility with older WooCommerce versions
		// check if function exists before calling
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		// get the location from the legacy method
		switch ( $this->get_checkbox_position() ) {
			case 'woocommerce_review_order_before_payment':
			case 'woocommerce_review_order_before_submit':
			case 'woocommerce_after_order_notes':
				$location = 'order';
				break;
			case 'woocommerce_checkout_billing':
			case 'woocommerce_checkout_shipping':
				$location = 'address';
				break;
			default:
				$location = 'contact';
				break;
		}

		woocommerce_register_additional_checkout_field(
			array(
				'id'            => 'noptin/optin',
				'location'      => $location,
				'type'          => 'checkbox',
				'label'         => $this->get_label_text(),
				'optionalLabel' => $this->get_label_text(),
			)
		);
	}

	/**
	 * Adds the checkbox after an email field.
	 *
	 * @return bool
	 */
	public function add_checkbox_after_email_field( $field, $key ) {
		if ( 'billing_email' !== $key ) {
			return $field;
		}

		return $this->append_checkbox( $field );
	}

	/**
	 * Prints the checkbox wrapper.
	 *
	 */
	public function before_checkbox_wrapper() {

		if ( 'woocommerce_checkout_after_customer_details' !== $this->get_checkbox_position() ) {
			echo '<p class="form-row form-row-wide" id="noptin_woocommerce_optin_checkbox">';
		}
	}

	/**
	 * Prints the checkbox closing wrapper.
	 *
	 */
	public function after_checkbox_wrapper() {

		if ( 'woocommerce_checkout_after_customer_details' !== $this->get_checkbox_position() ) {
			echo '</p>';
		}
	}

	/**
	 * Adds the woocommerce checkbox class to the subscription checkbox.
	 *
	 * @param array $attributes An array of checkbox attributes.
	 * @since 1.2.6
	 * @return array
	 */
	public function add_woocommerce_class_to_checkbox( $attributes ) {
		$attributes['class'] = 'input-checkbox';
		return $attributes;
	}

	/**
	 * Returns the checkbox message default value.
	 */
	public function get_checkbox_message_integration_default_value() {
		return __( 'Add me to your newsletter and keep me updated whenever you publish new products', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_order_customer_email( $order_id ) {

		$order = wc_get_order( $order_id );
		if ( empty( $order ) ) {
			return '';
		}

		if ( method_exists( $order, 'get_billing_email' ) ) {
			return $order->get_billing_email();
		}

		return $order->billing_email;
	}

	/**
	 * @inheritdoc
	 */
	public function get_order_customer_user_id( $order_id ) {

		$order = wc_get_order( $order_id );
		if ( empty( $order ) ) {
			return 0;
		}

		if ( method_exists( $order, 'get_customer_id' ) ) {
			return $order->get_customer_id();
		}

		return $order->customer_id;
	}

	/**
	 * @inheritdoc
	 */
	public function get_order_customer_details( $order_id, $existing_subscriber = false ) {

		// Fetch the order.
		$order = wc_get_order( $order_id );
		if ( empty( $order ) ) {
			return array();
		}

		$noptin_fields = array();

		if ( ! $existing_subscriber ) {
			$noptin_fields = array(
				'source'   => 'woocommerce_checkout',
				'order_id' => $order_id,
			);
		}

		if ( method_exists( $order, 'get_billing_email' ) ) {
			$noptin_fields['email']             = $order->get_billing_email();
			$noptin_fields['name']              = $order->get_formatted_billing_full_name();
			$noptin_fields['phone']             = $order->get_billing_phone();
			$noptin_fields['company']           = $order->get_billing_company();
			$noptin_fields['address_1']         = $order->get_billing_address_1();
			$noptin_fields['address_2']         = $order->get_billing_address_2();
			$noptin_fields['postcode']          = $order->get_billing_postcode();
			$noptin_fields['city']              = $order->get_billing_city();
			$noptin_fields['state']             = $order->get_billing_state();
			$noptin_fields['country']           = $order->get_billing_country();
			$noptin_fields['wp_user_id']        = $order->get_customer_id();
			$noptin_fields['ip_address']        = $order->get_customer_ip_address();
			$noptin_fields['user_agent']        = $order->get_customer_user_agent();
			$noptin_fields['formatted_address'] = $order->get_formatted_billing_address();

			if ( ! empty( $noptin_fields['country'] ) ) {
				$countries                      = WC()->countries->get_countries();
				$noptin_fields['country_short'] = $noptin_fields['country'];
				$noptin_fields['country']       = isset( $countries[ $noptin_fields['country'] ] ) ? $countries[ $noptin_fields['country'] ] : $noptin_fields['country'];
			}
		} else {
			$noptin_fields['email']      = $order->billing_email;
			$noptin_fields['name']       = trim( "{$order->billing_first_name} {$order->billing_last_name}" );
			$noptin_fields['wp_user_id'] = $order->customer_id;
			$noptin_fields['phone']      = $order->billing_phone;
			$noptin_fields['company']    = $order->billing_company;
			$noptin_fields['address_1']  = $order->billing_address_1;
			$noptin_fields['address_2']  = $order->billing_address_2;
			$noptin_fields['postcode']   = $order->billing_postcode;
			$noptin_fields['city']       = $order->billing_city;
			$noptin_fields['state']      = $order->billing_state;
			$noptin_fields['country']    = $order->billing_country;
			$noptin_fields['ip_address'] = $order->customer_ip_address;
			$noptin_fields['user_agent'] = $order->customer_user_agent;
		}

		return array_filter( $noptin_fields );
	}

	/**
	 * @inheritdoc
	 */
	public function available_customer_fields() {
		return array(
			'phone'             => __( 'Billing Phone', 'newsletter-optin-box' ),
			'company'           => __( 'Billing Company', 'newsletter-optin-box' ),
			'address_1'         => __( 'Billing Address 1', 'newsletter-optin-box' ),
			'address_2'         => __( 'Billing Address 2', 'newsletter-optin-box' ),
			'postcode'          => __( 'Billing Postcode', 'newsletter-optin-box' ),
			'city'              => __( 'Billing City', 'newsletter-optin-box' ),
			'state'             => __( 'Billing State', 'newsletter-optin-box' ),
			'country'           => __( 'Billing Country', 'newsletter-optin-box' ),
			'country_short'     => __( 'Billing Country Code', 'newsletter-optin-box' ),
			'formatted_address' => __( 'Formatted Billing Address', 'newsletter-optin-box' ),
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_order_details( $order_id ) {

		// Fetch the order.
		$order = wc_get_order( $order_id );
		if ( empty( $order ) ) {
			return parent::get_order_details( $order_id );
		}

		$details = array(
			'id'       => $order->get_id(),
			'order_id' => $order->get_id(),
			'total'    => $order->get_total(),
			'tax'      => $order->get_total_tax(),
			'fees'     => $order->get_total_fees(),
			'currency' => $order->get_currency(),
			'discount' => $order->get_total_discount(),
			'edit_url' => $order->get_edit_order_url(),
			'view_url' => $order->get_view_order_url(),
			'pay_url'  => $order->get_checkout_payment_url(),
			'status'   => str_replace( 'wc-', '', $order->get_status() ),

			'title'    => sprintf(
				// translators: %1$s is the order id, %2$s is the customer email.
				esc_html__( 'Order #%1$d from %2$s', 'newsletter-optin-box' ),
				$order->get_id(),
				$order->get_billing_email()
			),
			'items'    => array_map(
				array( $this, 'get_order_item_details' ),
				$order->get_items()
			),
		);

		// Date the order was created.
		$details['date_created'] = $order->get_date_created();
		if ( ! empty( $details['date_created'] ) ) {
			$details['date_created'] = $details['date_created']->__toString();
		}

		// Date it was paid.
		$details['date_paid'] = $order->get_date_completed();
		if ( ! empty( $details['date_paid'] ) ) {
			$details['date_paid'] = $details['date_paid']->__toString();
		}

		return $details;
	}

	/**
	 * Returns an array of order item details.
	 *
	 * @param WC_Order_Item_Product $item The item id.
	 * @since 1.3.0
	 * @return array
	 */
	protected function get_order_item_details( $item ) {

		$product_id   = $item->get_product_id();
		$variation_id = $item->get_variation_id();

		if ( empty( $variation_id ) ) {
			$variation_id = $item->get_product_id();
		}

		return array(
			'item_id'         => $item->get_id(),
			'product_id'      => $product_id,
			'variation_id'    => $variation_id,
			'name'            => $item->get_name(),
			'price'           => $item->get_total(),
			'total_formatted' => wc_price( $item->get_total() ),
			'quantity'        => $item->get_quantity(),
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_order_count( $customer_id_or_email ) {
		return \Hizzle\Noptin\Integrations\WooCommerce\Main::count_customer_orders( $customer_id_or_email );
	}

	/**
	 * @inheritdoc
	 */
	public function get_total_spent( $customer_id_or_email ) {
		return \Hizzle\Noptin\Integrations\WooCommerce\Main::calculate_customer_lifetime_value( $customer_id_or_email );
	}

	/**
	 * @inheritdoc
	 */
	public function get_product_purchase_count( $customer_id_or_email = null, $product_id = 0 ) {

		$orders = wc_get_orders(
			array(
				'limit'    => -1,
				'customer' => $customer_id_or_email,
				'status'   => array( 'wc-completed', 'wc-processing', 'wc-refunded' ),
			)
		);

		$count = 0;

		// Loop through each order.
		$product_id = (int) $product_id;
   		foreach ( $orders as $order ) {

			// Fetch the items.
			$items = $order->get_items();

			// Compare each product to our product.
	  		foreach ( $items as $item ) {
				$item = $this->get_order_item_details( $item );

				if ( $product_id === (int) $item['product_id'] ) {
					++ $count;
		 		} elseif ( $product_id === (int) $item['variation_id'] ) {
					++ $count;
				}
			}
   		}

		return $count;
	}
}

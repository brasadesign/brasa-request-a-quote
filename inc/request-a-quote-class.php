<?php
/**
  * Class Brasa_Request_A_Quote
  */
class Brasa_Request_A_Quote {

	/**
	 * Set if quote cart template is showing
	 * @var boolean
	 */
	private $is_quote_cart = false;

	/**
	 * Prefix to use in save options
	 * @var string
	 */
	private $cart_prefix = '_cart';

	/**
	 * Array to save cart items
	 * @var array
	 */
	private $save_cart = array();

	/**
	 * Class Constructor
	 * @return null
	 */
	public function __construct() {
		// Check if WooCommerce is active
		add_action( 'admin_notices', array( $this, 'check_woocommerce_is_install' ) );

		// Filter WooCommerce Default Cart to remove quote items
		add_filter( 'woocommerce_cart_item_product', array( $this, 'filter_woocommerce_default_cart' ), 9999999 );

		// Add Quote cart
		add_action( 'woocommerce_after_cart', array( $this, 'add_quote_cart' ) );

		// Add Quote colaterals
		//add_action( 'woocommerce_cart_collaterals', array( $this, 'add_quote_informations' ) );


		// Remove default cart
		add_action( 'woocommerce_before_cart', array( $this, 'remove_default_cart' ) );

		// Add get param to checkout URL
		add_filter( 'woocommerce_get_checkout_url', array( $this, 'add_quote_param_checkout_url' ), 9999999 );

		// Change WooCommerce strings to request a quote
		add_filter( 'gettext', array( $this, 'change_woocommerce_strings' ), 20, 3 );

		// Remove coupons button on quote cart
		//add_filter( 'woocommerce_coupons_enabled', array( $this, 'remove_coupons_button_on_quote_cart' ), 9999999 );

		// Remove shipping form
		add_filter( 'woocommerce_cart_needs_shipping_address', array( $this, 'remove_shipping_form' ), 9999999 );

		// Change checkout fields
		//add_filter( 'woocommerce_default_address_fields', array( $this, 'change_checkout_fields' ), 999999999999999999999 );

		// Change checkout fields
		add_filter( 'woocommerce_cart_needs_payment', array( $this, 'remove_payment' ), 9999999 );

		// Add body class on quote checkout
		add_filter( 'body_class', array( $this, 'add_body_classes' ), 9999999 );

		// Hide CSS items
		add_action( 'wp_enqueue_scripts', array( $this, 'hide_css_items' ), 9999999 );

		// Remove price on quote products in cart
		//add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'remove_price_cart' ), 9999999 );

		// Add hidden field to quote checkout
		add_action( 'woocommerce_after_order_notes', array( $this, 'add_checkout_hidden_field' ), 9999999 );

		// Process checkout quote
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'quote_checkout_process' ), 9999999 );

		// Separate each product
		add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart_items' ), 9999999 );

		// Save new cart
		add_action( 'woocommerce_cart_emptied', array( $this, 'save_cart' ), 9999999 );
		add_action( 'wp', array( $this, 'save_cart' ), 9999999 );

		// Add new cart
		add_action( 'get_header', array( $this, 'add_new_cart' ), 9999999999999999 );

		// Change add to cart text
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'change_add_to_cart_text' ), 9999999999999999 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'change_add_to_cart_text' ), 9999999999999999 );

		// Set quote products to virtual
		add_action( 'save_post_product', array( $this, 'save_post_product' ), 9999999999999999 );

		// Set quote products to virtual
		add_filter( 'woocommerce_get_formatted_order_total', array( $this, 'remove_total_price_order' ), 9999999999999999 );

		// Set quote products to virtual
		//add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'adjust_price_by_type' ), 9999999999999999 );

		// Add new cart
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'woocommerce_cart_loaded_from_session' ), 9999999999999999 );

	}

	/**
	 * Check if WooCommerce is active. If not, send a admin notice
	 * @return null
	 */
	public function check_woocommerce_is_install() {
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    		printf( '<div class="error"><p>%s</p></div>', __( '<b>Brasa Request a Quote:</b> WooCommerce is needed to activate', 'brasa-request-a-quote' ) );
		}
	}
	/**
	 * Check if is quote order
	 * @param string $order_id
	 * @return boolean
	 */
	public function is_quote_order( $order_id ) {
		if ( $field = get_post_meta( $order_id, 'is_request_a_quote_order', true ) ) {
			if ( $field == 'true' ) {
				return true;
			}
		}
		return false;
	}
	/**
	 * Check if is quote product
	 * @param string $product_id
	 * @return boolean
	 */
	public function is_quote_product( $product_id ) {
		if ( $field = get_post_meta( $product_id, 'is_request_a_quote', true ) ) {
			if ( $field == 'true' ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if cart item is quote product, if true, remove from the cart list
	 * @param object $data
	 * @param array $cart_item
	 * @param array $cart_item_key
	 * @return mixed
	 */
	public function filter_woocommerce_default_cart( $data, $cart_item = array(), $cart_item_key = array() ) {
		if ( is_cart() ) {
			if ( ! $this->is_quote_cart && $this->is_quote_product( $data->post->ID ) ) {
				return false;
			}
			if ( $this->is_quote_cart && ! $this->is_quote_product( $data->post->ID ) ) {
				return false;
			}
		}
		if ( is_checkout() ) {
			if ( ! $this->is_quote_checkout() && $this->is_quote_product( $data->post->ID ) ) {
				return false;
			}
			if ( $this->is_quote_checkout() && ! $this->is_quote_product( $data->post->ID ) ) {
				return false;
			}

		}
		return $data;
	}
	/**
	 * Check if has quote product on cart
	 * @return boolean
	 */
	public function has_quote_product_on_cart () {
		global $woocommerce;
		if ( $woocommerce->cart->is_empty() ) {
			return false;
		}
		$items = $woocommerce->cart->get_cart();
		foreach( $items as $item => $values ) {
			if ( $this->is_quote_product( $values['data']->post->ID ) ) {
				return true;
				break;
			}
		}
		return false;
	}
	/**
	 * Check if has quote product on cart
	 * @return boolean
	 */
	public function has_default_product_on_cart () {
		global $woocommerce;
		if ( $woocommerce->cart->is_empty() ) {
			return false;
		}
		$items = $woocommerce->cart->get_cart();
		foreach( $items as $item => $values ) {
			if ( ! $this->is_quote_product( $values['data']->post->ID ) ) {
				return true;
				break;
			}
		}
		return false;
	}

	/**
	 * Add quote cart on cart page
	 * @return null
	 */
	public function add_quote_cart() {
		if ( ! $this->has_default_product_on_cart() && ! $this->is_quote_cart ) {
			ob_end_clean();
		}

		if ( ! $this->is_quote_cart && $this->has_quote_product_on_cart() ) {
			$this->is_quote_cart = true;
			$this->add_quote_informations();

			// Remove woocommerce checkout options
			remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cart_totals', 10 );
			remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
			echo apply_filters( 'brasa_request_a_quote_cart_div_open', '<div class="brasa-request-a-quote-cart">' );
			printf( apply_filters( 'brasa_request_a_quote_cart_title_html', '<h3 class="title request-a-quote-title">%s</h1>' ), __( 'Request a Quote', 'brasa-request-a-quote' ) );
			wc_get_template( 'cart/cart.php' );
			echo apply_filters( 'brasa_request_a_quote_cart_div_close', '</div><!-- .brasa-request-a-quote-cart -->' );
			$this->is_quote_cart = false;
		}
	}
	public function add_quote_informations() {
		if ( ! $this->has_default_product_on_cart() && $this->is_quote_cart ) {
			echo '<div class="cart-collaterals">';
			wc_get_template( 'cart/cart-totals.php' );
			echo  '</div>';
		}
	}
	/**
	 * Add quote get param to checkout url
	 * @param string $url
	 * @return string
	 */
	public function add_quote_param_checkout_url( $url ) {
		if ( $this->is_quote_cart ) {
			return $url . '?brasa_request_a_quote_checkout=true';
		}
		return $url . '?is_checkout';
	}
	/**
	 * Change add to cart text in quote products
	 * @param  string $text
	 * @return string
	 */
	public function change_add_to_cart_text ( $text = null ) {
		global $post;
		if ( $this->is_quote_product( $post->ID ) ){
			return apply_filters( 'add_to_quote_text', __( 'Add to Quote', 'brasa-request-a-quote' ) );
		}
		return $text;
	}
	/**
	 * Change WooCommerce text to Quote
	 * @param string $translated_text
	 * @param string $text
	 * @param string $text_domain
	 * @return string
	 */
	public function change_woocommerce_strings ( $translated_text = null, $text = null, $text_domain = null ) {
		global $post, $wp;
		if ( $text_domain == 'brasa-request-a-quote' ) {
			return $translated_text;
		}
		if ( $text_domain != 'woocommerce' ) {
			return $translated_text;
		}

		load_plugin_textdomain( 'brasa-request-a-quote', false, BRASA_REQUEST_A_QUOTE_DIR . '/languages/' );

		if ( $this->is_quote_cart ) {
			if ( $text == 'Proceed to Checkout' ) {
				return __( 'Send Request a Quote', 'brasa-request-a-quote' );
			}
			if ( $text == 'Update Cart' ) {
				return __( 'Update Quote', 'brasa-request-a-quote' );
			}
			if ( $text == 'Total' ) {
				return '';
			}

		}
		if ( $this->is_quote_checkout() ) {
			if ( $text == 'Billing Details' ) {
				return __( 'Quote Details', 'brasa-request-a-quote' );
			}
			if ( $text == 'Order Notes' ) {
				return __( 'Quote Notes', 'brasa-request-a-quote' );
			}
			if ( $text == 'Place order' ) {
				return __( 'Place Quote', 'brasa-request-a-quote' );
			}
			if ( $text == 'Total' ) {
				return '';
			}
			if ( $text == 'Your order' ) {
				return __( 'Your Quote', 'brasa-request-a-quote' );
			}
		}

		if ( isset ( $wp->query_vars['order-received'] ) && $this->is_quote_order( $wp->query_vars['order-received'] ) ) {
			if ( $text == 'Total' ) {
				return '';
			}
			if ( $text == 'Total:' ) {
				return '';
			}
			if ( $text == 'Order Details' ) {
				return __( 'Quote Details', 'brasa-request-a-quote' );
			}
		}

		return $translated_text;
	}
	/**
	 * Remove coupons area on quote cart
	 * @param boolean $value
	 * @return boolean;
	 */
	public function remove_coupons_button_on_quote_cart ( $value ) {
		if ( $this->is_quote_cart || $this->is_quote_checkout() ) {
			return false;
		}
		return $value;
	}
	/**
	 * Check if is quote checkout
	 * @return boolean
	 */
	public function is_quote_checkout() {
		if ( is_checkout() && isset( $_GET[ 'brasa_request_a_quote_checkout' ] ) ) {
			return true;
		}
		return false;
	}
	/**
	 * Remove shipping fields on quote checkout
	 * @param boolean $value
	 * @return boolean
	 */
	public function remove_shipping_form( $value ) {
		if ( $this->is_quote_checkout() ) {
			return false;
		}
		return $value;
	}
	/**
	 * Change/Remove WooCommerce Checkout fields on quote checkout
	 * @param array $fields
	 * @return array
	 */
	public function change_checkout_fields( $fields ) {
		if ( $this->is_quote_checkout() ) {
			if ( isset( $fields[ 'postcode' ] ) ) {
				unset( $fields[ 'postcode' ] );
			}
		}
		return $fields;
	}
	/**
	 * Remove payment on quote checkout
	 * @param boolean $value
	 * @return boolean
	 */
	public function remove_payment( $value ) {
		if ( $this->is_quote_checkout() ) {
			return false;
		}
		if ( isset( $_POST[ 'is_request_a_quote_order' ] ) && $_POST[ 'is_request_a_quote_order' ] == 'true' ) {
			return false;
		}
		return $value;
	}
	/**
	 * Add body class in checkout
	 * @param type $classes
	 * @return type
	 */
	public function add_body_classes( $classes ) {
		if ( $this->is_quote_checkout() ) {
			$classes[] = 'quote-checkout';
		}
		return $classes;
	}
	/**
	 * Add CSS style to hide unused WooCommerce features
	 * @return type
	 */
	public function hide_css_items() {
		global $wp;
		if ( $this->is_quote_checkout() || is_cart() ) {
			wp_enqueue_style( 'brasa-request-a-quote-css', BRASA_REQUEST_A_QUOTE_URL . 'public/assets/css/style.css' );
			return;
		}

		if ( isset( $wp->query_vars['order-received'] ) && $this->is_quote_order( $wp->query_vars['order-received'] ) ) {
			wp_enqueue_style( 'brasa-request-a-quote-css', BRASA_REQUEST_A_QUOTE_URL . 'public/assets/css/style.css' );
			return;
		}
	}
	/**
	 * Remove price from quote cart
	 * @param string $price
	 * @param array $cart_item
	 * @param array $cart_item_key
	 * @return string
	 */
	public function remove_price_cart( $price, $cart_item = array(), $cart_item_key = array() ) {
		if ( $this->is_quote_cart ) {
			return '';
		}
		return $price;
	}
	/**
	 * Add checkout field to set if is quote
	 * @return boolean
	 */
	public function add_checkout_hidden_field() {
		if ( $this->is_quote_checkout() ) {
			$value = 'true';
		}
		else {
			$value = 'false';
		}

		echo '<div id="quote-checkout-hidden" style="display:none;">';
		woocommerce_form_field( 'is_request_a_quote_order', array(
        	'type'          => 'text',
        	'class'         => array('request-a-quote-hidden'),
        	'label'         => '',
        	'placeholder'   => '',
        	), $value
		);
		echo '</div>';
	}
	/**
	 * Save meta for show if is quote order
	 * @param string $order_id
	 * @return boolean
	 */
	public function quote_checkout_process( $order_id, $posted = null ) {
		if ( isset( $_POST[ 'is_request_a_quote_order' ] ) && $_POST[ 'is_request_a_quote_order' ] == 'true' ) {
			update_post_meta( $order_id, 'is_request_a_quote_order', 'true' );
			do_action( 'quote_checkout_process', $order_id );
		}
	}
	/**
	 * Get item product id
	 * @param object $post
	 * @return int
	 */
	public function get_item_product_id( $post ) {
		if ( get_post_type( $post->ID ) == 'product_variation' ) {
			return $post->post_parent;
		}
		return $post->ID;
	}
	/**
	 * Check cart items & remove items type if not like current checkout
	 * @return boolean
	 */
	public function check_cart_items() {
		if ( isset( $_POST[ 'is_request_a_quote_order' ] ) && $_POST[ 'is_request_a_quote_order' ] == 'true' ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$post_id = $this->get_item_product_id( $values['data']->post );
				if ( ! $this->is_quote_product( $values['data']->post->ID ) ) {
					$this->save_cart[$cart_item_key]['cart_item_key'] = $cart_item_key;
					$this->save_cart[$cart_item_key]['values'] = $values;
					WC()->cart->remove_cart_item( $cart_item_key );
				}
			}
		}
		if ( isset( $_POST[ 'is_request_a_quote_order' ] ) && $_POST[ 'is_request_a_quote_order' ] == 'false' ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				if ( $this->is_quote_product( $values['data']->post->ID ) ) {
					$this->save_cart[$cart_item_key]['cart_item_key'] = $cart_item_key;
					$this->save_cart[$cart_item_key]['values'] = $values;
					WC()->cart->remove_cart_item( $cart_item_key );
				}
			}
		}
	}
	/**
	 * Save cart items in option field
	 * @return boolean
	 */
	public function save_cart() {
		if ( ! empty( $this->save_cart ) ) {
			if ( $key = $this->get_woocommerce_session_key() ) {
				$key = $key . $this->cart_prefix;
				update_option( $key, $this->save_cart );
			}
		}
	}
	/**
	 * Save cart on a variable
	 * @param string $type
	 * @return boolean
	 */
	private function save_cart_items( $type ) {
		foreach( WC()->cart->get_cart() as $cart_item_key => $values ){
			$post_id = $this->get_item_product_id( $values['data']->post );

			if ( $type == 'default' ) {
				if ( $this->is_quote_product( $post_id ) ) {
					$this->save_cart[$cart_item_key]['cart_item_key'] = $cart_item_key;
					$this->save_cart[$cart_item_key]['values'] = $values;
					WC()->cart->remove_cart_item( $cart_item_key );
				}
			} else {
				if ( ! $this->is_quote_product( $post_id ) ) {
					$this->save_cart[$cart_item_key]['cart_item_key'] = $cart_item_key;
					$this->save_cart[$cart_item_key]['values'] = $values;
					WC()->cart->remove_cart_item( $cart_item_key );
				}
			}
		}
		$this->save_cart();
	}

	/**
	 * Add new cart after checkout
	 * @return boolean
	 */
	public function add_new_cart() {
		$key = $this->get_woocommerce_session_key();
		if ( ! $key ) {
			return;
		}
		$option_name = $key . $this->cart_prefix;

		if ( $cart = get_option( $option_name, false ) ) {
			$this->save_cart = $cart;
			if ( $this->save_cart && ! empty( $this->save_cart ) ) {
				foreach ( $this->save_cart as $product ) {
					$product_id = (string) $product['values']['product_id'];
					$qty = $product['values']['quantity'];
					$variation_id = $product['values']['variation_id'];
					$variation = $product['values']['variation'];
					$cart_item_data = array();

					WC()->cart->add_to_cart( $product_id, $qty, $variation_id, $variation, $cart_item_data );
				}
				// delete field after create cart
				delete_option( $option_name );
			}
		}
	}
	/**
	 * Save post as virtual product
	 * @param int $post_id
	 * @param object|null $post_object
	 * @param boolean|null $update
	 * @return boolean
	 */
	public function save_post_product( $post_id, $post_object = null, $update = null ) {
		if( ! isset( $_POST[ 'is_request_a_quote' ] ) ) {
			return;
		}

		if ( $_POST[ 'is_request_a_quote' ] == 'true' || ! isset( $_POST[ '_virtual' ] ) ) {
			$_POST[ '_virtual' ] = 'on';
		}
	}
	/**
	 * Remove price on order-received
	 * @param string $price
	 * @param object|bool $order
	 * @return string
	 */
	public function remove_total_price_order( $price, $order = false ) {
		global $wp;
		if ( isset( $wp->query_vars['order-received'] ) ) {
			$order = $wp->query_vars['order-received'];
		}
		if ( $this->is_quote_order( $order ) ) {
			return '';
		}
		return $price;
	}
	/**
	 * Remove default cart if is empty
	 * @return null;
	 */
	public function remove_default_cart() {
		if ( $this->has_default_product_on_cart() || $this->is_quote_cart ) {
			return;
		}
		ob_start();
	}
	/**
	 * Get WooCommerce session key on cookie
	 * @return boolean|string
	 */
	public function get_woocommerce_session_key() {
		if ( ! isset( $_COOKIE ) || ! is_array( $_COOKIE ) || empty( $_COOKIE ) ) {
			return false;
		}
		$session_key = false;

		foreach ( $_COOKIE as $key => $value) {
			$pos = strpos( $key, 'wp_woocommerce_session_' );
			if ( $pos !== false ) {
				$session_key = $key;
			}
		}

		return $session_key;
	}
	public function woocommerce_cart_loaded_from_session( $cart = false ) {
		global $wp_actions;
		if ( ! get_queried_object_id() ) {
			//unset( $wp_actions[ 'woocommerce_cart_loaded_from_session' ] );
		}


		if ( isset( $_GET[ 'brasa_request_a_quote_checkout' ] ) ) {
			$this->save_cart_items( 'quote' );
			return;
		}
		if ( isset( $_GET[ 'is_checkout' ] ) ) {
			$this->save_cart_items( 'default' );
		}
	}

}
global $brasa_request_quote;
$brasa_request_quote = new Brasa_Request_A_Quote();


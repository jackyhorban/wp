<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementor
 */

require("vendor/autoload.php");
require_once 'braintree/lib/Braintree.php';

use TomorrowIdeas\Plaid\Plaid;
use TomorrowIdeas\Plaid\Entities\User;
use TomorrowIdeas\Plaid\Entities\AccountHolder;
use TomorrowIdeas\Plaid\PlaidRequestException;
use Braintree\Gateway;

class PLAID_KEYS {
    const CLIENT_ID = "65a5e862f70cf3001b2f9824";
    const SANDBOX = "03e725c6f1d2e458d76bce9124832a";
    const DEVELOPMENT = "21e916b5edde1beaa508c62e421198";
    const PRODUCTION = "6401fe8af35aa1c636c89d60cefa11";
}

$AIRWALLEX_API = 'https://api.airwallex.com/api';

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_VERSION', '3.1.1' );
define( 'BOIR_LOG_PATH', '/home/drively1/boir.org/wp-content/themes/hello-elementor/');

if ( ! isset( $content_width ) ) {
	$content_width = 800; // Pixels.
}

if ( ! function_exists( 'hello_elementor_setup' ) ) {
	/**
	 * Set up theme support.
	 *
	 * @return void
	 */
	function hello_elementor_setup() {
		if ( is_admin() ) {
			hello_maybe_update_theme_version_in_db();
		}

		if ( apply_filters( 'hello_elementor_register_menus', true ) ) {
			register_nav_menus( [ 'menu-1' => esc_html__( 'Header', 'hello-elementor' ) ] );
			register_nav_menus( [ 'menu-2' => esc_html__( 'Footer', 'hello-elementor' ) ] );
		}

		if ( apply_filters( 'hello_elementor_post_type_support', true ) ) {
			add_post_type_support( 'page', 'excerpt' );
		}

		if ( apply_filters( 'hello_elementor_add_theme_support', true ) ) {
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'automatic-feed-links' );
			add_theme_support( 'title-tag' );
			add_theme_support(
				'html5',
				[
					'search-form',
					'comment-form',
					'comment-list',
					'gallery',
					'caption',
					'script',
					'style',
				]
			);
			add_theme_support(
				'custom-logo',
				[
					'height'      => 100,
					'width'       => 350,
					'flex-height' => true,
					'flex-width'  => true,
				]
			);

			/*
			 * Editor Style.
			 */
			add_editor_style( 'classic-editor.css' );

			/*
			 * Gutenberg wide images.
			 */
			add_theme_support( 'align-wide' );

			/*
			 * WooCommerce.
			 */
			if ( apply_filters( 'hello_elementor_add_woocommerce_support', true ) ) {
				// WooCommerce in general.
				add_theme_support( 'woocommerce' );
				// Enabling WooCommerce product gallery features (are off by default since WC 3.0.0).
				// zoom.
				add_theme_support( 'wc-product-gallery-zoom' );
				// lightbox.
				add_theme_support( 'wc-product-gallery-lightbox' );
				// swipe.
				add_theme_support( 'wc-product-gallery-slider' );
			}
		}
	}
}
add_action( 'after_setup_theme', 'hello_elementor_setup' );

function hello_maybe_update_theme_version_in_db() {
	$theme_version_option_name = 'hello_theme_version';
	// The theme version saved in the database.
	$hello_theme_db_version = get_option( $theme_version_option_name );

	// If the 'hello_theme_version' option does not exist in the DB, or the version needs to be updated, do the update.
	if ( ! $hello_theme_db_version || version_compare( $hello_theme_db_version, HELLO_ELEMENTOR_VERSION, '<' ) ) {
		update_option( $theme_version_option_name, HELLO_ELEMENTOR_VERSION );
	}
}

if ( ! function_exists( 'hello_elementor_display_header_footer' ) ) {
	/**
	 * Check whether to display header footer.
	 *
	 * @return bool
	 */
	function hello_elementor_display_header_footer() {
		$hello_elementor_header_footer = true;

		return apply_filters( 'hello_elementor_header_footer', $hello_elementor_header_footer );
	}
}

if ( ! function_exists( 'hello_elementor_scripts_styles' ) ) {
	/**
	 * Theme Scripts & Styles.
	 *
	 * @return void
	 */
	function hello_elementor_scripts_styles() {
		$min_suffix = ''; //defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( apply_filters( 'hello_elementor_enqueue_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor',
				get_template_directory_uri() . '/style' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( apply_filters( 'hello_elementor_enqueue_theme_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor-theme-style',
				get_template_directory_uri() . '/theme' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( hello_elementor_display_header_footer() ) {
			wp_enqueue_style(
				'hello-elementor-header-footer',
				get_template_directory_uri() . '/header-footer' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_scripts_styles' );

if ( ! function_exists( 'hello_elementor_register_elementor_locations' ) ) {
	/**
	 * Register Elementor Locations.
	 *
	 * @param ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $elementor_theme_manager theme manager.
	 *
	 * @return void
	 */
	function hello_elementor_register_elementor_locations( $elementor_theme_manager ) {
		if ( apply_filters( 'hello_elementor_register_elementor_locations', true ) ) {
			$elementor_theme_manager->register_all_core_location();
		}
	}
}
add_action( 'elementor/theme/register_locations', 'hello_elementor_register_elementor_locations' );

if ( ! function_exists( 'hello_elementor_content_width' ) ) {
	/**
	 * Set default content width.
	 *
	 * @return void
	 */
	function hello_elementor_content_width() {
		$GLOBALS['content_width'] = apply_filters( 'hello_elementor_content_width', 800 );
	}
}
add_action( 'after_setup_theme', 'hello_elementor_content_width', 0 );

if ( ! function_exists( 'hello_elementor_add_description_meta_tag' ) ) {
	/**
	 * Add description meta tag with excerpt text.
	 *
	 * @return void
	 */
	function hello_elementor_add_description_meta_tag() {
		if ( ! apply_filters( 'hello_elementor_description_meta_tag', true ) ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( empty( $post->post_excerpt ) ) {
			return;
		}

		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $post->post_excerpt ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'hello_elementor_add_description_meta_tag' );

add_action( 'wp_head', function(){
    wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js');
} );

// Admin notice
if ( is_admin() ) {
	require get_template_directory() . '/includes/admin-functions.php';
}

// Settings page
require get_template_directory() . '/includes/settings-functions.php';

// Header & footer styling option, inside Elementor
require get_template_directory() . '/includes/elementor-functions.php';

if ( ! function_exists( 'hello_elementor_customizer' ) ) {
	// Customizer controls
	function hello_elementor_customizer() {

    	if( !session_id() ) {
    		session_start();
    	}

	   // set_boir_id_by_token();

		if ( ! is_customize_preview() ) {
			return;
		}

		if ( ! hello_elementor_display_header_footer() ) {
			return;
		}

		require get_template_directory() . '/includes/customizer-functions.php';
	}
}
add_action( 'init', 'hello_elementor_customizer' );

if ( ! function_exists( 'hello_elementor_check_hide_title' ) ) {
	/**
	 * Check whether to display the page title.
	 *
	 * @param bool $val default value.
	 *
	 * @return bool
	 */
	function hello_elementor_check_hide_title( $val ) {
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$current_doc = Elementor\Plugin::instance()->documents->get( get_the_ID() );
			if ( $current_doc && 'yes' === $current_doc->get_settings( 'hide_title' ) ) {
				$val = false;
			}
		}
		return $val;
	}
}
add_filter( 'hello_elementor_page_title', 'hello_elementor_check_hide_title' );

/**
 * BC:
 * In v2.7.0 the theme removed the `hello_elementor_body_open()` from `header.php` replacing it with `wp_body_open()`.
 * The following code prevents fatal errors in child themes that still use this function.
 */
if ( ! function_exists( 'hello_elementor_body_open' ) ) {
	function hello_elementor_body_open() {
		wp_body_open();
	}
}

function ajax_enqueue() {

    wp_enqueue_script( 'ajax-script', '/wp-content/themes/hello-elementor/assets/js/ajax-script.js' );
    wp_localize_script( 'ajax-script', 'ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

}
add_action( 'wp_enqueue_scripts', 'ajax_enqueue' );

add_action('template_redirect', function() {
    $current_url = home_url($_SERVER['REQUEST_URI']);
    $remote_ip = $_SERVER['REMOTE_ADDR'];

    $timestamp = date('Y-m-d H:i:s');
    $log_msg = "Page visited at {$timestamp} - URL: {$current_url} IP: {$remote_ip} \n";
    // error_log($log_msg);

    file_put_contents(BOIR_LOG_PATH."url_trigger_log", $log_msg, FILE_APPEND);

    if (strpos($_SERVER['REQUEST_URI'], '/completed') === 0 && $_SERVER['HTTP_HOST'] === 'boir.org')
        file_put_contents(BOIR_LOG_PATH."conversion_log", $log_msg, FILE_APPEND);
    if (strpos($_SERVER['REQUEST_URI'], '/success') === 0 && $_SERVER['HTTP_HOST'] === 'boir.org')
        file_put_contents(BOIR_LOG_PATH."conversion_log", $log_msg, FILE_APPEND);


    if (strpos($_SERVER['REQUEST_URI'], '/update-your-boir') === 0 && $_SERVER['HTTP_HOST'] === 'boir.org') {
        $query_string = $_SERVER['QUERY_STRING']; // Get query string from old URL
        $new_url = 'https://new.boir.org/update-your-boir/';
        if ($query_string) {
            $new_url .= '?' . $query_string; // Append query string
        }
        wp_redirect($new_url, 301);
        exit;
    }

    if (strpos($_SERVER['REQUEST_URI'], '/update-initial-info') === 0 && $_SERVER['HTTP_HOST'] === 'boir.org') {
        $query_string = $_SERVER['QUERY_STRING']; // Get query string from old URL
        $new_url = 'https://new.boir.org/update-initial-info/';
        if ($query_string) {
            $new_url .= '?' . $query_string; // Append query string
        }
        wp_redirect($new_url, 301);
        exit;
    }

});

function get_boir_second_link() {
	$param = '';
	if(isset($_GET['submission_id']) && $_GET['submission_id'] != '')
		$param = '?submission_id='.sanitize_text_field($_GET['submission_id']);
	else {
	    if (isset($_SESSION['boir_id']) && $_SESSION['boir_id'] != '')
    		$param = '?submission_id='.$_SESSION['boir_id'];
	}

    $html = '<a href="/completed'.$param.'" class="btn btn-primary btn-lg mb-5 px-5 py-3 mt-3 text-white">See Submissin Status</a>';

    return $html;
}
add_shortcode('boir_second_link', 'get_boir_second_link');

function get_filing_id() {
    if (isset($_GET['submission_id']) && $_GET['submission_id'] != '')
        return $_GET['submission_id'];
    else if (isset($_SESSION['boir_id'])) {
        return $_SESSION['boir_id'];
    }
    return 'n/a';
}

add_shortcode('filing_id', 'get_filing_id');

/**
 * Implementation for the payment and the submission of BOI repport
 */

// Receive checkout form and Do initial checkout
function ajax_submit_checkout_form() {

    error_log("Submitting the nmi form...");

	$_SESSION['customerData'] = [
	    'first_name' => $_POST['first_name'],
	    'last_name' => $_POST['last_name'],
	    'card_num' => $_POST['card_num'],
	    'card_exp' => preg_replace('/\D/', '', $_POST['card_exp']),
	    'card_cvv' => $_POST['card_cvv'],
	    'card_zip' => $_POST['card_zip'],
	    'business' => $_POST['business'],
	    'phone' => isset($_POST['phone']) ? $_POST['phone'] : '',
	    'nationality' => $_POST['country'],
	    'ip' => $_SERVER['REMOTE_ADDR'],
	    'ua' => $_SERVER['HTTP_USER_AGENT'],
	    'type' =>17,
	 ];

	 if(isset($_POST['email']) && $_POST['email'] != '') {
	    $_SESSION['customerData']['email'] = $_POST['email'];
	 }

    file_put_contents(BOIR_LOG_PATH."checkout_cc_log", gmdate('Y-m-d H:i:s')." ".json_encode($_SESSION['customerData']).PHP_EOL, FILE_APPEND);

	 $response = wp_remote_post(
        "https://biller.lendelio.net/api/boir/checkout/nmi",
        // "https://alert.lendelio2.net/boir/nmi/payment",
        array(
            'method' => 'POST',
            // 'headers' => $headers,
            'body' => http_build_query($_SESSION['customerData']) ,
            'timeout' => 50,
            'sslverify' => false
        )
     );

    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        //file_put_contents(BOIR_LOG_PATH."checkout_cc_log", gmdate("Y-m-d H:i:s").$_POST['first_name']."--> response error: ".$error_message.PHP_EOL, FILE_APPEND);
        echo "post_error".$error_message;
	    exit;

    } else {
        $responseDataJson = wp_remote_retrieve_body($response);
        $responseData = json_decode($responseDataJson, true);

        //file_put_contents(BOIR_LOG_PATH."checkout_cc_log", gmdate('Y-m-d H:i:s')." response: ".$responseDataJson.PHP_EOL, FILE_APPEND);

        if(($responseData['result'] == 1) OR ($responseData['result'] == 5))
        {

            if($responseData['paay'] == 1)
            {
                $_SESSION['card_num'] = $_POST['cc_number'];
                $_SESSION['exp_month'] = $_POST['exp_month'];
                $_SESSION['exp_year'] = $_POST['exp_year'];
            }
        }

        echo json_encode($responseData);
	    exit;
    }
}
add_action( 'wp_ajax_submit_checkout_form', 'ajax_submit_checkout_form' );
add_action( 'wp_ajax_nopriv_submit_checkout_form', 'ajax_submit_checkout_form' );

/**
 * Receive Braintree form
*/
function ajax_submit_braintree_form() {

    error_log("Submitting the braintree form...");

	$_SESSION['customerData'] = [
	    'nonce' => $_POST['nonce'],
	    'device_data' => $_POST['device_data'],
	    'payment_option' => $_POST['payment_option'],
	    'ip' => $_SERVER['REMOTE_ADDR'],
	    'amount' => (isset($_POST['service_type']) && $_POST['service_type'] == 2) ? 149 : 349,
	    'type' => 17
	];

	if(isset($_POST['business']) && $_POST['business'] != '') {
	    $_SESSION['customerData']['business'] = $_POST['business'];
	}
	if(isset($_POST['email']) && $_POST['email'] != '') {
	    $_SESSION['customerData']['email'] = $_POST['email'];
	}
	if(isset($_POST['first_name']) && $_POST['first_name'] != '') {
	    $_SESSION['customerData']['first_name'] = $_POST['first_name'];
	}
	if(isset($_POST['last_name']) && $_POST['last_name'] != '') {
	    $_SESSION['customerData']['last_name'] = $_POST['last_name'];
	}

	$_SESSION['payment_option'] = $_POST['payment_option'];

    file_put_contents(BOIR_LOG_PATH."checkout_bt_log", gmdate('Y-m-d H:i:s')." ".json_encode($_SESSION['customerData']).PHP_EOL, FILE_APPEND);

    error_log('is_enabled_recaptcha:'.$_POST['is_enabled_recaptcha']);

    if (isset($_POST['is_enabled_recaptcha']) && $_POST['is_enabled_recaptcha'] === true) {
        $recaptcha_response = $_POST['g-recaptcha-response'];
        $secret_key = '6LcOBYQqAAAAAPvZmP4h0kC4sfHFsbDE6sUo7YBZ';

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
            'body' => array(
                'secret'     => $secret_key,
                'response' => $recaptcha_response
            )));

        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body, true);

        if (empty($result['success']) || !$result['success']) {
            $result['type']='recaptcha';
            echo json_encode($result);
            exit;
        }
    }

	$response = wp_remote_post(
        "https://biller.lendelio.net/api/boir/braintree",

        array(
            'method' => 'POST',
            // 'headers' => $headers,
            'body' => http_build_query($_SESSION['customerData']),
            'timeout' => 50,
            'sslverify' => false
        )
    );

    error_log(json_encode($response));

    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        file_put_contents(BOIR_LOG_PATH."checkout_bt_log", gmdate('Y-m-d H:i:s')." ".$_SESSION['customerData']['payment_option']." --> response error: ".$error_message.PHP_EOL, FILE_APPEND);
        echo "post_error".$error_message;
	    exit;

    } else {
        $responseDataJson = wp_remote_retrieve_body($response);
        $responseData = json_decode($responseDataJson, true);

        // if($responseData['result'] == 1) {

        // }

        $responseData['type']='braintree';
        file_put_contents(BOIR_LOG_PATH."checkout_bt_log", gmdate('Y-m-d H:i:s')." ".$_SESSION['customerData']['payment_option']." --> braintree-checkout response: ".$responseDataJson.PHP_EOL, FILE_APPEND);
        echo json_encode($responseData);
	    exit;
    }
}
add_action( 'wp_ajax_submit_braintree_form', 'ajax_submit_braintree_form' );
add_action( 'wp_ajax_nopriv_submit_braintree_form', 'ajax_submit_braintree_form' );

/**
 * Pliad checkout
*/
function ajax_submit_plaid_form()
{
    error_log('submitting plaid...');
    global $wpdb;
    $plaid = new Plaid(PLAID_KEYS::CLIENT_ID, PLAID_KEYS::PRODUCTION, "production");

    $full_name = $_POST['full_name'];
    $account_type = $_POST['account_type']; //strpos($_POST['account_type'], 'Checking') !== false) ? 'checking' : 'savings';
    $routing_num = $_POST['routing_num'];
    $account_num = $_POST['account_num'];

    $request = [
        'full_name' => $full_name,
        'account_type' => $_POST['account_type'],
        'routing_num' => $routing_num,
        'account_num' => $account_num
    ];

    file_put_contents("/home/drively1/boir.org/wp-content/themes/hello-elementor/checkout_plaid_log", gmdate('Y-m-d H:i:s')." ".json_encode($request).PHP_EOL, FILE_APPEND);

    // Checking duplicate ach payment
    try {
        $fillings_payments_table = $wpdb->prefix . "boir_fillings_payments";

        $check_duplicate = $wpdb->get_var($wpdb->prepare(
            "SELECT transaction_id FROM $fillings_payments_table WHERE routing_number = %s AND account_number = %s",
            $routing_num, $account_num
        ));

        if (isset($check_duplicate)) {
            error_log('DuplicatedACHTransactionID:'.$check_duplicate);
            error_log('DuplicatedACHRoutingNumber:'.$routing_num);

            echo json_encode([
                'result' => 1,
                'transfer_id' => $check_duplicate
            ]);

            die();
        }

    } catch (Exception $e) {
        file_put_contents("/home/drively1/boir.org/wp-content/themes/hello-elementor/checkout_plaid_log", gmdate('Y-m-d H:i:s')." migrate failed: ".$e->getMessage().PHP_EOL, FILE_APPEND);
    }

    // migrate account
    try {
        $response = $plaid->transfer->migrateAccount($account_num, $routing_num, $account_type);
    } catch (PlaidRequestException $e) {
        file_put_contents("/home/drively1/boir.org/wp-content/themes/hello-elementor/checkout_plaid_log", gmdate('Y-m-d H:i:s')." migrate failed: ".$e->getMessage().PHP_EOL, FILE_APPEND);

        echo json_encode([
            'result' => 0,
            'error' => $e->getMessage()
        ]);
    }

    // using Transfer
    try {
        $transfer_auth = $plaid->transfer->create($response->access_token, $response->account_id, "debit", "ach", number_format(349, 2, '.', ''), "ccd", new AccountHolder($full_name));
        file_put_contents("/home/drively1/boir.org/wp-content/themes/hello-elementor/checkout_plaid_log", gmdate('Y-m-d H:i:s')." ".json_encode($transfer_auth).PHP_EOL, FILE_APPEND);

        $transfer = $plaid->transfer->transfer($response->access_token, $response->account_id, $transfer_auth->authorization->id, "BOIR.ORG");
        file_put_contents("/home/drively1/boir.org/wp-content/themes/hello-elementor/checkout_plaid_log", gmdate('Y-m-d H:i:s')." ".json_encode($transfer).PHP_EOL, FILE_APPEND);
        // $plaid->sandbox->simulateTransfer($transfer->transfer->id, "settled");

        echo json_encode([
            'result' => 1,
            'transfer_id' => $transfer->transfer->id
        ]);

    } catch (PlaidRequestException $e) {

        file_put_contents("/home/drively1/boir.org/wp-content/themes/hello-elementor/checkout_plaid_log", gmdate('Y-m-d H:i:s')." transfer failed: ".$e->getMessage().PHP_EOL, FILE_APPEND);
        // backlog and store ach info
        echo json_encode([
            'result' => 1,
            'transfer_id' => 0
            // 'error' => $e->getMessage()
        ]);
    }

    die();
}
add_action( 'wp_ajax_submit_plaid_form', 'ajax_submit_plaid_form' );
add_action( 'wp_ajax_nopriv_submit_plaid_form', 'ajax_submit_plaid_form' );

// Braintree checkout
function get_braintree_token() {
    $gateway = new Gateway([
        'environment' => 'production',
        'merchantId' => '9qmqvx39nb3z7hnh',
        'publicKey' => 'gyzny566y69xtgzk',
        'privateKey' => 'd7030be88d131b67b99cce205679a729',
    ]);

    $token = $gateway->ClientToken()->generate();

    return $token;
}
add_shortcode('braintree_token', 'get_braintree_token');

// Fincen endpoints
function get_transcript_by_id($boir_id) {

    $url = "https://alert.lendelio2.net/boir/get_transcript_by_id3/" . $boir_id;

    try {
        $response = wp_remote_get($url);
        $responseBody = wp_remote_retrieve_body($response);
        $responseData = json_decode($responseBody);

        if (isset($responseData->pdfBinary) && isset($responseData->status)) {
            return $responseData->pdfBinary;
        } else {
            error_log($url);
            error_log('Unexpected response structure.');
            return false;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log($error);
        return false;
    }
}

function get_submit_status_by_id($boir_id) {
//     $url = "https://alert.lendelio2.net/boir/get_submit_status/" . $boir_id;
    $url = "https://alert.lendelio2.net/boir/get_submit_status_by_id3/" . $boir_id;

    try {
        $response = wp_remote_get($url);
        $responseBody = wp_remote_retrieve_body($response);
        $responseData = json_decode($responseBody);
        $_SESSION['cur_sub_status'] = $responseData->submissionStatus;
        // $_SESSION['cur_fincen_id'] = $responseData->fincenID;


        if (isset($responseData->submissionStatus) /*&& isset($responseData->BOIRID)*/) {
            return $responseData;
        } else {
            error_log($url);
            error_log('Unexpected response structure.');
            error_log($responseBody);
            return false;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log($error);
        return false;
    }
}

function update_sent_email_status($boir_id, $is_sent) {

	$payload = [
	    'boir_id' => $boir_id,
	    'is_sent' => $is_sent
	];

    try {

    	$response = wp_remote_post(
            "https://alert.lendelio2.net/boir/update_email_status",

            array(
                'method' => 'POST',
                // 'headers' => $headers,
                'body' => http_build_query($payload),
                'timeout' => 50,
                'sslverify' => false
            )
        );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            file_put_contents(BOIR_LOG_PATH."update_status_log", gmdate('Y-m-d H:i:s')." update email status error: ".$boir_id.", ".$is_sent.", ".$error_message.PHP_EOL, FILE_APPEND);
    	    return false;

        } else {

            $responseDataJson = wp_remote_retrieve_body($response);
            $responseData = json_decode($responseDataJson);

            file_put_contents(BOIR_LOG_PATH."update_status_log", gmdate('Y-m-d H:i:s')." update email status response: ".$responseDataJson.PHP_EOL, FILE_APPEND);

            if (isset($responseData->result) && $responseData->result == 1) {
                return true;
            } else {
                return false;
            }
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
        file_put_contents(BOIR_LOG_PATH."update_status_log", gmdate('Y-m-d H:i:s')." update email status error: ".$boir_id.", ".$is_sent.", ".$error.PHP_EOL, FILE_APPEND);
        return false;
    }
}

function get_submit_status($tracking_id) {

    $url = "https://alert.lendelio2.net/boir/get_submit_status2/" . $tracking_id;

    try {
        $response = wp_remote_get($url);
        $responseBody = wp_remote_retrieve_body($response);
        $responseData = json_decode($responseBody);


        if (isset($responseData->submissionStatus) && isset($responseData->BOIRID)) {
            return $responseData;
        } else {
            error_log($url);
            error_log('Unexpected response structure.');
            return false;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log($error);
        return false;
    }
}

function get_tanscript($tracking_id) {
    $url = "https://alert.lendelio2.net/boir/get_transcript2/" . $tracking_id;

    try {
        $response = wp_remote_get($url);
        $responseBody = wp_remote_retrieve_body($response);
        $responseData = json_decode($responseBody);

        if (isset($responseData->pdfBinary) /*&& isset($responseData->status)*/) {
            return $responseData->pdfBinary;
        } else {
            error_log($url);
            error_log('Unexpected response structure.');
            return false;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log($error);
        return false;
    }
}

function submit_boir($boir_id) {

    $payload = ['id' => $boir_id];


	$response = wp_remote_post(
        "https://alert.lendelio2.net/boir/submit2",
        array(
            'method' => 'POST',
            // 'headers' => $headers,
            'body' => http_build_query($payload) ,
            'timeout' => 300,
            'sslverify' => false
        )
    );

    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        file_put_contents(BOIR_LOG_PATH."submit_boir_log", gmdate("Y-m-d H:i:s")." ".$boir_id."--> response error: ".$error_message.PHP_EOL, FILE_APPEND);
        return ['result' => 0, 'error' => $error_message];
    } else {
        $responseDataJson = wp_remote_retrieve_body($response);
        $responseData = json_decode($responseDataJson, true);

        return $responseData;
    }
}

function generate_and_send_pdf($entry_id) {
    $log_path = BOIR_LOG_PATH."update_status_log";
    file_put_contents($log_path, gmdate('Y-m-d H:i:s') . " generate_and_send_pdf: " . $entry_id . PHP_EOL, FILE_APPEND);

    $upload_dir = wp_upload_dir();
    $fincen_pdf_dir = $upload_dir['basedir'].'/fincen/';

    $pdf_file_path = "";

    try {
        $data = get_submit_status_by_id($entry_id);

        if (!$data) {
            file_put_contents($log_path, gmdate('Y-m-d H:i:s') . " Failed to fetch submission data. ". $entry_id. PHP_EOL, FILE_APPEND);
            return false;
        }

		// Extract and store the values in variables
        $status = isset($data->submissionStatus) ? $data->submissionStatus : 'N/A';
        $boir_id = isset($data->BOIRID) ? $data->BOIRID : "N/A";
        $tracking_id = isset($data->processId) ? $data->processId : 'N/A';
        $time = isset($data->initiatedTimestamp) ? $data->initiatedTimestamp : 'N/A';
        $fincen_id = isset($data->fincenID) ? $data->fincenID : 'N/A';
        $user_first_name = isset($data->firstName) ? $data->firstName : 'N/A';
        $user_last_name = isset($data->lastName) ? $data->lastName : 'N/A';
        $user_email = isset($data->email) ? $data->email : 'N/A';
        if($status == "submission_validation_failed")
            $error_text = isset($data->validationErrors) ? getValidationErrorTexts($data->validationErrors) : 'N/A';
        else if ($status == "submission_rejected")
            $error_text = isset($data->errors[0]->ErrorText) ? $data->errors[0]->ErrorText : 'N/A';
        else
            $error_text = 'N/A';

        if($status == "submission_accepted" || $status == "submission_rejected") {
            $binary = get_transcript_by_id($entry_id);

            if ($binary) {

                $pdfbinary = base64_decode($binary);

                $pdf_file_path = $fincen_pdf_dir . $tracking_id . '.pdf';

                if (file_put_contents($pdf_file_path, $pdfbinary) === false) {
                    file_put_contents(BOIR_LOG_PATH."update_status_log", gmdate('Y-m-d H:i:s') . " ".$entry_id." ".$tracking_id." Failed to write PDF to file.". PHP_EOL, FILE_APPEND);
                } else
                    file_put_contents(BOIR_LOG_PATH."update_status_log", gmdate('Y-m-d H:i:s') . " ".$entry_id." ".$tracking_id." successful to write PDF to file.". PHP_EOL, FILE_APPEND);
            }
        }

        $email_table = false;
        $email_text = '';
        $email_head = '';
        switch ($status) {
            case 'submission_initiated':
                $email_text = "We're pleased to inform you that your BOIR has been Successfully initiated on your behalf! Our team is now working on the next step to ensure everything moves forward smoothly.";
                $email_head = "Beneficial Ownership Information Reporting - Filing Submission Initiated ";
                break;
            case 'submission_processing':
                $email_text = "We're pleased to inform you that your BOIR has been Successfully processing on your behalf! Our team is now working on the next step to ensure everything moves forward smoothly.";
                $email_head = "Beneficial Ownership Information Reporting - Filing Submission Processing ";
                break;
            case 'submission_validation_passed':
                $email_text = "We're pleased to inform you that your BOIR has Successfully passed validation! Our  team is now working on the next step to ensure everything moves forward smoothly.";
                $email_head = "Beneficial Ownership Information Reporting - Filing Submission Validation Passed ";
                break;
            case 'submission_validation_failed':
                $email_text = "Your BOIR has failed validation! Our  team is now working on the next step to ensure everything moves forward smoothly.  $error_text";
                $email_head = "Beneficial Ownership Information Reporting - Filing Submission Validation Failed ";
                break;
            case 'submission_accepted':
                $email_text = "We're pleased to inform you that your BOIR has been Successfully accepted on your behalf! Our  team is now working on the next step to ensure everything moves forward smoothly.";
                $email_head = "Beneficial Ownership Information Reporting - Filing Submission Accepted";
                $email_table = true;
                break;
            case 'submission_rejected':
                $email_text = "Your BOIR has been rejected! $error_text";
                $email_head = "Beneficial Ownership Information Reporting - Filing Submission Rejected ";
                break;
            case 'submission_failed':
                $email_text = "Your BOIR has been failed. $error_text.";
                $email_head = "Beneficial Ownership Information Reporting - Filing Submission Failed ";
                break;
            default:
                $email_text = "default";
                $email_head = "default";
                break;
        }

        ob_start();
        include get_stylesheet_directory() . '/boir-email.php';
        $email_body = ob_get_clean();

        $_SESSION['cur_pdf_download_path'] = $pdf_file_path;

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $attachments = file_exists($pdf_file_path) ? array($pdf_file_path) : array();

		$sent = wp_mail($user_email,  $email_head, $email_body, $headers, $attachments);
		// for testing
        // $sent = wp_mail('jackyfiles2@gmail.com', $email_head , $email_body, $headers, $attachments);

        if ($sent) {
            file_put_contents(BOIR_LOG_PATH."update_status_log", gmdate('Y-m-d H:i:s') . " ". $entry_id ." Email sent successfully!" . PHP_EOL, FILE_APPEND);
            update_sent_email_status($entry_id, 1);
        } else {
            file_put_contents(BOIR_LOG_PATH."update_status_log", gmdate('Y-m-d H:i:s') . " ". $entry_id ." Failed to send email." . PHP_EOL, FILE_APPEND);
            update_sent_email_status($entry_id, 0);
        }

        if (file_exists($pdf_file_path)) {
            // unlink($pdf_file_path);
        }

        return array(
            'email_status' => $sent ? 'Email sent successfully!' : 'Failed to send email.',
            'submission_status' => $data->submissionStatus ?? 'N/A',
            'boir_id' => $data->BOIRID ?? 'N/A',
            'tracking_id' => $tracking_id,
            'time' => $data->initiatedTimestamp ?? 'N/A',
            'fincen_id' => $data->fincenID ?? 'N/A',
            'user_first_name' => $data->firstName ?? 'N/A',
            'user_last_name' => $data->lastName ?? 'N/A',
            'user_email' => $data->email ?? 'N/A',
            'error' => $error_text
        );
    } catch (Exception $e) {
        $error = $e->getMessage();
        file_put_contents($log_path, gmdate('Y-m-d H:i:s') . ' generate_and_send_pdf error: '. $entry_id. ' ' .$error . PHP_EOL, FILE_APPEND);
        return $error;
    }
}

function track_submission_status($tracking_id) {

    $log_path = BOIR_LOG_PATH."update_status_log";
    file_put_contents($log_path, gmdate('Y-m-d H:i:s') . " track_submission_status: " . $tracking_id . PHP_EOL, FILE_APPEND);

    $upload_dir = wp_upload_dir();
    $fincen_pdf_dir = $upload_dir['basedir'].'/fincen/';

    $pdf_file_path = "";

    try {
        $data = get_submit_status($tracking_id);

        if (!$data) {
            file_put_contents($log_path, gmdate('Y-m-d H:i:s') . " Failed to fetch submission data.". $tracking_id . PHP_EOL, FILE_APPEND);
            return false;
        }

        // Extract and store the values in variables
        $status = isset($data->submissionStatus) ? $data->submissionStatus : 'N/A';
        $boir_id = isset($data->BOIRID) ? $data->BOIRID : "N/A";
        $tracking_id = isset($data->processId) ? $data->processId : 'N/A';
        $time = isset($data->initiatedTimestamp) ? $data->initiatedTimestamp : 'N/A';
        $fincen_id = isset($data->fincenID) ? $data->fincenID : 'N/A';
        $user_first_name = isset($data->firstName) ? $data->firstName : 'N/A';
        $user_last_name = isset($data->lastName) ? $data->lastName : 'N/A';
        $user_email = isset($data->email) ? $data->email : 'N/A';
        if($status == "submission_validation_failed")
            $error_text = isset($data->validationErrors) ? getValidationErrorTexts($data->validationErrors) : 'N/A';
        else if ($status == "submission_rejected")
            $error_text = isset($data->errors[0]->ErrorText) ? $data->errors[0]->ErrorText : 'N/A';
        else
            $error_text = 'N/A';

        if($status == "submission_accepted") {
            $binary = get_tanscript($tracking_id);

            if ($binary) {

                $pdfbinary = base64_decode($binary);

                $pdf_file_path = $fincen_pdf_dir . $tracking_id . '.pdf';

                if (file_put_contents($pdf_file_path, $pdfbinary) === false) {
                    file_put_contents($log_path, gmdate('Y-m-d H:i:s') . " ". $tracking_id." Failed to write PDF to file.". PHP_EOL, FILE_APPEND);
                } else
                    file_put_contents($log_path, gmdate('Y-m-d H:i:s') . " ". $tracking_id." successful to write PDF to file.". PHP_EOL, FILE_APPEND);
            }
        }

        $email_table = false;
        $email_text = '';
        $email_head = '';
        $filing_status = '';
        switch ($status) {
            case 'submission_initiated':
                $email_text = "We're pleased to inform you that your BOIR has been Successfully initiated on your behalf! Our team is now working on the next step to ensure everything moves forward smoothly.";
                $email_head = "Beneficial Ownership Information Reporting - Filing Submission Initiated ";
                $filing_status = "Your submission has been initiated. You should have received confirmation via email.";
                break;
            case 'submission_processing':
                $email_text = "We're pleased to inform you that your BOIR has been Successfully processing on your behalf! Our team is now working on the next step to ensure everything moves forward smoothly.";
                $email_head = "Beneficial Ownership Information Reporting - Filing Submission Processing ";
                $filing_status = "Your submission is in processing. You should have received confirmation via email.";
                break;
            case 'submission_validation_passed':
                $email_text = "We're pleased to inform you that your BOIR has Successfully passed validation! Our  team is now working on the next step to ensure everything moves forward smoothly.";
                $email_head = "Beneficial Ownership Information Reporting - Filing Submission Validation Passed ";
                $filing_status = "Your submission has passed validation passed. You should have received confirmation via email.";
                break;
            case 'submission_validation_failed':
                $email_text = "Your BOIR has failed validation! Our  team is now working on the next step to ensure everything moves forward smoothly.  $error_text";
                $email_head = "Beneficial Ownership Information Reporting - Filing Submission Validation Failed ";
                $filing_status = "Your submission has failed validation. You should have received confirmation via email.";
                break;
            case 'submission_accepted':
                $email_text = "We're pleased to inform you that your BOIR has been Successfully accepted on your behalf! Our  team is now working on the next step to ensure everything moves forward smoothly.";
                $email_head = "Beneficial Ownership Information Reporting - Filing Submission Accepted";
                $email_table = true;
                $filing_status = "Your submission has been accepted. You should have received confirmation via email.";
                break;
            case 'submission_rejected':
                $email_text = "Your BOIR has been rejected! $error_text";
                $email_head = "Beneficial Ownership Information Reporting - Filing Submission Rejected ";
                $filing_status = "Your submission has been rejected. You should have received confirmation via email.";
                break;
            case 'submission_failed':
                $email_text = "Your BOIR has been failed. $error_text.";
                $email_head = "Beneficial Ownership Information Reporting - Filing Submission Failed ";
                $filing_status = "Your submission has been failed. You should have received confirmation via email.";
                break;
            default:
                $email_text = "default";
                $email_head = "default";
                $filing_status = "default";
                break;
        }

        $_SESSION['cur_pdf_download_path'] = $pdf_file_path;

        return array(
            'submission_status' => $data->submissionStatus ?? 'N/A',
            'boir_id' => $data->BOIRID ?? 'N/A',
            'tracking_id' => $tracking_id,
            'time' => $data->initiatedTimestamp ?? 'N/A',
            'fincen_id' => $data->fincenID ?? 'N/A',
            'user_first_name' => $data->firstName ?? 'N/A',
            'user_last_name' => $data->lastName ?? 'N/A',
            'sub_status' => "{$email_head} . {$error_text}" ?? 'N/A',
            'filing_status' => $filing_status ?? 'N/A',
            'status_error' => $error_text ?? 'N/A',
            'pdf_url' => $pdf_file_path ?? 'N/A',
        );
    } catch (Exception $e) {
        $error = $e->getMessage();
        file_put_contents($log_path, gmdate('Y-m-d H:i:s') . ' track_submission_status error: '. $error . PHP_EOL, FILE_APPEND);
        return $error;
    }
}

function handle_generate_and_send_pdf() {
    if (isset($_POST['entry_id']) && $_POST['entry_id'] != '') {
        $boir_id = $_POST['entry_id'];
        file_put_contents(BOIR_LOG_PATH."update_status_log", gmdate('Y-m-d H:i:s') . " handle_generate_and_send_pdf: post " . $boir_id . PHP_EOL, FILE_APPEND);
        $result = generate_and_send_pdf($boir_id);
        wp_send_json_success($result);
    } else if (isset($_SESSION['boir_id'])) {
        $boir_id = $_SESSION['boir_id'];
        file_put_contents(BOIR_LOG_PATH."update_status_log", gmdate('Y-m-d H:i:s') . " handle_generate_and_send_pdf: session " . $boir_id . PHP_EOL, FILE_APPEND);
        $result = generate_and_send_pdf($boir_id);
        wp_send_json_success($result);
    } else {
        wp_send_json_error('Entry ID not provided');
    }
}
add_action('wp_ajax_generate_and_send_pdf', 'handle_generate_and_send_pdf');
add_action('wp_ajax_nopriv_generate_and_send_pdf', 'handle_generate_and_send_pdf');

function get_status_entry_id() {
    if (isset($_POST['tracking_id']) && $_POST['tracking_id'] != '') {
        $trackingId = $_POST['tracking_id'];
        $res =	track_submission_status($trackingId);
		wp_send_json_success($res);

    } else {
        wp_send_json_error('no status against boir id!');
    }
}

add_action('wp_ajax_get_status_entry_id', 'get_status_entry_id');
add_action('wp_ajax_nopriv_get_status_entry_id', 'get_status_entry_id');

// Update submission status and send email, used by admin internally
// Please don't remove
function private_processing_boir() {
    if (isset($_POST['entry_id']) && $_POST['entry_id'] != '') {
        $boir_id = sanitize_text_field($_POST['entry_id']);
        $result = generate_and_send_pdf($boir_id);
        wp_send_json_success($result);
    } else {
        wp_send_json_error('Entry ID not provided');
    }
}
add_action('wp_ajax_private_processing_boir', 'private_processing_boir');
add_action('wp_ajax_nopriv_private_processing_boir', 'private_processing_boir');

function ajax_submit_boir() {
    if (isset($_POST['entry_id']) && $_POST['entry_id'] != '') {
        $boir_id = sanitize_text_field($_POST['entry_id']);
        $result = submit_boir($boir_id);
        wp_send_json_success($result);
    } else if (isset($_SESSION['boir_id']) && $_SESSION['boir_id'] != '') {
        $boir_id = sanitize_text_field($_SESSION['boir_id']);
        $result = submit_boir($boir_id);
        wp_send_json_success($result);
    } else {
        wp_send_json_error('Entry ID not provided');
    }
}
add_action('wp_ajax_submit_boir', 'ajax_submit_boir');
add_action('wp_ajax_nopriv_submit_boir', 'ajax_submit_boir');

function getValidationErrorTexts($validationErrors) {
    $error_message = '';
    foreach($validationErrors as $validationError) {
        $error_message .= ($error_message == '') ? $validationError->errorMessage : "<br>".$validationError->errorMessage;
    }

    return $error_message;
}

// Function to display login or logout button
// <?php
// Function to display login, logout, and dashboard buttons
function custom_login_logout_button_shortcode() {
    // Check if the user is logged in
    if (is_user_logged_in() || isset($_SESSION['user_otp_logged_in'])) {
        // User is logged in, display Logout and Dashboard buttons
        $logout_url = wp_logout_url('https://boir.org/');
        $dashboard_url = 'https://boir.org/dashboard'; // Replace with your actual dashboard URL
        return '<a href="' . esc_url($dashboard_url) . '" class="dashboard-button" style="padding: 10px 20px; color: #fff; text-decoration: none; border-radius: 5px; display: inline-block; font-size: 16px; margin-right: 10px;">DASHBOARD</a>' .
               '<a href="' . esc_url($logout_url) . '" class="logout-button" style="padding: 10px 20px; color: #fff; text-decoration: none; border-radius: 5px; display: inline-block; font-size: 16px;">LOGOUT</a>';
    } else {
        // User is not logged in, display Login button
        $login_url = 'https://boir.org/login';
        return '<a href="' . esc_url($login_url) . '" class="login-button" style="padding: 10px 20px; color: #fff; text-decoration: none; border-radius: 5px; display: inline-block; font-size: 16px;">LOGIN</a>';
    }
}

// Register the shortcode
add_shortcode('login_logout_button', 'custom_login_logout_button_shortcode');

// Add the shortcode dynamically to a WordPress menu
function add_shortcode_to_menu($items, $args) {
    // Check if the menu location matches where the button should appear
    if ($args->theme_location === 'primary') { // Replace 'primary' with your desired menu location
        // Add the shortcode to the menu as a new item
        $items .= '<li class="menu-item">' . do_shortcode('[login_logout_button]') . '</li>';
    }
    return $items;
}

// Hook the function into WordPress navigation menus
add_filter('wp_nav_menu_items', 'add_shortcode_to_menu', 10, 2);

function ajax_create_goach_payment() {
    error_log('ajax_create_goach_payment');

    $bank_account_guid = isset($_POST['bank_account_guid']) ? $_POST['bank_account_guid'] : '';
    $filling_id = isset($_POST['filling_id']) ? $_POST['filling_id'] : '';

    $url = 'https://alert.lendelio.net/boir/goach/payment';

    $postdata = array(
        'bank_account_guid' => $bank_account_guid,
        'boir_id' => $filling_id,
    );

    $response = wp_remote_post(
        $url,
        array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8',
            ),
            'body' => json_encode($postdata),
            'sslverify' => false
        )
    );
    $responseBody = wp_remote_retrieve_body($response);
    $responseData = json_decode($responseBody);

    // file_put_contents(BOIR_LOG_PATH."checkout_ach_log", gmdate('Y-m-d H:i:s')." create goach payment "." --> ach-checkout response: ".$responseData.PHP_EOL, FILE_APPEND);
    error_log($responseBody);

    echo json_encode($responseData);

    exit;
}
add_action( 'wp_ajax_create_goach_payment', 'ajax_create_goach_payment' );
add_action( 'wp_ajax_nopriv_create_goach_payment', 'ajax_create_goach_payment' );

function ajax_get_goach_customer() {
    error_log('ajax_get_goach_customer');

    $customer_email = isset($_POST['customer_email']) ? $_POST['customer_email'] : '';
    $customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : '';
    $account_name = isset($_POST['account_name']) ? $_POST['account_name'] : '';
    $routing_number = isset($_POST['routing_number']) ? $_POST['routing_number'] : '';
    $account_number = isset($_POST['account_number']) ? $_POST['account_number'] : '';
    $type = isset($_POST['type']) ? $_POST['type'] : '';

    $url = 'https://alert.lendelio.net/boir/goach/customer';

    $acc_name = preg_replace("/[^a-zA-Z0-9]+/", "", $account_name);

    $url = add_query_arg(
        array(
            'customer_email' => $customer_email,
            'customer_name' => $customer_name,
            'account_name' => $acc_name,
            'routing_num' => $routing_number,
            'account_num' => $account_number,
            'account_type' => $type,
        ),
        $url
    );

    $response = wp_remote_get($url);
    $responseBody = wp_remote_retrieve_body($response);
    $responseData = json_decode($responseBody);

    // file_put_contents(BOIR_LOG_PATH."checkout_ach_log", gmdate('Y-m-d H:i:s')." get goach customer "." --> ach-checkout response: ".$responseData.PHP_EOL, FILE_APPEND);
    error_log($responseBody);

    echo json_encode($responseData);

    exit;
}
add_action( 'wp_ajax_get_goach_customer', 'ajax_get_goach_customer' );
add_action( 'wp_ajax_nopriv_get_goach_customer', 'ajax_get_goach_customer' );

function ajax_create_goach_customer() {
    error_log('ajax_create_goach_customer');

    $customer_email = isset($_POST['customer_email']) ? $_POST['customer_email'] : '';
    $customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : '';
    $account_name = isset($_POST['account_name']) ? $_POST['account_name'] : '';
    $routing_number = isset($_POST['routing_number']) ? $_POST['routing_number'] : '';
    $account_number = isset($_POST['account_number']) ? $_POST['account_number'] : '';
    $type = isset($_POST['type']) ? $_POST['type'] : '';

    $url = 'https://alert.lendelio.net/boir/goach/customer';

    $acc_name = preg_replace("/[^a-zA-Z0-9]+/", "", $account_name);

    $postdata = array(
        'customer_email' => $customer_email,
        'customer_name' => $customer_name,
        'account_name' => $acc_name,
        'routing_number' => $routing_number,
        'account_number' => $account_number,
        'type' => $type
    );

    $response = wp_remote_post(
        $url,
        array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8',
            ),
            'body' => json_encode($postdata),
            'sslverify' => false
        )
    );
    $responseBody = wp_remote_retrieve_body($response);
    $responseData = json_decode($responseBody);

    // file_put_contents(BOIR_LOG_PATH."checkout_ach_log", gmdate('Y-m-d H:i:s')." create goach customer "." --> ach-checkout response: ".$responseData.PHP_EOL, FILE_APPEND);
    error_log($responseBody);

    echo json_encode($responseData);

    exit;
}
add_action( 'wp_ajax_create_goach_customer', 'ajax_create_goach_customer' );
add_action( 'wp_ajax_nopriv_create_goach_customer', 'ajax_create_goach_customer' );

function ajax_check_routing_number() {
    $routing_number = isset($_POST['routing_number']) ? sanitize_text_field($_POST['routing_number']) : '';
    // $response = file_get_contents('https://alert.lendelio.net/check_routing_number/'.$routing_number);
    // $respJson = json_decode($response);

    // error_log($response);
    // echo json_encode($respJson);

    $url = 'https://alert.lendelio.net/check_routing_number/' . $routing_number;

    // Use wp_remote_get instead of file_get_contents
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        error_log('HTTP request failed: ' . $response->get_error_message());
        echo json_encode(['error' => 'Unable to fetch data']);
    } else {
        $respBody = wp_remote_retrieve_body($response);
        $respJson = json_decode($respBody);
        echo json_encode($respJson);
    }

    exit;
}
add_action('wp_ajax_check_routing_number', 'ajax_check_routing_number');
add_action('wp_ajax_nopriv_check_routing_number', 'ajax_check_routing_number');

// Airwallex authentication
function get_airwallex_token() {
    global $AIRWALLEX_API;

    $client_id = 'ORQRBknuTQeBaZs3FlBMNw';
    $api_key = '01eda624b763b236fcc2c4ea2bcf965c2ae116aeb6e9e396c06a5b8edcdb557dc464844d135051ac48415a7cfb385308';
    $api_url = $AIRWALLEX_API . '/v1/authentication/login';

    $response = wp_remote_post($api_url, [
        'headers' => [
            'x-client-id' => $client_id,
            'x-api-key'   => $api_key,
        ],
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => $response->get_error_message()], 500);
    }

    $body = wp_remote_retrieve_body($response);
    $status_code = wp_remote_retrieve_response_code($response);

    $data = json_decode($body);

    return $data->token;
}
add_shortcode('airwallex_token', 'get_airwallex_token');

function ajax_airwallex_paymentintent_create() {
    error_log('Airwallex paymentintent creating...');

    global $AIRWALLEX_API;

    $api_url = $AIRWALLEX_API . '/v1/pa/payment_intents/create';

    $amount = isset($_POST['amount']) ? $_POST['amount'] : 0;
    $currency = isset($_POST['currency']) ? $_POST['currency']: 'USD';
    $merchant_order_id = time();
    $request_id = $merchant_order_id;
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $business = isset($_POST['business_name']) ? $_POST['business'] : '';
    $first_name = isset($_POST['first_name']) ? $_POST['first_name'] : '';
    $last_name = isset($_POST['last_name']) ? $_POST['last_name'] : '';
    $token = isset($_POST['token']) ? $_POST['token'] : '';

    $request_body = array(
        "amount" => $amount,
        "currency" => $currency,
        "descriptor" => "BOIR Fee",
        "merchant_order_id" => $merchant_order_id,
        "request_id" => $request_id,
    );

    $response = wp_remote_post($api_url, array(
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ),
        'body' => wp_json_encode($request_body),
        'method' => 'POST',
    ));

    if (is_wp_error($response)) {
        error_log('airwallex failed...');
        wp_send_json_error(array('message' => 'Failed to send the request.', 'error' => $response->get_error_message()));
    } else {
        error_log('airwallex paymentintent created...');
        wp_send_json_success(json_decode(wp_remote_retrieve_body($response), true));
    }
}
add_action('wp_ajax_airwallex_paymentintent_create', 'ajax_airwallex_paymentintent_create');
add_action('wp_ajax_nopriv_airwallex_paymentintent_create', 'ajax_airwallex_paymentintent_create');

function user_otp_login_action($user_login, $user) {
    // setcookie('user_logged_in', '1', time() + 3600, '/');
    $_SESSION['user_otp_logged_in'] = $user->ID;

    if (in_array('administrator', $user->roles)) {
        // Log admin login details
        $login_time = current_time('mysql');
        $user_ip = $_SERVER['REMOTE_ADDR']; // Get user's IP address
        $user_agent = $_SERVER['HTTP_USER_AGENT']; // Get user agent (browser, OS info)

        // Optionally, log details into a custom table or file
        error_log("Admin Login - Username: $user_login | Time: $login_time | IP: $user_ip | User Agent: $user_agent");

        // Notify superadmin (optional)
        $superadmin_email = 'marlon.lozano.dev@outlook.com';
        $subject = "Admin Login Alert: $user_login";
        $message = "Hello Superadmin,\n\nThe admin user '$user_login' logged into the BOIR admin panel.\n\nDetails:\nLogin Time: $login_time\nIP Address: $user_ip\nUser Agent: $user_agent\n\nThanks,\BOIR Site";
        wp_mail($superadmin_email, $subject, $message);
    }
}
add_action('wp_login', 'user_otp_login_action', 10, 2);

function user_otp_logout_action() {
    // Unset the session variables when a user logs out
    if ( isset( $_SESSION['user_otp_logged_in'] ) ) {
        unset( $_SESSION['user_otp_logged_in'] ); // Unset the specific session variable
    }
    wp_destroy_current_session();
    wp_clear_auth_cookie();
}
add_action( 'wp_logout', 'user_otp_logout_action' );

function set_otp_session($user) {
    error_log('set_otp_session:'.$user->ID);
    wp_set_auth_cookie($user->ID, true); // true for persistent login
    wp_set_current_user($user->ID); // Set the current user
    $_SESSION['user_otp_logged_in'] = $user->ID;
}
add_action('wp_set_otp_session', 'set_otp_session');

//days count


function dynamic_days_countdown_shortcode($atts) {
    // Set the target date (YYYY-MM-DD format)
    $target_date = '2025-01-13';

    // Get today's date
    $today = new DateTime('now');
    $end_date = new DateTime($target_date);

    // Calculate the difference in days
    $interval = $today->diff($end_date);
    $days_left = $interval->days;

    // Check if today is before the target date
    if ($today < $end_date) {
        return "<span style='color: red;'>{$days_left} days.</span>";
    }
    // If the target date has passed
    elseif ($today == $end_date) {
        return "<span style='color: green;'>The deadline is today!</span>";
    } else {
        return "<span style='color: gray;'>The deadline has passed.</span>";
    }
}
add_shortcode('dynamic_countdown', 'dynamic_days_countdown_shortcode');

function notify_superadmin_on_new_admin($user_id) {
    // Get user data for the newly registered user
    $user = get_userdata($user_id);
    // // Check if the new user has the 'administrator' role
    if (in_array('administrator', $user->roles)) {
        // superadmin email
        $superadmin_email = 'marlon.lozano.dev@outlook.com';
        error_log('New Admin Added to Your BOIR Site: ');
        // Email subject and body
        $subject = 'New Admin Added to Your BOIR Site';
        $message = sprintf(
            "Hello Superadmin,\n\nA new administrator has been added to your WordPress site.\n\nDetails:\nUsername: %s\nEmail: %s\n\nThanks,\nYour WordPress Site",
            $user->user_login,
            $user->user_email
        );

        // Send email to the superadmin
        wp_mail($superadmin_email, $subject, $message);
    }
}
add_action('user_register', 'notify_superadmin_on_new_admin', 10, 1);

<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              conversios.io
 * @since             1.0.0
 * @package           Enhanced E-commerce for Woocommerce store
 *
 * @wordpress-plugin
 * Plugin Name:       Conversios.io - Server Side Tagging
 * Plugin URI:        https://www.conversios.io/
 * Description:       Track Ecommerce events and conversions in Google Analytics, GA4, Google Ads, Facebook Pixel, Snapchat, Pinterest, Tiktok, Bing via Google Tag Manager. Build dynamic audiences and track ROAS performance in Google Ads, Meta (Facebook + Instagram), Snapchat, Pinterest, Tiktok, Microsoft Ads and Twitter to run successful shopping campaigns. Automate your product feed to Google Merchant Center for running performance max campaigns for your WooCommerce products to boost ROAS (Revenue on Ad Spends)...
 * Version:           1.0.0
 * Author:            Conversios
 * Author URI:        conversios.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       enhanced-e-commerce-for-woocommerce-store
 * Domain Path:       /languages
 */

/**
 * If this file is called directly, abort.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */


// APP ID.
if ( ! defined( 'CONV_APP_ID' ) ) {
	define( 'CONV_APP_ID', 13 );
}
// Screen ID.
if (!defined('CONV_SCREEN_ID')) {
	define('CONV_SCREEN_ID', 'conversios_page_');
}
//Top Menu
if (!defined('CONV_TOP_MENU')) {
	define('CONV_TOP_MENU', 'Conversios');
}
//Menu Slug
if (!defined('CONV_MENU_SLUG')) {
	define('CONV_MENU_SLUG', 'conversios');
}

function is_EeAioFreePro_active($plugin_name = 'enhanced-e-commerce-for-woocommerce-store/enhanced-ecommerce-google-analytics.php')
{
	if (!function_exists('is_plugin_active')) {
		include_once(ABSPATH . 'wp-admin/includes/plugin.php');
	}
	return is_plugin_active($plugin_name);
}



register_activation_hook(__FILE__, 'activate_enhanced_ecommerce_google_analytics_pro');
function activate_enhanced_ecommerce_google_analytics_pro()
{
	if (is_EeAioFreePro_active('enhanced-e-commerce-for-woocommerce-store/enhanced-ecommerce-google-analytics.php')) {
		deactivate_plugins('enhanced-e-commerce-for-woocommerce-store/enhanced-ecommerce-google-analytics.php', true, false);
	}

	if (is_EeAioFreePro_active('enhanced-e-commerce-pro-for-woocommerce-store/enhanced-ecommerce-pro-google-analytics.php')) {
		deactivate_plugins('enhanced-e-commerce-pro-for-woocommerce-store/enhanced-ecommerce-pro-google-analytics.php', true, false);
	}

	$ee_options_settings = unserialize(get_option('ee_options'));

	$subscriptionId = (isset($ee_options_settings['subscription_id'])) ? $ee_options_settings['subscription_id'] : "";
	$apiDomain = "https://connect.tatvic.com/laravelapi/public/api";
	$header = array(
		"Authorization: Bearer 'MTIzNA=='",
		"Content-Type" => "application/json"
	);

	$current_user = wp_get_current_user();

	if (empty($subscriptionId)) {
		$current_user = wp_get_current_user();

		// Do customer login
		$url = $apiDomain . '/customers/login';
		$header = array("Authorization: Bearer MTIzNA==", "content-type: application/json");
		$data = [
			'first_name' => "",
			'last_name' => "",
			'access_token' => "",
			'refresh_token' => "",
			'email' => $current_user->user_email,
			'sign_in_type' => 1,
			'app_id' => CONV_APP_ID,
			'platform_id' => 1
		];

		$curl_url = $url;
		$data = json_encode($data);
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL => $curl_url, //esc_url($curl_url),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_POSTFIELDS => $data
		));
		$dologin_response = curl_exec($ch);
		$dologin_response = json_decode($dologin_response);


		// Update token to subs
		$url = $apiDomain . '/customer-subscriptions/update-token';
		$header = array("Authorization: Bearer MTIzNA==", "content-type: application/json");
		$data = [
			'subscription_id' => "",
			'gmail' => $current_user->user_email,
			'access_token' => "",
			'refresh_token' => "",
			'domain' => get_site_url(),
			'app_id' =>  CONV_APP_ID,
			'platform_id' => 1
		];

		$curl_url = $url;
		$data = json_encode($data);
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL => $curl_url, //esc_url($curl_url),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_POSTFIELDS => $data
		));

		$updatetoken_response = curl_exec($ch);
		$updatetoken_response = json_decode($updatetoken_response);


		//Get subscription details
		$url = $apiDomain . '/customer-subscriptions/subscription-detail';
		$header = array("Authorization: Bearer MTIzNA==", "content-type: application/json");
		$data = [
			'subscription_id' => $updatetoken_response->data->customer_subscription_id,
			'domain' => get_site_url(),
			'app_id' => CONV_APP_ID,
			'platform_id' => 1
		];
		$curl_url = $url;
		$postData = json_encode($data);
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL => $curl_url, //esc_url($curl_url),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_POSTFIELDS => $postData
		));
		$subsdetails_response = curl_exec($ch);
		$subsdetails_response = json_decode($subsdetails_response);

		$eeapidata = array("setting" => $subsdetails_response->data);
		update_option("ee_api_data", serialize($eeapidata));

		$subscriptiondata = $subsdetails_response->data;

		$eeoptions = array();
		$eeoptions["subscription_id"] = (isset($subscriptiondata->id) && $subscriptiondata->id != "") ? sanitize_text_field($subscriptiondata->id) : "";
		$eeoptions["ga_eeT"] = "on";
		$eeoptions["ga_ST"] = "on";
		$eeoptions["gm_id"] = (isset($subscriptiondata->measurement_id) && $subscriptiondata->measurement_id != "") ? sanitize_text_field($subscriptiondata->measurement_id) : "";
		$eeoptions["ga_id"] = (isset($subscriptiondata->property_id) && $subscriptiondata->property_id != "") ? sanitize_text_field($subscriptiondata->property_id) : "";
		$eeoptions["google_ads_id"] = (isset($subscriptiondata->google_ads_id) && $subscriptiondata->google_ads_id != "") ? sanitize_text_field($subscriptiondata->google_ads_id) : "";
		$eeoptions["google_merchant_id"] = (isset($subscriptiondata->google_merchant_center_id) && $subscriptiondata->google_merchant_center_id != "") ? sanitize_text_field($subscriptiondata->google_merchant_center_id) : "";
		$eeoptions["tracking_option"] = (isset($subscriptiondata->tracking_option) && $subscriptiondata->tracking_option != "") ? sanitize_text_field($subscriptiondata->tracking_option) : "";
		$eeoptions["ga_gUser"] = "on";
		$eeoptions["ga_Impr"] = "6";
		$eeoptions["ga_IPA"] = "on";
		$eeoptions["ga_PrivacyPolicy"] = "on";
		$eeoptions["google-analytic"] = "";
		$eeoptions["ga4_api_secret"] = "";
		$eeoptions["ga_CG"] = "";
		$eeoptions["ga_optimize_id"] = "";
		$eeoptions["tracking_method"] = (isset($subscriptiondata->tracking_method) && $subscriptiondata->tracking_method != "") ? sanitize_text_field($subscriptiondata->tracking_method) : "";
		$eeoptions["tvc_product_list_data_collection_method"] = (isset($subscriptiondata->tvc_product_list_data_collection_method) && $subscriptiondata->tvc_product_list_data_collection_method != "") ? sanitize_text_field($subscriptiondata->tvc_product_list_data_collection_method) : "";
		$eeoptions["tvc_product_detail_data_collection_method"] = (isset($subscriptiondata->tvc_product_detail_data_collection_method) && $subscriptiondata->tvc_product_detail_data_collection_method != "") ? sanitize_text_field($subscriptiondata->tvc_product_detail_data_collection_method) : "";
		$eeoptions["tvc_checkout_data_collection_method"] = (isset($subscriptiondata->tvc_checkout_data_collection_method) && $subscriptiondata->tvc_checkout_data_collection_method != "") ? sanitize_text_field($subscriptiondata->tvc_checkout_data_collection_method) : "";
		$eeoptions["tvc_thankyou_data_collection_method"] = (isset($subscriptiondata->tvc_thankyou_data_collection_method) && $subscriptiondata->tvc_thankyou_data_collection_method != "") ? sanitize_text_field($subscriptiondata->tvc_thankyou_data_collection_method) : "";
		$eeoptions["tvc_product_detail_addtocart_selector"] = (isset($subscriptiondata->tvc_product_detail_addtocart_selector) && $subscriptiondata->tvc_product_detail_addtocart_selector != "") ? sanitize_text_field($subscriptiondata->tvc_product_detail_addtocart_selector) : "";
		$eeoptions["tvc_product_detail_addtocart_selector_type"] = (isset($subscriptiondata->tvc_product_detail_addtocart_selector_type) && $subscriptiondata->tvc_product_detail_addtocart_selector_type != "") ? sanitize_text_field($subscriptiondata->tvc_product_detail_addtocart_selector_type) : "";
		$eeoptions["tvc_product_detail_addtocart_selector_val"] = (isset($subscriptiondata->tvc_product_detail_addtocart_selector_val) && $subscriptiondata->tvc_product_detail_addtocart_selector_val != "") ? sanitize_text_field($subscriptiondata->tvc_product_detail_addtocart_selector_val) : "";
		$eeoptions["tvc_checkout_step_2_selector"] = (isset($subscriptiondata->tvc_checkout_step_2_selector) && $subscriptiondata->tvc_checkout_step_2_selector != "") ? sanitize_text_field($subscriptiondata->tvc_checkout_step_2_selector) : "";
		$eeoptions["tvc_checkout_step_2_selector_type"] = (isset($subscriptiondata->tvc_checkout_step_2_selector_type) && $subscriptiondata->tvc_checkout_step_2_selector_type != "") ? sanitize_text_field($subscriptiondata->tvc_checkout_step_2_selector_type) : "";
		$eeoptions["tvc_checkout_step_2_selector_val"] = (isset($subscriptiondata->tvc_checkout_step_2_selector_val) && $subscriptiondata->tvc_checkout_step_2_selector_val != "") ? sanitize_text_field($subscriptiondata->tvc_checkout_step_2_selector_val) : "";
		$eeoptions["tvc_checkout_step_3_selector"] = (isset($subscriptiondata->tvc_checkout_step_3_selector) && $subscriptiondata->tvc_checkout_step_3_selector != "") ? sanitize_text_field($subscriptiondata->tvc_checkout_step_3_selector) : "";
		$eeoptions["tvc_checkout_step_3_selector_type"] = (isset($subscriptiondata->tvc_checkout_step_3_selector_type) && $subscriptiondata->tvc_checkout_step_3_selector_type != "") ? sanitize_text_field($subscriptiondata->tvc_checkout_step_3_selector_type) : "";
		$eeoptions["tvc_checkout_step_3_selector_val"] = (isset($subscriptiondata->tvc_checkout_step_3_selector_val) && $subscriptiondata->tvc_checkout_step_3_selector_val != "") ? sanitize_text_field($subscriptiondata->tvc_checkout_step_3_selector_val) : "";
		$eeoptions["want_to_use_your_gtm"] = (isset($subscriptiondata->want_to_use_your_gtm) && $subscriptiondata->want_to_use_your_gtm != "") ? sanitize_text_field($subscriptiondata->want_to_use_your_gtm) : "";
		$eeoptions["use_your_gtm_id"] = (isset($subscriptiondata->use_your_gtm_id) && $subscriptiondata->use_your_gtm_id != "") ? sanitize_text_field($subscriptiondata->use_your_gtm_id) : "";
		$eeoptions["fb_pixel_id"] = (isset($subscriptiondata->fb_pixel_id) && $subscriptiondata->fb_pixel_id != "") ? sanitize_text_field($subscriptiondata->fb_pixel_id) : "";
		$eeoptions["microsoft_ads_pixel_id"] = (isset($subscriptiondata->microsoft_ads_pixel_id) && $subscriptiondata->microsoft_ads_pixel_id != "") ? sanitize_text_field($subscriptiondata->microsoft_ads_pixel_id) : "";
		$eeoptions["twitter_ads_pixel_id"] = (isset($subscriptiondata->twitter_ads_pixel_id) && $subscriptiondata->twitter_ads_pixel_id != "") ? sanitize_text_field($subscriptiondata->twitter_ads_pixel_id) : "";
		$eeoptions["pinterest_ads_pixel_id"] = (isset($subscriptiondata->pinterest_ads_pixel_id) && $subscriptiondata->pinterest_ads_pixel_id != "") ? sanitize_text_field($subscriptiondata->pinterest_ads_pixel_id) : "";
		$eeoptions["snapchat_ads_pixel_id"] = (isset($subscriptiondata->snapchat_ads_pixel_id) && $subscriptiondata->snapchat_ads_pixel_id != "") ? sanitize_text_field($subscriptiondata->snapchat_ads_pixel_id) : "";
		$eeoptions["tiKtok_ads_pixel_id"] = (isset($subscriptiondata->tiKtok_ads_pixel_id) && $subscriptiondata->tiKtok_ads_pixel_id != "") ? sanitize_text_field($subscriptiondata->tiKtok_ads_pixel_id) : "";
		$eeoptions["fb_conversion_api_token"] = (isset($subscriptiondata->fb_conversion_api_token) && $subscriptiondata->fb_conversion_api_token != "") ? sanitize_text_field($subscriptiondata->fb_conversion_api_token) : "";
		$eeoptions["tiKtok_business_id"] = (isset($subscriptiondata->tiKtok_business_id) && $subscriptiondata->tiKtok_business_id != "") ? sanitize_text_field($subscriptiondata->tiKtok_business_id) : "";
		$eeoptions["tiKtok_mail_id"] = (isset($subscriptiondata->tiKtok_mail_id) && $subscriptiondata->tiKtok_mail_id != "") ? sanitize_text_field($subscriptiondata->tiKtok_mail_id) : "";

		update_option("ee_options", serialize($eeoptions));
	} else {
		$url = $apiDomain . "/customer-subscriptions/app_activity_detail";

		$postData = array(
			"subscription_id" => $subscriptionId,
			"domain" => esc_url_raw(get_site_url()),
			"app_status" => sanitize_text_field('active'),
			"app_data" => array(
				"app_version" => "1.0.0",
				"app_id" => CONV_APP_ID,
				"is_pro" => 1
			)
		);
		$args = array(
			'headers' => $header,
			'method' => 'POST',
			"timeout" => "1000",
			'body' => wp_json_encode($postData)
		);

		$request = wp_remote_post(esc_url_raw($url), $args);
	}
}

register_deactivation_hook(__FILE__, 'deactivate_enhanced_ecommerce_google_analytics_pro');
function deactivate_enhanced_ecommerce_google_analytics_pro()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-enhanced-ecommerce-google-analytics-deactivator.php';
	Enhanced_Ecommerce_Google_Analytics_Deactivator::deactivate();
	wp_clear_scheduled_hook('tvc_add_cron_interval_for_product_sync');
}


if (is_EeAioFreePro_active()) {
	return;
}


define('PLUGIN_TVC_VERSION', '1.0.0');
$fullName = plugin_basename(__FILE__);
$dir = str_replace('/server-side-tagging-via-google-tag-manager-for-wordpress.php', '', $fullName);
if (!defined('ENHANCAD_PLUGIN_NAME')) {
	define('ENHANCAD_PLUGIN_NAME', $dir);
}
// Store the directory of the plugin
if (!defined('ENHANCAD_PLUGIN_DIR')) {
	define('ENHANCAD_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
// Store the url of the plugin
if (!defined('ENHANCAD_PLUGIN_URL')) {
	define('ENHANCAD_PLUGIN_URL', plugins_url() . '/' . ENHANCAD_PLUGIN_NAME);
}

if (!defined('TVC_API_CALL_URL')) {
	define('TVC_API_CALL_URL', 'https://connect.tatvic.com/laravelapi/public/api');    
}

if (!defined('TVC_AUTH_CONNECT_URL')) {
	define('TVC_AUTH_CONNECT_URL', 'conversios.io');
}

if (!defined('TVC_Admin_Helper')) {
	include_once(ENHANCAD_PLUGIN_DIR . '/admin/class-tvc-admin-helper.php');
}

if (!defined('CNVS_LOG')) {
	define('CNVS_LOG', ENHANCAD_PLUGIN_DIR . 'logs/');
}

add_action('upgrader_process_complete', 'tvc_upgrade_function_pro', 10, 2);
function tvc_upgrade_function_pro($upgrader_object, $options)
{
	$fullName = plugin_basename(__FILE__);
	if ($options['action'] == 'update' && $options['type'] == 'plugin' && is_array($options['plugins'])) {
		foreach ($options['plugins'] as $each_plugin) {
			if ($each_plugin == $fullName) {
				$TVC_Admin_Helper = new TVC_Admin_Helper();
				$TVC_Admin_Helper->update_app_status();
			}
		}
	}
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-enhanced-ecommerce-google-analytics.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

function run_enhanced_ecommerce_google_analytics_pro()
{
	$plugin = new Enhanced_Ecommerce_Google_Analytics_Pro();
	$plugin->run();
}
run_enhanced_ecommerce_google_analytics_pro();

<?php
/**
 * Plugin Name: Media Redirect to Production
 * Description: Redirects media URLs to the production domain, with optional local uploads fallback.
 * Version: 1.13.0
 * Author: Kasia Izak i ChatGPT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MRP_VERSION', '1.13.0' );
define( 'MRP_PLUGIN_FILE', __FILE__ );
define( 'MRP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MRP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MRP_SETTINGS_GROUP', 'mrp_settings_group' );
define( 'MRP_SETTINGS_PAGE', 'media-redirect' );
define( 'MRP_OPTION_PRODUCTION_DOMAIN', 'mrp_production_domain' );
define( 'MRP_OPTION_CUSTOM_WPCONTENT', 'mrp_custom_wpcontent' );
define( 'MRP_OPTION_PREFER_LOCAL_UPLOADS', 'mrp_prefer_local_uploads' );
define( 'MRP_OPTION_ENABLE_WPBAKERY_COMPAT', 'mrp_enable_wpbakery_compat' );
define( 'MRP_OPTION_ENABLE_HORSECLUB_LATEST_POST_COMPAT', 'mrp_enable_horseclub_latest_post_compat' );

require_once MRP_PLUGIN_DIR . 'includes/options.php';
require_once MRP_PLUGIN_DIR . 'includes/rewrite.php';
if ( mrp_should_enable_wpbakery_compat() ) {
	require_once MRP_PLUGIN_DIR . 'includes/plugins/wpbakery.php';
}
if ( mrp_should_enable_horseclub_latest_post_compat() ) {
	require_once MRP_PLUGIN_DIR . 'includes/themes/horseclub.php';
}
require_once MRP_PLUGIN_DIR . 'includes/admin.php';

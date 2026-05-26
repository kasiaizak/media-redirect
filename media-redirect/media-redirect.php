<?php
/**
 * Plugin Name: Media Redirect to Production
 * Description: Redirects media URLs to the production domain, with optional local uploads fallback.
 * Version: 1.10.0
 * Author: Kasia Izak i ChatGPT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MRP_VERSION', '1.10.0' );
define( 'MRP_PLUGIN_FILE', __FILE__ );
define( 'MRP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MRP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once MRP_PLUGIN_DIR . 'includes/rewrite.php';
require_once MRP_PLUGIN_DIR . 'includes/admin.php';

<?php
/**
 * Plugin Name: Larry Ragland Dashboard
 * Description: Prayer Requests, God Did It testimonials, and EventON events shortcodes.
 * Version: 1.0.0
 * Author: Larry Ragland
 * Text Domain: lrd
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'LRD_PATH', plugin_dir_path( __FILE__ ) );
define( 'LRD_URL',  plugin_dir_url( __FILE__ ) );
define( 'LRD_VERSION', '1.0.0' );

require_once LRD_PATH . 'includes/class-lrd-db.php';
require_once LRD_PATH . 'includes/class-lrd-prayer.php';
require_once LRD_PATH . 'includes/class-lrd-godit.php';
require_once LRD_PATH . 'includes/class-lrd-events.php';
require_once LRD_PATH . 'includes/class-lrd-notifications.php';
require_once LRD_PATH . 'includes/class-lrd-ajax.php';

register_activation_hook( __FILE__, array( 'LRD_DB', 'install' ) );

add_action( 'wp_enqueue_scripts', 'lrd_enqueue_assets' );
function lrd_enqueue_assets() {
    wp_enqueue_style( 'lrd-style', LRD_URL . 'assets/css/lrd.css', array(), LRD_VERSION );
    wp_enqueue_script( 'lrd-script', LRD_URL . 'assets/js/lrd.js', array('jquery'), LRD_VERSION, true );
    wp_localize_script( 'lrd-script', 'LRD', array(
        'ajax_url'   => admin_url('admin-ajax.php'),
        'nonce'      => wp_create_nonce('lrd_nonce'),
        'logged_in'  => is_user_logged_in(),
        'user_id'    => get_current_user_id(),
        'user_name'  => is_user_logged_in() ? wp_get_current_user()->display_name : '',
        'login_url'  => wp_login_url( get_permalink() ),
    ));
}

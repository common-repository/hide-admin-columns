<?php
/*
Plugin Name: Hide Admin Columns
Plugin URI: https://www.fixwp.io
Description: Allows administrators to manage and hide admin columns.
Version: 1.0.1
Author: Bishoy A.
Author URI: https://www.bishoy.me
License: GPLv2 or later
Text Domain: hide-admin-columns
*/

namespace HideAdminColumns;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-hide-admin-columns.php';

function fwp_run_hide_admin_columns() {
	$plugin = new \HideAdminColumns\Hide_Admin_Columns();
	$plugin->run();
}

add_action('wp_loaded', 'HideAdminColumns\fwp_run_hide_admin_columns', 9999);
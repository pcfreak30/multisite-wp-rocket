<?php
/*
 * Plugin Name: WP Rocket Multisite
 * Plugin URI: https://github.com/pcfreak30/rocket-multisite
 * Description: Plugin to enable WP-Rocket to be managed in multisite
 * Version: 0.1.0
 * Author: Derrick Hammer
 * Author URI: https://www.derrickhammer.com
 * License: GPL3
 * Network: true
*/

/**
 * Deactivate and show error if WP-Rocket is missing
 *
 * @since 0.1.0
 *
 */
function rocket_multisite_plugins_loaded() {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	$error = false;
	if ( validate_plugin( 'wp-rocket/wp-rocket.php' ) || ! is_plugin_active( 'wp-rocket/wp-rocket.php' ) ) {
		$error = true;
		add_action( 'network_admin_notices', 'rocket_multisite_activate_error_no_wprocket' );
	}
	if ( $error ) {
		deactivate_plugins( basename( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . basename( __FILE__ ) );
	}
}

/**
 * Error function if WP Rocket is missing
 *
 * @since 0.1.0
 *
 */
function rocket_multisite_activate_error_no_wprocket() {
	$info = get_plugin_data( __FILE__ );
	_e( sprintf( '
	<div class="error notice">
		<p>Opps! %s requires WP Rocket! Please Download at <a href="http://www.wp-rocket.me">www.wp-rocket.me</a></p>
	</div>', $info['Name'] ) );
}

function rocket_multisite_admin_menu() {
	global $pagenow;
	if ( function_exists( 'get_rocket_option' ) ) {
		$wl_plugin_name = get_rocket_option( 'wl_plugin_name', WP_ROCKET_PLUGIN_NAME );

		$wl_plugin_slug = sanitize_key( $wl_plugin_name );

		if ( 'options-general.php' == $pagenow && ! empty( $_GET['page'] ) && $wl_plugin_slug == $_GET['page'] ) {
			wp_redirect( add_query_arg( 'page', $wl_plugin_slug, network_admin_url( 'settings.php' ) ) );
			exit();
		}
		add_submenu_page( 'settings.php', $wl_plugin_name, $wl_plugin_name, 'manage_network_options', $wl_plugin_slug, 'rocket_multisite_display_options' );
	}
}

function rocket_multisite_display_options() {
	require ABSPATH . 'wp-admin/options-head.php';
	ob_start();
	rocket_display_options();
	$output = ob_get_clean();
	echo str_replace( 'options.php', '../options.php', $output );
}

function rocket_multisite_update_option( $value, $old_value, $option ) {
	update_site_option( $option, $value );

	return $old_value;
}

function rocket_multisite_get_option( $value, $option ) {
	return get_site_option( $option );
}

function rocket_multisite_after_save_options() {
	foreach ( get_sites( array( 'fields' => 'ids', 'site__not_in' => array( BLOG_ID_CURRENT_SITE ) ) ) as $blog_id ) {
		switch_to_blog( $blog_id );
		rocket_generate_config_file();
		restore_current_blog();
	}
}

function rocket_multisite_purge_cache() {
	$lang = isset( $_GET['lang'] ) && $_GET['lang'] != 'all' ? sanitize_key( $_GET['lang'] ) : '';
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $blog_id ) {
		switch_to_blog( $blog_id );
		rocket_clean_domain( $lang );
		rocket_clean_minify();
		restore_current_blog();
	}
}

add_action( 'pre_update_' . WP_ROCKET_SLUG, 'rocket_multisite_update_option', 10, 3 );
add_action( 'pre_option_' . WP_ROCKET_SLUG, 'rocket_multisite_get_option', 10, 2 );
if ( is_subdomain_install() ) {
	add_action( 'update_option_' . WP_ROCKET_SLUG, 'rocket_multisite_after_save_options', 10 );
}
add_action( 'network_admin_menu', 'rocket_multisite_admin_menu', 9 );
add_action( 'admin_menu', 'rocket_multisite_admin_menu', 9 );

add_action( 'plugins_loaded', 'rocket_multisite_plugins_loaded', 11 );
add_action( 'admin_post_purge_cache', 'rocket_multisite_purge_cache', 9 );
<?php
/*
 * Plugin Name: Multisite Support for WP Rocket
 * Plugin URI: https://github.com/pcfreak30/multisite-wp-rocket
 * Description: Plugin to enable WP-Rocket to be managed in multisite
 * Version: 0.1.6
 * Author: Derrick Hammer
 * Author URI: https://www.derrickhammer.com
 * License: GPL3
 * Network: true
*/

use WP_Rocket\Admin\Database\Optimization;
use WP_Rocket\Admin\Database\Optimization_Process;
use WP_Rocket\Admin\Options;
use WP_Rocket\Admin\Options_Data;
use WP_Rocket\Admin\Settings\Beacon;
use WP_Rocket\Admin\Settings\Page as Settings_Page;
use WP_Rocket\Admin\Settings\Render as Settings_Render;
use WP_Rocket\Admin\Settings\Settings;

/**
 * Deactivate and show error if WP-Rocket is missing
 *
 * @since 0.1.0
 *
 */
function multisite_wp_rocket_plugins_loaded() {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	$error = false;
	if ( validate_plugin( 'wp-rocket/wp-rocket.php' ) || ! is_plugin_active( 'wp-rocket/wp-rocket.php' ) ) {
		$error = true;
		add_action( 'network_admin_notices', 'multisite_wp_rocket_activate_error_no_wprocket' );
	}
	if ( $error ) {
		deactivate_plugins( basename( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . basename( __FILE__ ) );

		return;
	}
	add_action( 'pre_update_option_' . WP_ROCKET_SLUG, 'multisite_wp_rocket_update_option', 11, 3 );
	add_action( 'pre_option_' . WP_ROCKET_SLUG, 'multisite_wp_rocket_get_option', 10, 2 );
	if ( is_subdomain_install() ) {
		add_action( 'update_site_option_' . WP_ROCKET_SLUG, 'multisite_wp_rocket_after_save_options', 9 );
	}
}

/**
 * Error function if WP Rocket is missing
 *
 * @since 0.1.0
 *
 */
function multisite_wp_rocket_activate_error_no_wprocket() {
	$info = get_plugin_data( __FILE__ );
	_e( sprintf( '
	<div class="error notice">
		<p>Opps! %s requires WP Rocket! Please Download at <a href="http://www.wp-rocket.me">www.wp-rocket.me</a></p>
	</div>', $info['Name'] ) );
}

function multisite_wp_rocket_admin_menu() {
	global $pagenow;
	if ( function_exists( 'get_rocket_option' ) ) {
		$wl_plugin_name = get_rocket_option( 'wl_plugin_name', WP_ROCKET_PLUGIN_NAME );

		$wl_plugin_slug = sanitize_key( $wl_plugin_name );

		if ( 'options-general.php' === $pagenow && ! empty( $_GET['page'] ) && $wl_plugin_slug == $_GET['page'] ) {
			wp_redirect( add_query_arg( 'page', $wl_plugin_slug, network_admin_url( 'settings.php' ) ) );
			exit();
		}
		add_submenu_page( 'settings.php', $wl_plugin_name, $wl_plugin_name, 'manage_network_options', $wl_plugin_slug, 'multisite_wp_rocket_display_options' );
	}
}

function multisite_wp_rocket_display_options() {
	require ABSPATH . 'wp-admin/options-head.php';
	ob_start();

	if ( class_exists( '\WP_Rocket\Plugin' ) ) {
		$settings_page_args = [
			'slug'       => WP_ROCKET_PLUGIN_SLUG,
			'title'      => WP_ROCKET_PLUGIN_NAME,
			'capability' => apply_filters( 'rocket_capacity', 'manage_options' ),
		];
		$options        = new Options( 'wp_rocket_' );
		$options_data = new Options_Data( $options->get( 'settings', array() ) );
		$settings = new Settings( $options_data );
		$settings_render = new Settings_Render( WP_ROCKET_PATH . 'views/settings' );
		$beancon = new Beacon( $options_data );
		$optimization_process = new Optimization_Process();
		$optimization = new Optimization( $optimization_process );
		$settings_page = new Settings_Page( $settings_page_args, $settings, $settings_render, $beancon, $optimization );

		add_action( 'wp_ajax_rocket_toggle_option', [ $settings_page, 'toggle_option' ] );
		add_filter( 'option_page_capability_' . WP_ROCKET_PLUGIN_SLUG, [ $settings_page, 'required_capability' ] );
		add_filter( 'pre_get_rocket_option_cache_mobile', [ $settings_page, 'is_mobile_plugin_active' ] );
		add_filter( 'pre_get_rocket_option_do_caching_mobile_files', [ $settings_page, 'is_mobile_plugin_active' ] );

		$settings_page->configure();
		$settings_page->render_page();
	} else {
		rocket_display_options();
	}
	$output = ob_get_clean();
	echo str_replace( 'options.php', '../options.php', $output );
}

function multisite_wp_rocket_update_option( $value, $old_value, $option ) {
	update_site_option( $option, $value );

	return $old_value;
}

function multisite_wp_rocket_get_option( $value, $option ) {
	return get_site_option( $option );
}

function multisite_wp_rocket_after_save_options() {

	foreach ( get_sites( array( 'fields' => 'ids', 'site__not_in' => array( BLOG_ID_CURRENT_SITE ) ) ) as $blog_id ) {
		switch_to_blog( $blog_id );
		rocket_generate_config_file();
		restore_current_blog();
	}
}

function multisite_wp_rocket_purge_cache() {
	$lang = isset( $_GET['lang'] ) && $_GET['lang'] != 'all' ? sanitize_key( $_GET['lang'] ) : '';
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $blog_id ) {
		switch_to_blog( $blog_id );
		rocket_clean_domain( $lang );
		rocket_clean_minify();
		restore_current_blog();
	}
}

function multisite_wp_rocket_filter_config_files( $config_files_path ) {
	foreach ( $config_files_path as $index => $path ) {
		if ( '' === pathinfo( $path, PATHINFO_FILENAME ) ) {
			unset( $config_files_path[ $index ] );
		}
	}

	return $config_files_path;
}

add_action( 'network_admin_menu', 'multisite_wp_rocket_admin_menu', 9 );
add_action( 'admin_menu', 'multisite_wp_rocket_admin_menu', 9 );

add_action( 'plugins_loaded', 'multisite_wp_rocket_plugins_loaded', 9 );
add_action( 'admin_post_purge_cache', 'multisite_wp_rocket_purge_cache', 9 );

add_filter( 'rocket_config_files_path', 'multisite_wp_rocket_filter_config_files' );

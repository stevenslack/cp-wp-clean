<?php
/**
 * Cleans up a WordPress site by removing or customizing certain features
 * Adapted some functions from Cliff Seal: https://gist.github.com/logoscreative/f881dd0473dd60a687d0
 *
 * This plugin was meant to be modified per project.
 * Customize this file and drop it in the mu directory
 *
 *
 * Plugin Name: WordPress Cleanup
 * Plugin URI: http://stevenslack.com/
 * Description: Cleans up WordPress admin and removes unnecessary functions
 * Version: 1.0
 * Author: Steven Slack <steven@s2webpress.com>
 * Author URI: http://stevenslack.com
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Turn off File editing from WordPress admin
 */

if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

/**
 * Uncomment below if you want to disallow the user to add plugins
 */
// if ( ! defined( 'DISALLOW_FILE_MODS' ) ) {
//  define( 'DISALLOW_FILE_MODS', true );
// }

/**
 * The Cleanup Class
 */
class CP_Cleanup {

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.0.0
	 */
	public static function init() {

		$self = new self();
		add_action( 'wp_loaded', array( $self, 'on_loaded' ) );

	}

	/**
	 * Fire hooks on wp_loaded
	 * This action hook is fired once WordPress, all plugins, and the theme are fully loaded and instantiated.
	 * https://codex.wordpress.org/Plugin_API/Action_Reference/wp_loaded
	 */
	public function on_loaded() {

		add_action( 'admin_menu', array( $this, 'remove_pages' ), 999 );
		add_action( 'wp_before_admin_bar_render', array( $this, 'remove_admin_bar_links' ) );
		add_action( 'admin_init', array( $this, 'remove_dashboard_widgets' ) );
		add_action( 'admin_init', array( $this, 'add_theme_caps' ) );
		add_action( 'customize_register', array( $this, 'remove_customizer_sections' ), 20 );
		add_action( 'init', array( $this, 'remove_admin_notices' ) );
		add_action( 'init', array( $this, 'head_cleanup' ) );
		add_filter( 'show_advanced_plugins', '__return_false' );
		// hide plugins from view
		add_filter( 'all_plugins', array( $this, 'filter_plugins' ) );
		// remove WP version from rss feed
		add_filter( 'the_generator', '__return_false' );
		// remove WP version from css
		add_filter( 'style_loader_src', array( $this, 'remove_wp_ver_css_js' ), 9999 );
		// remove Wp version from scripts
		add_filter( 'script_loader_src', array( $this, 'remove_wp_ver_css_js' ), 9999 );

	}

	/**
	 * Clean Up WordPress Head
	 */
	public function head_cleanup() {

		// remove feed links
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		// EditURI link
		remove_action( 'wp_head', 'rsd_link' );
		// windows live writer
		remove_action( 'wp_head', 'wlwmanifest_link' );
		// previous link
		remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );
		// start link
		remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
		// links for adjacent posts
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
		// WP version
		remove_action( 'wp_head', 'wp_generator' );
		// WP Shortlinks
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );

	}

	/*
	 * Remove Query Strings From CSS & Javascript
	 */
	public function remove_wp_ver_css_js( $src ) {
		if ( strpos( $src, 'ver=' ) )
			$src = remove_query_arg( 'ver', $src );
		return $src;
	}

	/**
	 * Remove Pages
	 *
	 * http://codex.wordpress.org/Function_Reference/remove_menu_page
	 *
	 * @since    1.0.0
	 */
	public function remove_pages() {

		$current_user = wp_get_current_user();

		if ( ! ( $current_user instanceof WP_User ) )
			return;

		// remove_menu_page( 'edit-comments.php' );
		// remove_menu_page( 'sucuriscan' );
		// remove_menu_page( 'w3tc_dashboard' );
		// remove_menu_page( 'amazon-web-services' );
		remove_submenu_page( 'tools.php', 'tools.php' );

		// hide menu for every one else except for this user
		if ( $current_user->user_login !== 'admin' ) {
			// remove_submenu_page( 'themes.php', 'theme-editor.php' );
			// remove_submenu_page( 'plugins.php', 'plugin-editor.php' );
		}

	}

	/**
	 * Hide Extraneous Plugin Options from the Menu Bar
	 *
	 * @since    1.0.0
	 */
	public function remove_admin_bar_links() {

		global $wp_admin_bar;

		$wp_admin_bar->remove_menu( 'wp-logo' );          // Remove the WordPress logo
		$wp_admin_bar->remove_menu( 'about' );            // Remove the about WordPress link
		$wp_admin_bar->remove_menu( 'wporg' );            // Remove the WordPress.org link
		$wp_admin_bar->remove_menu( 'support-forums' );   // Remove the support forums link
		$wp_admin_bar->remove_menu( 'feedback' );         // Remove the feedback link
		$wp_admin_bar->remove_menu( 'comments' );         // Remove the comments link
		// $wp_admin_bar->remove_menu('w3tc-faq');      // Remove W3TC total cache faq
		// $wp_admin_bar->remove_menu('w3tc-support');  // Remove W3TC total support
		// $wp_admin_bar->remove_menu('wpseo-menu');    // Remove the Yoast SEO menu

	}

	/**
	 * Cleanup WP Dashboard
	 *
	 * @since    1.0.0
	 */
	public function remove_dashboard_widgets() {
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );   // Quick Press
		remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' ); // Recent Drafts
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );       // WordPress blog
		remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );    // Other WordPress News
	}

	/**
	 * Allow Editors to Get to Widgets
	 *
	 * @since    1.0.0
	 */
	public function add_theme_caps() {
		$role_object = get_role( 'editor' );
		$role_object->add_cap( 'edit_theme_options' );
	}

	/**
	 * Remove Customizer Sections
	 *
	 * @since    1.0.0
	 */
	public function remove_customizer_sections() {
		global $wp_customize;

		// Removes the theme switcher from the customizer
		// because we don't want them switching away from our awesome theme!
		$wp_customize->remove_section( 'themes' );

		// Uncomment the below lines to remove the default controls
		// $wp_customize->remove_section( 'title_tagline' );
		// $wp_customize->remove_section( 'colors' );
		// $wp_customize->remove_section( 'background_image' );
		// $wp_customize->remove_section( 'static_front_page' );
		// $wp_customize->remove_section( 'nav' );
		// $wp_customize->remove_control( 'blogdescription' );

	}

	/**
	 * Remove Admin Notices
	 *
	 * @since    1.0.0
	 */
	public function remove_admin_notices() {
		remove_action( 'admin_notices', 'woothemes_updater_notice' );
	}

	/**
	 * Hide Sensitive Plugins from Plugins Listing
	 *
	 * @since    1.0.0
	 */
	public function filter_plugins( $plugins ) {
		$hidden = array(
			'Sucuri Security - Auditing, Malware Scanner and Hardening',
			'W3 Total Cache'
		);
		if ( ! isset( $_GET['seeplugins'] ) || $_GET['seeplugins'] !== 'akismet' ) {
			foreach ( $plugins as $key => &$plugin ) {
				if ( in_array( $plugin["Name"], $hidden ) ) {
					unset( $plugins[$key] );
				}
			}
		}
		return $plugins;
	}

}
CP_Cleanup::init();

<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       moxcar.com
 * @since      1.0.0
 *
 * @package    KOC_Physicians_Network_Sync
 * @subpackage KOC_Physicians_Network_Sync/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    KOC_Physicians_Network_Sync
 * @subpackage KOC_Physicians_Network_Sync/includes
 * @author     Gino Peterson <gpeterson@moxcar.com>
 */
class KOC_Physicians_Network_Sync {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      KOC_Physicians_Network_Sync_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $koc_physicians_network_sync    The string used to uniquely identify this plugin.
	 */
	protected $koc_physicians_network_sync;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'KOC_PHYSICIANS_NETWORK_SYNC_VERSION' ) ) {
			$this->version = KOC_PHYSICIANS_NETWORK_SYNC_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->koc_physicians_network_sync = 'koc-physicians-network-sync';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - KOC_Physicians_Network_Sync_Loader. Orchestrates the hooks of the plugin.
	 * - KOC_Physicians_Network_Sync_i18n. Defines internationalization functionality.
	 * - KOC_Physicians_Network_Sync_Admin. Defines all hooks for the admin area.
	 * - KOC_Physicians_Network_Sync_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-koc-physicians-network-sync-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-koc-physicians-network-sync-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-koc-physicians-network-sync-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-koc-physicians-network-sync-public.php';

		/**
		 * The class responsible for creating admin pages
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-koc-phyisicians-network-sync-admin-pages.php';

		$this->loader = new KOC_Physicians_Network_Sync_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the KOC_Physicians_Network_Sync_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new KOC_Physicians_Network_Sync_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new KOC_Physicians_Network_Sync_Admin( $this->get_koc_physicians_network_sync(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$plugin_admin_pages = new KOC_Physicians_Network_Sync_Admin_Pages();
		$this->loader->add_action( 'admin_menu', $plugin_admin_pages, 'register_admin_pages' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new KOC_Physicians_Network_Sync_Public( $this->get_koc_physicians_network_sync(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_koc_physicians_network_sync() {
		return $this->koc_physicians_network_sync;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    KOC_Physicians_Network_Sync_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}

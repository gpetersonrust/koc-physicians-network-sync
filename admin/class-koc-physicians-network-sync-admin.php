<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       moxcar.com
 * @since      1.0.0
 *
 * @package    KOC_Physicians_Network_Sync
 * @subpackage KOC_Physicians_Network_Sync/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    KOC_Physicians_Network_Sync
 * @subpackage KOC_Physicians_Network_Sync/admin
 * @author     Gino Peterson <gpeterson@moxcar.com>
 */
class KOC_Physicians_Network_Sync_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $koc_physicians_network_sync    The ID of this plugin.
	 */
	private $koc_physicians_network_sync;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $koc_physicians_network_sync       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $koc_physicians_network_sync, $version ) {

		$this->koc_physicians_network_sync = $koc_physicians_network_sync;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in KOC_Physicians_Network_Sync_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The KOC_Physicians_Network_Sync_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->koc_physicians_network_sync, plugin_dir_url( __FILE__ ) . 'css/koc-physicians-network-sync-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in KOC_Physicians_Network_Sync_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The KOC_Physicians_Network_Sync_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->koc_physicians_network_sync, plugin_dir_url( __FILE__ ) . 'js/koc-physicians-network-sync-admin.js', array( 'jquery' ), $this->version, false );

	}

}

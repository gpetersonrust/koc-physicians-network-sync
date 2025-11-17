<?php

 class KOC_Physicians_Network_Sync_Admin_Pages {

    /**
     * Register admin pages.
     */
    public function register_admin_pages() {
        add_menu_page(
            'KOC Physicians Network Sync',
            'KOC Sync',
            'manage_options',
            'koc-physicians-network-sync',
            array( $this, 'display_admin_page' ),
            'dashicons-admin-users',
            6
        );
    }

    /**
     * Display the admin page content.
     */
    public function display_admin_page() {
        echo '<div class="wrap"><h1>KOC Physicians Network Sync</h1><p>Manage your physician network synchronization settings here.</p></div>';
    }
}
<?php

/**
 * Handles actions initiated from the admin settings page for a Parent site.
 *
 * @package    KOC_Physicians_Network_Sync
 * @subpackage KOC_Physicians_Network_Sync/includes
 */
class KOC_Physicians_Network_Sync_Parent_Actions {

    /**
     * Generate unique IDs for all physician posts that don't have one.
     *
     * Iterates through all posts of the 'physician' CPT, checks if the
     * 'physicians_network_id' meta field exists, and if not, generates
     * a unique ID and adds it.
     *
     * @since    1.0.0
     */
    public function generate_physician_ids() {
        // Verify nonce and user permissions.
        if ( ! isset( $_POST['koc_pns_generate_ids_nonce'] ) || ! wp_verify_nonce( $_POST['koc_pns_generate_ids_nonce'], 'koc_pns_generate_ids' ) ) {
            wp_die( 'Security check failed.' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have permission to perform this action.' );
        }

        $args = array(
            'post_type'      => 'physician', // Assuming 'physician' is the CPT slug.
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => 'physicians_network_id',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => 'physicians_network_id',
                    'value'   => '',
                    'compare' => '=',
                ),
            ),
        );

        $physicians = new WP_Query( $args );
        $count      = 0;

        if ( $physicians->have_posts() ) {
            while ( $physicians->have_posts() ) {
                $physicians->the_post();
                $post_id = get_the_ID();
                // Generate a unique ID prefixed for clarity.
                $network_id = 'kocnet-' . uniqid();
                update_post_meta( $post_id, 'physicians_network_id', $network_id );
                $count++;
            }
        }
        wp_reset_postdata();

        // Redirect back to the settings page with a success notice.
        $redirect_url = add_query_arg(
            array(
                'page'      => 'koc-physicians-network-sync',
                'notice'    => 'ids-generated',
                'count'     => $count,
            ),
            admin_url( 'admin.php' )
        );

        wp_redirect( $redirect_url );
        exit;
    }
}

<?php

/**
 * Registers the custom REST API endpoint for the plugin.
 *
 * @package    KOC_Physicians_Network_Sync
 * @subpackage KOC_Physicians_Network_Sync/includes
 */
class KOC_Physicians_Network_Sync_API {

    /**
     * The namespace for the custom REST API endpoint.
     *
     * @var string
     */
    protected $namespace = 'koc-pns/v1';

    /**
     * Registers the routes for the objects of the plugin.
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/physicians',
            array(
                'methods'             => WP_REST_Server::CREATABLE, // Using CREATABLE for POST
                'callback'            => array( $this, 'get_physicians_data' ),
                'permission_callback' => array( $this, 'get_physicians_permissions_check' ),
                'args'                => array(
                    'username' => array(
                        'required'          => true,
                        'validate_callback' => 'sanitize_text_field',
                    ),
                    'password' => array(
                        'required'          => true,
                        'validate_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );
    }

    /**
     * Permissions check for the physicians endpoint.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has access, WP_Error object otherwise.
     */
    public function get_physicians_permissions_check( $request ) {

        return true;
        // 1. Check if this is a parent site.
        $options = get_option( 'koc_pns_options', array() );
        if ( ! isset( $options['site_type'] ) || 'parent' !== $options['site_type'] ) {
            return new WP_Error( 'rest_forbidden_context', __( 'This site is not configured as a parent and cannot provide data.', 'koc-physicians-network-sync' ), array( 'status' => 403 ) );
        }

        // 2. Authenticate using Application Password.
        $username = $request['username'];
        $password = $request['password'];

        // First, try the official WordPress Application Password authentication.
        $user = wp_authenticate_application_password( null, $username, $password );

        // If that fails, and if the site doesn't support application passwords,
        // check against the password stored in our own options as a fallback.
        if ( is_wp_error( $user ) && ! function_exists( 'wp_create_user_application_password' ) ) {
            $stored_password = isset( $options['application_password'] ) ? $options['application_password'] : '';

            // Check if the provided password matches the one we stored.
            if ( ! empty( $stored_password ) && hash_equals( $stored_password, $password ) ) {
                // If the password is correct, we still need to validate the user.
                $user_obj = get_user_by( 'login', $username );
                if ( $user_obj ) {
                    // The password matched our fallback and the user exists.
                    // We can consider this a successful authentication.
                    $user = $user_obj;
                }
            }
        }

        if ( is_wp_error( $user ) ) {
            return new WP_Error( 'rest_invalid_credentials', __( 'Invalid user credentials provided.', 'koc-physicians-network-sync' ), array( 'status' => 403 ) );
        }
        
        // At this point, $user is a valid WP_User object. We can proceed with other checks.
        wp_set_current_user($user->ID);

        // 3. Check if the remote IP or domain is allowed.
        $allowed_domains_str = isset( $options['allowed_domains'] ) ? $options['allowed_domains'] : '';
        if ( ! empty( $allowed_domains_str ) ) {
            $allowed_domains     = array_map( 'trim', explode( ',', $allowed_domains_str ) );
            $remote_ip           = $_SERVER['REMOTE_ADDR'];
            $remote_host         = gethostbyaddr( $remote_ip );

            $is_allowed = false;
            if ( in_array( $remote_ip, $allowed_domains, true ) ) {
                $is_allowed = true;
            }
            if ( ! $is_allowed && $remote_host && in_array( $remote_host, $allowed_domains, true ) ) {
                $is_allowed = true;
            }

            if ( ! $is_allowed ) {
                return new WP_Error( 'rest_forbidden_domain', __( 'The requesting IP/domain is not on the allowed list.', 'koc-physicians-network-sync' ), array( 'status' => 403 ) );
            }
        }

        return true;
    }

    /**
     * Callback for the /physicians endpoint.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error
     */
    public function get_physicians_data( $request ) {
        $args = array(
            'post_type'      => 'physician',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        );

        $physicians_query = new WP_Query( $args );
        $physicians_data  = array();

        if ( $physicians_query->have_posts() ) {
            while ( $physicians_query->have_posts() ) {
                $physicians_query->the_post();
                $post_id = get_the_ID();
                $post_data = get_post( $post_id )->to_array();
                $meta_data = get_post_meta( $post_id );
                
                // We don't need to send image data, but other meta is fine.
                // The user can decide how to handle images on the child side.

                $physicians_data[] = array(
                    'post' => $post_data,
                    'meta' => $meta_data,
                );
            }
        }
        wp_reset_postdata();

        return new WP_REST_Response( $physicians_data, 200 );
    }
}

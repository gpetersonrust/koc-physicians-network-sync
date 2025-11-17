<?php

class KOC_Physicians_Network_Sync_Admin_Pages {

    /**
     * Call these from your loader or main plugin file:
     *
     * $admin_pages = new KOC_Physicians_Network_Sync_Admin_Pages();
     * $loader->add_action( 'admin_menu', $admin_pages, 'register_admin_pages' );
     * $loader->add_action( 'admin_init', $admin_pages, 'register_settings' );
     * $loader->add_action( 'admin_notices', $admin_pages, 'display_admin_notices' );
     * $loader->add_action( 'admin_post_koc_pns_generate_app_password', $admin_pages, 'handle_generate_app_password_action' );
     * $loader->add_action( 'admin_post_koc_pns_generate_ids', $admin_pages, 'handle_generate_ids_action' );
     * $loader->add_action( 'admin_post_koc_pns_trigger_sync', $admin_pages, 'handle_trigger_sync_action' );
     */

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
     * Display admin notices based on query args.
     */
    public function display_admin_notices() {
        if ( ! isset( $_GET['page'] ) || 'koc-physicians-network-sync' !== $_GET['page'] ) {
            return;
        }

        if ( isset( $_GET['notice'] ) ) {
            $notice_type = $_GET['notice'];
            $message = '';
            $class = 'notice-info';

            if ( 'ids-generated' === $notice_type ) {
                $count = isset( $_GET['count'] ) ? (int) $_GET['count'] : 0;
                $message = sprintf(
                    esc_html__( 'Successfully generated unique IDs for %d physicians.', 'koc-physicians-network-sync' ),
                    $count
                );
                $class = 'notice-success';
            } elseif ( 'sync-success' === $notice_type ) {
                $updated = isset( $_GET['updated'] ) ? (int) $_GET['updated'] : 0;
                $created = isset( $_GET['created'] ) ? (int) $_GET['created'] : 0;
                $message = sprintf(
                    esc_html__( 'Sync successful. Updated: %d, Created: %d.', 'koc-physicians-network-sync' ),
                    $updated,
                    $created
                );
                $class = 'notice-success';
            } elseif ( 'sync-failed' === $notice_type ) {
                $error_code = isset( $_GET['error'] ) ? sanitize_text_field( $_GET['error'] ) : 'unknown';
                $message = sprintf( esc_html__( 'Sync failed. Error: %s', 'koc-physicians-network-sync' ), $error_code );
                $class = 'notice-error';
            } elseif ( 'sync-not-implemented' === $notice_type ) {
                $message = esc_html__( 'The manual sync trigger is not yet implemented.', 'koc-physicians-network-sync' );
                $class = 'notice-info';
            } elseif ( 'app-password-generated' === $notice_type ) {
                $message = esc_html__( 'A new application password has been generated.', 'koc-physicians-network-sync' );
                $class = 'notice-success';
            } elseif ( 'app-password-failed' === $notice_type ) {
                $error_code = isset( $_GET['error'] ) ? sanitize_text_field( $_GET['error'] ) : 'unknown';
                if ( 'plugin_missing' === $error_code ) {
                    $message = sprintf(
                        esc_html__( 'The Application Passwords feature is not available. Please install and activate the %s plugin.', 'koc-physicians-network-sync' ),
                        '<a href="https://wordpress.org/plugins/application-passwords/" target="_blank">Application Passwords</a>'
                    );
                } else {
                    $message = sprintf( esc_html__( 'Failed to generate Application Password. Error: %s', 'koc-physicians-network-sync' ), $error_code );
                }
                $class = 'notice-error';
            }

            if ( $message ) {
                echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . $message . '</p></div>';
            }
        }
    }
    
    /**
     * Handle the generation of application passwords for child sites.
     */
    public function handle_generate_app_password_action() {
        error_log('KOC PNS: handle_generate_app_password_action started.');

        if ( ! current_user_can( 'manage_options' ) ) {
            error_log('KOC PNS: User does not have manage_options capability.');
            wp_die( 'You do not have permission to perform this action.' );
        }

        // Verify nonce for security.
        if ( ! isset( $_POST['koc_pns_generate_app_password_nonce'] ) || ! wp_verify_nonce( $_POST['koc_pns_generate_app_password_nonce'], 'koc_pns_generate_app_password' ) ) {
            error_log('KOC PNS: Nonce verification failed.');
            $this->redirect_with_notice( 'app-password-failed', 'nonce_mismatch' );
        }
        error_log('KOC PNS: Nonce verified.');

        $user_id = get_current_user_id();
        error_log('KOC PNS: User ID: ' . $user_id);

        $app_password_name = sprintf( __( 'KOC PNS Generated %s', 'koc-physicians-network-sync' ), date( 'Y-m-d H:i:s' ) );
        error_log('KOC PNS: App Password Name: ' . $app_password_name);
        
        $app_password = '';
        if ( function_exists( 'wp_create_user_application_password' ) ) {
            // Generate a new application password using core function.
            $app_password_data = wp_create_user_application_password( $user_id, array(
                'name' => $app_password_name,
            ) );

            if ( is_wp_error( $app_password_data ) ) {
                error_log('KOC PNS: wp_create_user_application_password failed: ' . $app_password_data->get_error_message());
                $this->redirect_with_notice( 'app-password-failed', $app_password_data->get_error_code() );
            }
            $app_password = $app_password_data[0];
        } else {
            // Fallback: Generate a random 16-character string with mixed case and symbols.
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_+=';
            $app_password = substr( str_shuffle( $chars ), 0, 16 );
            // Log for debugging on older WP versions
            error_log('KOC PNS: Custom generated password due to missing wp_create_user_application_password: ' . $app_password);
        }

        error_log( 'Generated Password: ' . $app_password );

        // Persist the password in plugin options.
        $options = get_option( 'koc_pns_options', array() );
        $options['application_password'] = $app_password;
        error_log( 'Options to be saved: ' . print_r( $options, true ) );
        update_option( 'koc_pns_options', $options );
        wp_cache_delete( 'alloptions', 'options' );

        // No transient needed anymore.
        $this->redirect_with_notice( 'app-password-generated' );
    }

    /**
     * Handle the form submission for generating physician IDs.
     */
    public function handle_generate_ids_action() {
        $parent_actions = new KOC_Physicians_Network_Sync_Parent_Actions();
        $parent_actions->generate_physician_ids();
    }

    /**
     * Handle the form submission for triggering a sync from a child site.
     */
    public function handle_trigger_sync_action() {
        // Verify nonce and user permissions.
        if ( ! isset( $_POST['koc_pns_trigger_sync_nonce'] ) || ! wp_verify_nonce( $_POST['koc_pns_trigger_sync_nonce'], 'koc_pns_trigger_sync' ) ) {
            wp_die( 'Security check failed.' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have permission to perform this action.' );
        }

        $options = get_option( 'koc_pns_options', array() );
        $parent_url = isset( $options['parent_url'] ) ? $options['parent_url'] : '';
       
        $password = isset( $options['application_password'] ) ? $options['application_password'] : '';

        if ( empty( $parent_url ) || empty( $password ) ) {
            $this->redirect_with_notice( 'sync-failed', 'missing_credentials' );
        }

        $api_url = trailingslashit( $parent_url ) . 'wp-json/koc-pns/v1/physicians';

        $response = wp_remote_post( $api_url, array(
            'method'    => 'POST',
            'timeout'   => 45,
            'body'      => array(
                'password' => $password,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            $this->redirect_with_notice( 'sync-failed', $response->get_error_message() );
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        if ( 200 !== $response_code ) {
            $error_data = json_decode( $response_body, true );
            $error_message = isset( $error_data['message'] ) ? $error_data['message'] : 'unknown_api_error';
            $this->redirect_with_notice( 'sync-failed', $error_message );
        }

        $physicians_data = json_decode( $response_body, true );
        $updated_count = 0;
        $created_count = 0;

        foreach ( $physicians_data as $physician ) {
            $network_id = isset( $physician['meta']['physicians_network_id'][0] ) ? $physician['meta']['physicians_network_id'][0] : null;
            $post_title = isset( $physician['post']['post_title'] ) ? $physician['post']['post_title'] : null;

            $post_id = 0; // Initialize post_id

            // 1. Try to find post by network ID.
            if ( $network_id ) {
                $args = array(
                    'post_type'      => 'physician',
                    'meta_key'       => 'physicians_network_id',
                    'meta_value'     => $network_id,
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                );
                $existing_posts = get_posts( $args );
                if ( ! empty( $existing_posts ) ) {
                    $post_id = $existing_posts[0];
                }
            }

            // 2. If not found by network ID, try to find by post title and type (for first sync).
            if ( ! $post_id && $post_title ) {
                $args = array(
                    'post_type'      => 'physician',
                    'title'          => $post_title,
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                    'post_status'    => 'any', // Include all statuses to find existing.
                );
                $existing_posts_by_title = get_posts( $args );
                if ( ! empty( $existing_posts_by_title ) ) {
                    $post_id = $existing_posts_by_title[0];
                    // If matched by title, add the network_id for future syncs.
                    if ( $network_id ) {
                        update_post_meta( $post_id, 'physicians_network_id', $network_id );
                    }
                }
            }

            $post_data = array(
                'post_title'   => $physician['post']['post_title'],
                'post_content' => $physician['post']['post_content'],
                'post_excerpt' => $physician['post']['post_excerpt'],
                'post_status'  => 'publish',
                'post_type'    => 'physician',
            );
            
            if ( $post_id ) {
                // Update existing post.
                $post_data['ID'] = $post_id;
                wp_update_post( $post_data );
                $updated_count++;
            } else {
                // Do not create new post and set post id to null 
                $post_id = null;
                
            }
            
            if( $post_id && ! is_wp_error( $post_id ) ) {
                // Update all meta fields.
                foreach( $physician['meta'] as $meta_key => $meta_value_array ) {
                    // Skip updating the featured image.
                    if ( '_thumbnail_id' === $meta_key ) {
                        continue;
                    }
                    // get_post_meta returns an array, so we take the first value.
                    // update_post_meta will serialize if it's an array.
                    update_post_meta( $post_id, $meta_key, $meta_value_array[0] );
                }
            }
        }

        // Update the 'last_sync' timestamp.
        $options['last_sync'] = current_time( 'mysql' );
        update_option( 'koc_pns_options', $options );
        
        $this->redirect_with_notice( 'sync-success', null, array( 'updated' => $updated_count, 'created' => $created_count ) );
    }

    /**
     * Helper to redirect to the settings page with a notice.
     */
    private function redirect_with_notice( $notice_type, $error_code = null, $extra_args = array() ) {
        $base_url = admin_url( 'admin.php' );
        $query_args = array(
            'page'      => 'koc-physicians-network-sync',
            'notice'    => $notice_type,
        );

        if ( $error_code ) {
            $query_args['error'] = $error_code;
        }
        
        if ( ! empty( $extra_args ) ) {
            $query_args = array_merge( $query_args, $extra_args );
        }

        wp_redirect( add_query_arg( $query_args, $base_url ) );
        exit;
    }


    /**
     * Register settings, sections, and fields.
     */
    public function register_settings() {

        register_setting(
            'koc_physicians_network_sync_options_group',
            'koc_pns_options',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_options' ),
                'default'           => array(
                    'site_type' => 'parent',
                ),
            )
        );

        // One section for everything.
        add_settings_section(
            'koc_pns_main_section',
            __( 'Network Sync Settings', 'koc-physicians-network-sync' ),
            array( $this, 'main_section_cb' ),
            'koc-physicians-network-sync'
        );

        // Site type: Parent or Child.
        add_settings_field(
            'koc_pns_site_type',
            __( 'Site Type', 'koc-physicians-network-sync' ),
            array( $this, 'site_type_field_cb' ),
            'koc-physicians-network-sync',
            'koc_pns_main_section'
        );

        // Parent-only: allowed domains.
        add_settings_field(
            'koc_pns_allowed_domains',
            __( 'Allowed Domains', 'koc-physicians-network-sync' ),
            array( $this, 'allowed_domains_field_cb' ),
            'koc-physicians-network-sync',
            'koc_pns_main_section',
            array(
                'class' => 'koc-pns-parent-row',
            )
        );

        // Child-only: application password.
        add_settings_field(
            'koc_pns_application_password',
            __( 'Application Password', 'koc-physicians-network-sync' ),
            array( $this, 'application_password_field_cb' ),
            'koc-physicians-network-sync',
            'koc_pns_main_section'
        );

    

        // Child-only: parent URL.
        add_settings_field(
            'koc_pns_parent_url',
            __( 'Parent Site URL', 'koc-physicians-network-sync' ),
            array( $this, 'parent_url_field_cb' ),
            'koc-physicians-network-sync',
            'koc_pns_main_section',
            array(
                'class' => 'koc-pns-child-row',
            )
        );

        // Child-only: sync frequency.
        add_settings_field(
            'koc_pns_sync_frequency',
            __( 'Sync Frequency', 'koc-physicians-network-sync' ),
            array( $this, 'sync_frequency_field_cb' ),
            'koc-physicians-network-sync',
            'koc_pns_main_section',
            array(
                'class' => 'koc-pns-child-row',
            )
        );

        // Child-only: last sync.
        add_settings_field(
            'koc_pns_last_sync',
            __( 'Last Sync', 'koc-physicians-network-sync' ),
            array( $this, 'last_sync_field_cb' ),
            'koc-physicians-network-sync',
            'koc_pns_main_section',
            array(
                'class' => 'koc-pns-child-row',
            )
        );
    }

    public function main_section_cb() {
        echo '<p>' .
             esc_html__(
                 'Choose whether this site is the Parent (data source) or a Child (consumer). Then configure the appropriate credentials.',
                 'koc-physicians-network-sync'
             ) .
             '</p>';
    }

    /**
     * Parent/Child toggle.
     */
    public function site_type_field_cb() {
        $options   = get_option( 'koc_pns_options', array() );
        $site_type = isset( $options['site_type'] ) ? $options['site_type'] : 'parent';
        ?>
        <fieldset>
            <label>
                <input type="radio"
                       name="koc_pns_options[site_type]"
                       value="parent"
                       <?php checked( $site_type, 'parent' ); ?> />
                <?php esc_html_e( 'Parent (provides data to children)', 'koc-physicians-network-sync' ); ?>
            </label>
            <br />
            <label>
                <input type="radio"
                       name="koc_pns_options[site_type]"
                       value="child"
                       <?php checked( $site_type, 'child' ); ?> />
                <?php esc_html_e( 'Child (requests data from parent)', 'koc-physicians-network-sync' ); ?>
            </label>
        </fieldset>
        <?php
    }

    /**
     * Parent: Allowed domains (comma-separated).
     */
    public function allowed_domains_field_cb() {
        $options         = get_option( 'koc_pns_options', array() );
        $allowed_domains = isset( $options['allowed_domains'] ) ? $options['allowed_domains'] : '';
        ?>
        <textarea id="koc_pns_allowed_domains"
                  name="koc_pns_options[allowed_domains]"
                  rows="3"
                  class="large-text"><?php echo esc_textarea( $allowed_domains ); ?></textarea>
        <p class="description">
            <?php esc_html_e( 'Comma-separated list of domains allowed to connect as children (e.g. child1.koc.com, child2.koc.com).', 'koc-physicians-network-sync' ); ?>
        </p>
        <?php
    }

    /**
     * Child: Parent URL.
     */
    public function parent_url_field_cb() {
        $options    = get_option( 'koc_pns_options', array() );
        $parent_url = isset( $options['parent_url'] ) ? $options['parent_url'] : '';
        ?>
        <input type="url"
               id="koc_pns_parent_url"
               name="koc_pns_options[parent_url]"
               value="<?php echo esc_attr( $parent_url ); ?>"
               class="regular-text"
               placeholder="https://parent.koc.com" />
        <p class="description">
            <?php esc_html_e( 'The full URL of the parent site.', 'koc-physicians-network-sync' ); ?>
        </p>
        <?php
    }

    /**
     * Child: application password.
     */
    public function application_password_field_cb() {
        $options          = get_option( 'koc_pns_options', array() );
        $application_pass = isset( $options['application_password'] ) ? $options['application_password'] : '';
        $site_type        = isset( $options['site_type'] ) ? $options['site_type'] : 'parent';

        $description = '';
        $input_attributes = '';
        $show_copy_button = false;

        if ( 'parent' === $site_type ) {
            $description = esc_html__( 'Application password generated on this parent site for a child site to authenticate.', 'koc-physicians-network-sync' );
            $input_attributes = 'readonly="readonly"';
            $show_copy_button = true;
        } else { // 'child'
            $description = esc_html__( 'Application password provided by the parent site for this child site to authenticate. Paste it here and save changes.', 'koc-physicians-network-sync' );
            // No readonly attribute, so it's editable.
        }

        ?>
        <div style="display: flex; align-items: center; gap: 10px;">
            <input type="text"
                   id="koc_pns_application_password"
                   name="koc_pns_options[application_password]"
                   value="<?php echo esc_attr( $application_pass ); ?>"
                   class="regular-text"
                   autocomplete="off"
                   <?php echo $input_attributes; ?> />
            <?php if ( $show_copy_button ) : ?>
                <button type="button" class="button button-secondary" id="koc-pns-copy-password"><?php esc_html_e( 'Copy', 'koc-physicians-network-sync' ); ?></button>
            <?php endif; ?>
        </div>
        <p class="description">
            <?php echo $description; ?>
            <?php if ( 'parent' === $site_type ) : ?>
                <br>
                <em><?php esc_html_e( 'This value is not editable, but can be copied.', 'koc-physicians-network-sync' ); ?></em>
            <?php endif; ?>
        </p>
        <?php
    }

    /**
     * Child: user login.
     */
    public function child_username_field_cb() {
        $options   = get_option( 'koc_pns_options', array() );
        $username  = isset( $options['child_username'] ) ? $options['child_username'] : '';
        ?>
        <input type="text"
               id="koc_pns_child_username"
               name="koc_pns_options[child_username]"
               value="<?php echo esc_attr( $username ); ?>"
               class="regular-text" />
        <p class="description">
            <?php esc_html_e( 'Username that will authenticate against the parent site.', 'koc-physicians-network-sync' ); ?>
        </p>
        <?php
    }



    /**
     * Child: sync frequency.
     */
    public function sync_frequency_field_cb() {
        $options    = get_option( 'koc_pns_options', array() );
        $frequency  = isset( $options['sync_frequency'] ) ? $options['sync_frequency'] : '4h';
        $intervals = array(
            '1h'  => __( 'Every Hour', 'koc-physicians-network-sync' ),
            '4h'  => __( 'Every 4 Hours', 'koc-physicians-network-sync' ),
            '8h'  => __( 'Every 8 Hours', 'koc-physicians-network-sync' ),
            '24h' => __( 'Every 24 Hours', 'koc-physicians-network-sync' ),
        );
        ?>
        <select id="koc_pns_sync_frequency" name="koc_pns_options[sync_frequency]">
            <?php foreach ( $intervals as $value => $label ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $frequency, $value ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Child: last sync timestamp.
     */
    public function last_sync_field_cb() {
        $options   = get_option( 'koc_pns_options', array() );
        $last_sync = isset( $options['last_sync'] ) ? $options['last_sync'] : 'Never';
        ?>
        <p><strong><?php echo esc_html( $last_sync ); ?></strong></p>
        <p class="description">
            <?php esc_html_e( 'The date and time of the last successful data pull.', 'koc-physicians-network-sync' ); ?>
        </p>
        <?php
    }


    /**
     * Sanitize and normalize options.
     */
    public function sanitize_options( $input ) {
        $sanitized = array();
        $existing_options = get_option( 'koc_pns_options', array() );

        // Site type: only parent/child allowed.
        $sanitized['site_type'] = (
            isset( $input['site_type'] ) && 'child' === $input['site_type']
        )
            ? 'child'
            : 'parent';

        // Parent setting: allowed domains (keep as a normalized comma-separated string).
        if ( isset( $input['allowed_domains'] ) ) {
            $domains = array_filter(
                array_map(
                    'trim',
                    explode( ',', $input['allowed_domains'] )
                )
            );
            $sanitized['allowed_domains'] = implode( ', ', $domains );
        }

        // Child settings.
        if ( isset( $input['parent_url'] ) ) {
            $sanitized['parent_url'] = esc_url_raw( $input['parent_url'] );
        }
        if ( isset( $input['application_password'] ) ) {
            $sanitized['application_password'] = $input['application_password'];
        } elseif ( isset( $existing_options['application_password'] ) ) {
            $sanitized['application_password'] = $existing_options['application_password'];
        }

        if ( isset( $input['child_username'] ) ) {
            $sanitized['child_username'] = sanitize_text_field( $input['child_username'] );
        }



        // Child setting: sync frequency.
        $allowed_frequencies = array( '1h', '4h', '8h', '24h' );
        if ( isset( $input['sync_frequency'] ) && in_array( $input['sync_frequency'], $allowed_frequencies, true ) ) {
            $sanitized['sync_frequency'] = $input['sync_frequency'];
        } else {
            // Default to 4h if invalid value is submitted.
            $sanitized['sync_frequency'] = '4h';
        }

        // Preserve the last_sync value, as it is not user-editable.
        if ( isset( $existing_options['last_sync'] ) ) {
            $sanitized['last_sync'] = $existing_options['last_sync'];
        }


        return $sanitized;
    }


    /**
     * Display the admin page content.
     */
    public function display_admin_page() {
        require_once KOC_PHYSICIANS_NETWORK_SYNC_PLUGIN_DIR . 'views/koc-physicians-network-sync-page.php';
    }
}

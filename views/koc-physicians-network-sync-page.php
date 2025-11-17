<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <form method="post" action="options.php">
        <?php
        settings_fields( 'koc_physicians_network_sync_options_group' );
        do_settings_sections( 'koc-physicians-network-sync' );
        submit_button();
        ?>
    </form>

    <hr />

    <h2><?php esc_html_e( 'Actions', 'koc-physicians-network-sync' ); ?></h2>

    <?php
    $options = get_option( 'koc_pns_options', array() );
    $site_type = isset( $options['site_type'] ) ? $options['site_type'] : 'parent';
    ?>

    <div id="koc-pns-parent-actions" style="display: <?php echo 'parent' === $site_type ? 'block' : 'none'; ?>;">
        <h3><?php esc_html_e( 'Parent Site Actions', 'koc-physicians-network-sync' ); ?></h3>
        <p><?php esc_html_e( 'Generate unique network IDs for all physicians. This is required for syncing.', 'koc-physicians-network-sync' ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="koc_pns_generate_ids">
            <?php wp_nonce_field( 'koc_pns_generate_ids', 'koc_pns_generate_ids_nonce' ); ?>
            <?php submit_button( __( 'Generate Physician IDs', 'koc-physicians-network-sync' ), 'secondary' ); ?>
        </form>

        <h4 style="margin-bottom: 0;"><?php esc_html_e( 'Application Passwords', 'koc-physicians-network-sync' ); ?></h4>
        <p class="description"><?php esc_html_e( 'Generate a new application password for a child site to use for authentication when syncing. Keep this password secret!', 'koc-physicians-network-sync' ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="koc_pns_generate_app_password">
            <?php wp_nonce_field( 'koc_pns_generate_app_password', 'koc_pns_generate_app_password_nonce' ); ?>
            <?php submit_button( __( 'Generate Application Password', 'koc-physicians-network-sync' ), 'secondary' ); ?>
        </form>
    </div>

    <div id="koc-pns-child-actions" style="display: <?php echo 'child' === $site_type ? 'block' : 'none'; ?>;">
        <h3><?php esc_html_e( 'Child Site Actions', 'koc-physicians-network-sync' ); ?></h3>
        <p><?php esc_html_e( 'Manually trigger a data pull from the parent site. This will update all physician data.', 'koc-physicians-network-sync' ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="koc_pns_trigger_sync">
            <?php wp_nonce_field( 'koc_pns_trigger_sync', 'koc_pns_trigger_sync_nonce' ); ?>
            <?php submit_button( __( 'Trigger Sync from Parent', 'koc-physicians-network-sync' ), 'primary' ); ?>
        </form>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('input[name="koc_pns_options[site_type]"]');
    const parentRows = document.querySelectorAll('tr.koc-pns-parent-row');
    const childRows  = document.querySelectorAll('tr.koc-pns-child-row');
    const parentActions = document.getElementById('koc-pns-parent-actions');
    const childActions = document.getElementById('koc-pns-child-actions');
    const passwordInput = document.getElementById('koc_pns_application_password');
    const copyButton = document.getElementById('koc-pns-copy-password');

    function updateVisibility() {
        const checked = document.querySelector('input[name="koc_pns_options[site_type]"]:checked');
        const type = checked ? checked.value : 'parent';

        parentRows.forEach(function (row) {
            row.style.display = (type === 'parent') ? '' : 'none';
        });

        childRows.forEach(function (row) {
            row.style.display = (type === 'child') ? '' : 'none';
        });

        if (parentActions) {
            parentActions.style.display = (type === 'parent') ? 'block' : 'none';
        }

        if (childActions) {
            childActions.style.display = (type === 'child') ? 'block' : 'none';
        }

        // Dynamically update the password field based on site type.
        if (passwordInput) {
            if (type === 'parent') {
                passwordInput.readOnly = true;
                if (copyButton) {
                    copyButton.style.display = '';
                }
            } else { // child
                passwordInput.readOnly = false;
                if (copyButton) {
                    copyButton.style.display = 'none';
                }
            }
        }
    }

    radios.forEach(function (r) {
        r.addEventListener('change', updateVisibility);
    });

    // Initial check
    updateVisibility();
});
</script>
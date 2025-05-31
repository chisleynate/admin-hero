<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1) Show “Info (Pro)” in Settings & bump priority
 */
add_filter( 'admin_hero_features', function( $features ) {
    foreach ( $features as &$feat ) {
        if ( $feat['id'] === 'info' ) {
            $feat['name']     = 'Info (Pro)';
            $feat['priority'] = 45;
            break;
        }
    }
    return $features;
}, 25 );

/**
 * 2) Enqueue Pro‐only stylesheet (depends on the free info.css handle)
 */
add_action( 'admin_enqueue_scripts', function() {
    if ( ! function_exists( 'admin_hero_pro_license_valid' ) || ! admin_hero_pro_license_valid() ) {
        return;
    }

    $assets_dir = ADMIN_HERO_PRO_DIR  . 'features/info-pro/assets/';
    $assets_url = ADMIN_HERO_PRO_URL  . 'features/info-pro/assets/';
    $css_files  = glob( $assets_dir . 'info-pro*.css' );
    if ( $css_files ) {
        rsort( $css_files );
        wp_enqueue_style(
            'admin-hero-info-pro',
            $assets_url . basename( $css_files[0] ),
            [ 'admin-hero-info' ],
            ADMIN_HERO_PRO_VERSION
        );
    }
}, 20 );

/**
 * 3) Inject Pro fieldsets into the free info form
 *    (only require a valid license—no user‐meta toggle)
 */
add_action( 'admin_hero_info_extra_fields', function() {
    if ( ! function_exists( 'admin_hero_pro_license_valid' ) || ! admin_hero_pro_license_valid() ) {
        return;
    }

    $uid = get_current_user_id();

    $sections = [
        'Hosting & Server' => [
            'hosting_provider_url' => [ 'label'=>'Hosting Provider URL', 'meta'=>'admin_hero_info_hosting_provider_url' ],
            'hosting_login_user'   => [ 'label'=>'Hosting Username',    'meta'=>'admin_hero_info_hosting_login_user' ],
            'hosting_login_pass'   => [ 'label'=>'Hosting Password',    'meta'=>'admin_hero_info_hosting_login_pass' ],
            'cp_url'               => [ 'label'=>'Control Panel URL',    'meta'=>'admin_hero_info_cp_url' ],
            'ftp_host'             => [ 'label'=>'FTP/SFTP Host',        'meta'=>'admin_hero_info_ftp_host' ],
            'ftp_port'             => [ 'label'=>'FTP/SFTP Port',        'meta'=>'admin_hero_info_ftp_port' ],
            'ftp_user'             => [ 'label'=>'FTP/SFTP User',        'meta'=>'admin_hero_info_ftp_user' ],
            'ftp_path'             => [ 'label'=>'FTP/SFTP Path',        'meta'=>'admin_hero_info_ftp_path' ],
            'server_ip'            => [ 'label'=>'Server IP Address',    'meta'=>'admin_hero_info_server_ip' ],
        ],
        'Domain & DNS' => [
            'domain_registrar_url' => [ 'label'=>'Domain Registrar URL','meta'=>'admin_hero_info_domain_registrar_url' ],
            'dns_host_url'         => [ 'label'=>'DNS Host URL',        'meta'=>'admin_hero_info_dns_host_url' ],
            'nameserver-1'         => [ 'label'=>'Nameserver 1',        'meta'=>'admin_hero_info_nameserver-1' ],
            'nameserver-2'         => [ 'label'=>'Nameserver 2',        'meta'=>'admin_hero_info_nameserver-2' ],
            'nameserver-3'         => [ 'label'=>'Nameserver 3',        'meta'=>'admin_hero_info_nameserver-3' ],
            'nameserver-4'         => [ 'label'=>'Nameserver 4',        'meta'=>'admin_hero_info_nameserver-4' ],
        ],
        'Google Services'  => [
            'shared_google_drive_url'    => [ 'label'=>'Shared Drive URL',         'meta'=>'admin_hero_info_shared_google_drive_url' ],
            'analytics_property_id'      => [ 'label'=>'Analytics Property ID',     'meta'=>'admin_hero_info_analytics_property_id' ],
            'search_console_verification'=> [ 'label'=>'Search Console Verification','meta'=>'admin_hero_info_search_console_verification' ],
            'gtm_container_id'           => [ 'label'=>'GTM Container ID',         'meta'=>'admin_hero_info_gtm_container_id' ],
            'gtm_workspace_url'          => [ 'label'=>'GTM Workspace URL',        'meta'=>'admin_hero_info_gtm_workspace_url' ],
        ],
        'Other Services'   => [
            'email_marketing_url'   => [ 'label'=>'Email Marketing URL',     'meta'=>'admin_hero_info_email_marketing_url' ],
            'email_marketing_user'  => [ 'label'=>'Email Marketing Username','meta'=>'admin_hero_info_email_marketing_user' ],
            'email_marketing_pass'  => [ 'label'=>'Email Marketing Password','meta'=>'admin_hero_info_email_marketing_pass' ],
        ],
        'Design & Branding' => [
            'example_site_1_url'    => [ 'label'=>'Example Site 1 URL', 'meta'=>'admin_hero_info_example_site_1_url' ],
            'example_site_2_url'    => [ 'label'=>'Example Site 2 URL', 'meta'=>'admin_hero_info_example_site_2_url' ],
            'example_site_3_url'    => [ 'label'=>'Example Site 3 URL', 'meta'=>'admin_hero_info_example_site_3_url' ],
        ],
    ];

    foreach ( $sections as $title => $fields ) {
        $slug = sanitize_key( str_replace( '&', 'and', $title ) );
        echo '<fieldset class="admin-hero-info-section admin-hero-info-section-'. esc_attr( $slug ) .'">';
        echo '<legend>'. esc_html( $title ) .'</legend>';

        foreach ( $fields as $key => $f ) {
            $val      = get_user_meta( $uid, $f['meta'], true );
            $field_id = 'admin-hero-info-' . $key;
            $is_url   = substr( $key, -4 ) === '_url';
            $has_val  = ! empty( $val );
            $link_href  = $has_val ? esc_url( $val ) : '#';
            $link_class = 'admin-hero-link-btn' . ( $has_val ? '' : ' disabled' );
            $link_attrs = $has_val
                ? 'target="_blank"'
                : 'aria-disabled="true" tabindex="-1" onclick="return false;"';
            ?>
            <p class="admin-hero-info-field admin-hero-info-<?php echo esc_attr( $key ); ?>">
              <label for="<?php echo esc_attr( $field_id ); ?>">
                <?php echo esc_html( $f['label'] ); ?>:
              </label><br/>
              <span class="admin-hero-info-input-wrap">
                <input
                  type="text"
                  id="<?php echo esc_attr( $field_id ); ?>"
                  name="<?php echo esc_attr( $key ); ?>"
                  class="regular-text"
                  value="<?php echo esc_attr( $val ); ?>"
                />

                <?php if ( $is_url ) : ?>
                  <a
                    href="<?php echo $link_href; ?>"
                    class="<?php echo esc_attr( $link_class ); ?>"
                    title="Open <?php echo esc_attr( $f['label'] ); ?>"
                    <?php echo $link_attrs; ?>
                  ><i class="fas fa-external-link-alt"></i></a>
                <?php endif; ?>

                <button
                  type="button"
                  class="admin-hero-copy-btn"
                  data-target="<?php echo esc_attr( $field_id ); ?>"
                  title="Copy <?php echo esc_attr( $f['label'] ); ?>"
                ><i class="fas fa-copy"></i></button>
              </span>
            </p>
            <?php
        }

        echo '</fieldset>';
    }
}, 30 );

/**
 * 4) Save all Pro fields _before_ the free info AJAX handler
 */
add_action( 'wp_ajax_admin_hero_save_info', function() {
    check_ajax_referer( 'admin_hero_action', 'nonce' );
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error();
    }

    $uid = get_current_user_id();
    $map = [
        'hosting_provider_url'      => 'admin_hero_info_hosting_provider_url',
        'hosting_login_user'        => 'admin_hero_info_hosting_login_user',
        'hosting_login_pass'        => 'admin_hero_info_hosting_login_pass',
        'cp_url'                    => 'admin_hero_info_cp_url',
        'ftp_host'                  => 'admin_hero_info_ftp_host',
        'ftp_port'                  => 'admin_hero_info_ftp_port',
        'ftp_user'                  => 'admin_hero_info_ftp_user',
        'ftp_path'                  => 'admin_hero_info_ftp_path',
        'server_ip'                 => 'admin_hero_info_server_ip',
        'domain_registrar_url'      => 'admin_hero_info_domain_registrar_url',
        'dns_host_url'              => 'admin_hero_info_dns_host_url',
        'nameserver-1'              => 'admin_hero_info_nameserver-1',
        'nameserver-2'              => 'admin_hero_info_nameserver-2',
        'nameserver-3'              => 'admin_hero_info_nameserver-3',
        'nameserver-4'              => 'admin_hero_info_nameserver-4',
        'shared_google_drive_url'   => 'admin_hero_info_shared_google_drive_url',
        'analytics_property_id'     => 'admin_hero_info_analytics_property_id',
        'search_console_verification'=> 'admin_hero_info_search_console_verification',
        'gtm_container_id'          => 'admin_hero_info_gtm_container_id',
        'gtm_workspace_url'         => 'admin_hero_info_gtm_workspace_url',
        'email_marketing_url'       => 'admin_hero_info_email_marketing_url',
        'email_marketing_user'      => 'admin_hero_info_email_marketing_user',
        'email_marketing_pass'      => 'admin_hero_info_email_marketing_pass',
        'example_site_1_url'        => 'admin_hero_info_example_site_1_url',
        'example_site_2_url'        => 'admin_hero_info_example_site_2_url',
        'example_site_3_url'        => 'admin_hero_info_example_site_3_url',
    ];
    foreach ( $map as $field => $meta_key ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_user_meta(
                $uid,
                $meta_key,
                sanitize_text_field( wp_unslash( $_POST[ $field ] ) )
            );
        }
    }
}, 5 );

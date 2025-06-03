<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Load “modal-core” dynamically (whichever file matches modal-core*.php).
 */
$tpls = glob( ADMIN_HERO_DIR . 'templates/modal-core*.php' );
rsort( $tpls );
$load_file = $tpls[0] ?? ( ADMIN_HERO_DIR . 'templates/modal-core.php' );
if ( file_exists( $load_file ) ) {
    include_once $load_file;
}

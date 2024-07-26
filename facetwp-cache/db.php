<?php

if ( is_multisite() ) {
    add_action( 'ms_loaded', 'fwp_load_cache_file' ); // wait for DB prefix
}
else {
    fwp_load_cache_file(); // run immediately
}

function fwp_load_cache_file() {
    if ( file_exists( WP_CONTENT_DIR . '/plugins/facetwp-cache/cache.php' ) ) {
        include( WP_CONTENT_DIR . '/plugins/facetwp-cache/cache.php' );
    }
}

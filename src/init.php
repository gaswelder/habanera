<?php

define( '_PATH', dirname(__FILE__).'/' );
require _PATH.'core/http.php';
require _PATH.'core/output.php';
require _PATH.'core/errors.php';
require _PATH.'core/libs.php';
require _PATH.'core/files.php';
require _PATH.'core/functions.php';
require _PATH.'core/logs.php';
require _PATH.'core/req_url.php';
require _PATH.'core/settings.php';
require _PATH.'core/top.php';
require _PATH.'core/uploads.php';
require _PATH.'core/user.php';
require _PATH.'core/vars.php';
require _PATH.'subservers/pages.php';
require _PATH.'subservers/actions.php';

/*
 * APP_DIR is the read-only directory where application files are
 * stored: templates, configuration files, static data and other source
 * files. If the application doesn't define it, it is set to "appfiles".
 * The directory must not be accessible through HTTP.
 */
if( !defined( 'APP_DIR' ) ) {
	define( 'APP_DIR', 'appfiles/' );
}

/*
 * WRITE_DIR is a directory in which the script will be writing some
 * working files like cache files or logs. It must not be accessible
 * through HTTP.
 */
if( !defined( 'WRITE_DIR' ) ) {
	define( 'WRITE_DIR', APP_DIR.'tmp/' );
}

function h2main( $base = '/' ) {
	h2::main( $base );
}
?>

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

function h2main( $base = '/' )
{
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

	/*
	 * SITE_PROTOCOL
	 */
	if( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) {
		define( 'SITE_PROTOCOL', 'https' );
	} else {
		define( 'SITE_PROTOCOL', 'http' );
	}

	/*
	 * We need to know the hostname to create URLs. It is in the HTTP_HOST
	 * header. If it is not there, then we are most likely dealing with some
	 * shady script making queries because all web browsers and most bots
	 * support this header.
	 */
	if( !isset( $_SERVER['HTTP_HOST'] ) ) {
		warning( "No host given in the request" );
		error_not_found();
	}

	define( 'SITE_DOMAIN', SITE_PROTOCOL.'://'.$_SERVER['HTTP_HOST'] );
	define( 'CURRENT_URL', SITE_DOMAIN.$_SERVER['REQUEST_URI'] );

	mb_internal_encoding( 'UTF-8' );

	add_classes_dir( APP_DIR.'classes' );
	if( file_exists( APP_DIR.'init.php' ) ) {
		require APP_DIR.'init.php';
	}

	load_ext( 'snippets' );
	h2::process( $base );
}
?>

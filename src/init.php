<?php

function h2main()
{
	define( '_PATH', dirname(__FILE__).'/' );

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
	 * SITE_ROOT is a prefix added to all site URLs. Most times
	 * this is a single backslash, but if the site is placed in a
	 * subdirectory, SITE_ROOT has to specify that subdirectory.
	 */
	if( !defined( 'SITE_ROOT' ) ) {
		define( 'SITE_ROOT', '/' );
	}

	/*
	 * USER_AGENT
	 */
	if( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
		define( 'USER_AGENT', $_SERVER['HTTP_USER_AGENT'] );
	} else {
		define( 'USER_AGENT', 'Unknown agent' );
	}

	/*
	 * SITE_PROTOCOL
	 */
	if( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) {
		define( 'SITE_PROTOCOL', 'https' );
	} else {
		define( 'SITE_PROTOCOL', 'http' );
	}

	mb_internal_encoding( 'UTF-8' );

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

	require _PATH.'subservers/pages.php';
	require _PATH.'subservers/actions.php';

	add_classes_dir( APP_DIR.'classes' );
	if( file_exists( APP_DIR.'init.php' ) ) {
		require APP_DIR.'init.php';
	}

	load_ext( 'snippets' );
	h2::process();
}
?>

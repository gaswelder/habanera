<?php
/*
 * APP_DIR is the read-only directory where application files are
 * stored: templates, configuration files, static data and other source
 * files. If the application doesn't define it, we set to to "appfiles".
 * The directory must be secured in a way that its contents can't be
 * accessed through HTTP.
 */
if( !defined( 'APP_DIR' ) ) {
	define( 'APP_DIR', 'appfiles/' );
}

if( !defined( 'WRITE_DIR' ) ) {
	define( 'WRITE_DIR', APP_DIR.'tmp/' );
}

/*
 * SITE_ROOT is actually a prefix to add to all site URLs. Most times
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

/*
 * We need to know the hostname to create URLs. It is in the HTTP_HOST
 * header. If it is not there, then we are most likely dealing with some
 * shady script making queries because all web browsers and most bots
 * support HTTP 1.1 nowadays.
 */
if( !isset( $_SERVER['HTTP_HOST'] ) )
{
	$s = date( 'Y.m.d H:i:s' )
		."\tNo host given in the request"
		."\t".USER_AGENT
		."\t".$_SERVER['REMOTE_ADDR']
		."\t".$_SERVER['REQUEST_URI'];
	error_log( $s );
	error_bad_request();
}

define( 'SITE_DOMAIN', SITE_PROTOCOL.'://'.$_SERVER['HTTP_HOST'] );
define( 'CURRENT_URL', SITE_DOMAIN.$_SERVER['REQUEST_URI'] );
define( '_PATH', dirname(__FILE__).'/' );

mb_internal_encoding( 'UTF-8' );
date_default_timezone_set( 'UTC' );

require _PATH.'core/libs.php';
require _PATH.'core/files.php';
require _PATH.'core/functions.php';
require _PATH.'core/http.php';
require _PATH.'core/lang.php';
require _PATH.'core/logs.php';
require _PATH.'core/req_url.php';
require _PATH.'core/settings.php';
require _PATH.'core/top.php';
require _PATH.'core/user.php';
require _PATH.'core/vars.php';

require _PATH.'subservers/pages.php';
require _PATH.'subservers/actions.php';

require _PATH.'ext/snippets.php';
require _PATH.'ext/table.php';

/*
 * PHP errors handler.
 */
function _error( $type, $msg, $file, $line, $context ) {
	error( "$msg -- $file:$line" );
	error_server();
}
set_error_handler( '_error' );

add_classes_dir( APP_DIR.'classes' );
if( file_exists( APP_DIR.'init.php' ) ) {
	require APP_DIR.'init.php';
}

h2::process( CURRENT_URL );
?>

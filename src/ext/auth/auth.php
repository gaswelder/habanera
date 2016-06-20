<?php
/*
 * Performs basic HTTP autorization. $realm is the "realm" name
 * according to the protocol, $callback is a function($name, $password)
 * that must return true if the name and password are valid.
 * The function call should be placed at the top of the script. If the
 * user is authorized successfully, it will return and let the reset of
 * the script be executed, otherwise it will stop with 401 error.
 */
function basic_auth( $realm, $callback )
{
	$h = req_header( "Authorization" );
	if( $h && strpos( $h, 'Basic ' ) === 0 ) {
		$val = ltrim( substr( $h, strlen( 'Basic ' ) ) );
		$val = base64_decode( $val );
		list( $name, $pass ) = explode( ':', $val, 2 );
		if( $callback( $name, $pass ) ) {
			return;
		}
	}

	header( sprintf( "WWW-Authenticate: Basic realm=\"%s\"",
		str_replace( '"', '\\"', $realm ) ) );
	http_w::show_error( 401 );
}
?>

<?php

/*
 * Makes a redirect to the given URL.
 * The URL must be full.
 */
function redirect( $url, $code = 302 ) {
	http_w::show_status( $code );
	header( "Location: ".$url );
	exit;
}

function error_bad_request() {
	http_w::show_error( '400' );
}

function error_forbidden() {
	http_w::show_error( '403' );
}

function error_notfound() {
	http_w::show_error( '404' );
}

function error_gone() {
	http_w::show_error( '410' );
}

function error_server() {
	http_w::show_error( '500' );
}

function announce_json() {
	header( "Content-Type: application/json; charset=UTF-8" );
}

function announce_txt( $charset = 'UTF-8' ) {
	header( "Content-Type: text/plain; charset=$charset" );
}

function announce_html( $charset = 'UTF-8' ) {
	header( "Content-Type: text/html; charset=$charset" );
}

function announce_file( $filename, $size = null )
{
	$ext = ext( $filename );
	$type = _mime::type( $ext );
	if( !$type ) {
		warning( "Unknown MIME type for '$filename'" );
		$type = 'application/octet-stream';
	}

	header( 'Content-Type: '.$type );
	header( 'Content-Disposition: attachment;filename="'.$filename.'"');
	if( $size ) {
		header( 'Content-Length: '.$size );
	}
}

function http_status( $code ) {
	http_w::show_status( $code );
}

function req_header( $name ) {
	$h = getallheaders();
	if( isset( $h[$name] ) ) {
		return $h[$name];
	}
	return null;
}

class http_w
{
	/*
	 * All error pages are stored in the known location. If the page
	 * for the given error exists there, it is returned.
	 */
	private static function get_error_page( $errno )
	{
		$path = APP_DIR . "error-pages/$errno.htm";
		if( file_exists( $path ) ) {
			return file_get_contents( $path );
		}
		else {
			return null;
		}
	}

	/*
	 * Ouput an error header and error page (or message) for the
	 * HTTP error with code $errno.
	 */
	static function show_error( $errno )
	{
		error_log( "HTTP error $errno	" . current_url() );
		$s = self::get_error_page( $errno );
		if( !$s ) {
			$s = "Error $errno";
		}
		ob_destroy();
		self::show_status( $errno );
		echo $s;
		exit;
	}

	static function show_status( $code )
	{
		$codes = array(
			'200' => 'OK',
			'201' => 'Created',
			'202' => 'Accepted',
			'301' => 'Moved Permanently',
			'302' => 'Found',
			'303' => 'See Other',
			'304' => 'Not Modified',
			'400' => 'Bad Request',
			'403' => 'Forbidden',
			'404' => 'Not Found',
			'405' => 'Method Not Allowed',
			'406' => 'Not Acceptable',
			'410' => 'Gone',
			'500' => 'Internal Server Error',
			'503' => 'Service Unavailable'
		);

		/*
		 * If code number is unknown, resort to internal error.
		 */
		if( !isset( $codes[$code] ) ) {
			error( "Unknown HTTP error number: $code" );
		}

		$str = $codes[$code];
		header( "$_SERVER[SERVER_PROTOCOL] $code $str" );
	}
}

?>

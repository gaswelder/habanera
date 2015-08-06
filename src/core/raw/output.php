<?php
/*
 * Functions dealing with output to the browser and redirects.
 */

/*
 * Discards all previously created buffers.
 */
function ob_destroy()
{
	while( ob_get_level() ){
		ob_end_clean();
	}
}

/*
 * Cleans the output, dumps the given variable and stops the script.
 */
function e( $var )
{
	ob_destroy();
	$vars = func_get_args();
	foreach( $vars as $var ){
		var_dump( $var );
	}
	exit;
}

/*
 * Makes a redirect to the given URL. The URL should be full.
 */
function redirect( $url ) {
	error_log( "Redirect: $url" );
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

function announce_json( $charset = 'UTF-8' ){
	header( "Content-Type: application/json; charset=$charset" );
}

function announce_txt( $charset = 'UTF-8' ){
	header( "Content-Type: text/plain; charset=$charset" );
}

function announce_html( $charset = 'UTF-8' ){
	header( "Content-Type: text/html; charset=$charset" );
}

function announce_file( $filename, $size = null )
{
	$types = array(
		'.xls' => 'application/vnd.ms-excel',
		'.xlsx' => 'application/vnd.openxmlformats-officedocument'.
			'.spreadsheetml.sheet',
		'.zip' => 'application/zip'
	);
	$ext = ext( $filename );
	if( isset( $types[$ext] ) ) {
		$type = $types[$ext];
	}
	else {
		warning( "Unknown MIME type for '$filename'" );
		$type = 'application/octet-stream';
	}

	header( 'Content-Type: '.$type );
	header( 'Content-Disposition: attachment;filename="'.$filename.'"');
	if( $size ) {
		header( 'Content-Length: '.$size );
	}
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
		/*
		 * 401 is excluded because it requires some nontrivial actions
		 * from us.
		 */
		$errors = array(
			'400' => 'Bad Request',
			'403' => 'Forbidden',
			'404' => 'Not Found',
			'410' => 'Gone',
			'500' => 'Internal Server Error'
		);

		/*
		 * If error number is unknown, resort to internal error.
		 */
		if( !isset( $errors[$errno] ) ) {
			warning( "Unknown HTTP error number: $errno" );
			$errno = '500';
		}

		$errstr = $errors[$errno];

		$s = self::get_error_page( $errno );
		if( !$s ) $s = $errstr;

		error_log( sprintf( "%s: %s -- %s -- %s",
			$errstr, $_SERVER['REQUEST_URI'],
			USER_AGENT, $_SERVER['REMOTE_ADDR'] )
		);

		ob_destroy();

		header( "$_SERVER[SERVER_PROTOCOL] $errno $errstr" );
		echo $s;
		exit;
	}

}

?>

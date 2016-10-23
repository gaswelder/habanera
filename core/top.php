<?php

/*
 * Set a function to preprocess the URL before calling subservers.
 */
function set_url_proc( $func ) {
	h2::set_url_proc( $func );
}

/*
 * Register a subserver: a function which takes a req_url object and
 * returns true if it has served the content.
 */
function add_subserver( $func ) {
	h2::add_subserver( $func );
}

function arg($i) {
	return h2::argv($i);
}

function poparg() {
	return h2::poparg();
}

function current_url() {
	return h2::url();
}

function limit_args($min = 0, $max = 0)
{
	$n = 0;
	while( arg( $n ) !== null && arg( $n ) !== '' ) {
		$n++;
	}

	if( $n < $min || $n > $max ) {
		error_notfound();
	}
}

class h2
{
	private static $appdir;

	private static $url = null;
	private static $domain = null;
	private static $req = null;
	private static $base = null;

	private static $preprocess_func = null;
	private static $serve_functions = array();

	static function set_url_proc( $func )
	{
		if( self::$preprocess_func != null ) {
			error( "URL preprocess function is already registered." );
			return;
		}
		if( !is_callable( $func ) ) {
			error( "URL preprocess function is not callable" );
			return;
		}
		self::$preprocess_func = $func;
	}

	static function add_subserver( $func )
	{
		if( !is_callable( $func ) ) {
			error( "Given subserver function is not callable" );
			return false;
		}
		array_unshift( self::$serve_functions, $func );
		return true;
	}

	static function appdir() {
		return self::$appdir;
	}

	static function url() {
		return self::$url;
	}

	static function domain() {
		return self::$domain;
	}

	static function base() {
		$b = self::$domain;
		if( self::$base ) {
			$b .= '/' . self::$base;
		}
		return $b;
	}

	static function prefix() {
		return self::$domain . self::$req->prefix();
	}

	static function argv($i) {
		return self::$req->arg($i);
	}

	static function poparg()
	{
		$arg = self::$req->arg(0);
		if( $arg === null ) {
			return null;
		}
		self::$req->omit();
		return $arg;
	}

	/*
	 * Main function of the whole script.
	 */
	static function main( $appdir, $base )
	{
		if( !is_dir( $appdir ) ) {
			error( "No directory '$appdir'" );
			return false;
		}

		self::$appdir = realpath( $appdir ) . '/';

		/*
		 * WRITE_DIR is a directory in which the script will be writing some
		 * working files like cache files or logs. It must not be accessible
		 * through HTTP.
		 */
		if( !defined( 'WRITE_DIR' ) ) {
			define( 'WRITE_DIR', self::$appdir.'tmp/' );
		}

		/*
		 * Remove trailing slash from the base path.
		 */
		if( substr( $base, -1 ) == "/" ) {
			$base = substr( $base, 0, -1 );
		}

		self::$base = $base;

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

		/*
		 * Reconstruct the requested URL.
		 */
		if( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) {
			$protocol = "https";
		}
		else {
			$protocol = "http";
		}
		$domain = $protocol.'://'.$_SERVER['HTTP_HOST'];
		self::$domain = $domain;
		self::$url = $domain . $_SERVER['REQUEST_URI'];

		/*
		 * Parse and check the URL.
		 */
		$req = new req_url( self::$url );
		self::$req = $req;

		if( !self::check_url( $req ) ) {
			error_log( "Bad URL: " . self::$url );
			error_bad_request();
		}

		if( $base != "" && self::poparg() != $base ) {
			trigger_error( "Expected URL starting with $base, got $url" );
			return false;
		}

		mb_internal_encoding( 'UTF-8' );

		add_classes_dir( self::$appdir.'classes' );
		if( file_exists( self::$appdir.'init.php' ) ) {
			require self::$appdir.'init.php';
		}

		load_ext( 'snippets' );

		/*
		 * Run the URL preprocessing function, if specified.
		 * There is intentionally only one function because the
		 * preprocessing is a global decision.
		 */
		if( self::$preprocess_func ) {
			call_user_func( self::$preprocess_func, $req );
		}

		if( !self::serve_content( $req ) ) {
			error_notfound();
		}
	}

	/*
	 * Returns true is the URL doesn't have any parts that might cause
	 * problems.
	 */
	private static function check_url( $req )
	{
		$n = $req->argsnum();
		for( $i = 0; $i < $n; $i++ )
		{
			$part = $req->arg($i);
			if( trim($part) === '' && $i != $n-1 ) {
				return false;
			}

			if( $part != '' && $part[0] == '.' ) {
				return false;
			}
		}
		return true;
	}

	/*
	 * Serve the content for the given URL.
	 */
	private static function serve_content( $req )
	{
		/*
		 * Call all subservers until one of them returns true.
		 */
		foreach( self::$serve_functions as $f ) {
			if( call_user_func( $f, $req ) ) {
				return true;
			}
		}
		return false;
	}
}

?>

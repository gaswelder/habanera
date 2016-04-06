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

function argv($i) {
	return h2::argv($i);
}

class h2
{
	private static $req = null;
	private static $preprocess_func = null;
	/*
	 * Entry functions for registered "subservers".
	 */
	private static $serve_functions = array();

	/*
	 * Serve content for the current URL.
	 */
	static function process()
	{
		$url = CURRENT_URL;
		$req = new req_url( $url );
		self::$req = $req;

		if( !self::check_url( $req ) ) {
			error_log( "Bad URL: $url" );
			error_bad_request();
		}

		self::preprocess_url( $req );

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
	 * Preprocessing is made before the URL is given to any of the
	 * subservers. This step may have redirects, error triggers or
	 * URL manipulation.
	 */
	static function preprocess_url( req_url $url )
	{
		/*
		 * There is intentionally only one function because the
		 * preprocessing is a global decision.
		 */
		if( !self::$preprocess_func ) {
			return;
		}
		call_user_func( self::$preprocess_func, $url );
	}

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

	static function add_subserver( $func )
	{
		if( !is_callable( $func ) ) {
			error( "Given subserver function is not callable" );
			return false;
		}
		array_unshift( self::$serve_functions, $func );
		return true;
	}

	static function argv($i) {
		return self::$req->arg($i);
	}

}

?>

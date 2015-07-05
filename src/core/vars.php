<?php
class vars
{
	private static $init = false;
	private static $post = null;
	private static $get = null;

	static function get( $key )
	{
		self::init();
		if( array_key_exists( $key, self::$get ) ) {
			return self::$get[$key];
		}
		else {
			return null;
		}
	}

	static function post( $key )
	{
		self::init();
		if( array_key_exists( $key, self::$post ) ) {
			return self::$post[$key];
		}
		else {
			return null;
		}
	}

	static function posts( $_keys_ )
	{
		$keys = func_get_args();
		$data = array();
		foreach( $keys as $k ) {
			$data[$k] = self::post( $k );
		}
		return $data;
	}

	private static function init()
	{
		if( self::$init ) return;
		self::$init = true;

		self::$post = array();
		self::$get = array();

		$mq = get_magic_quotes_gpc();
		foreach( $_POST as $k => $v )
		{
			if( $mq ) {
				$k = stripslashes( $k );
				$v = self::recurse( $v, 'stripslashes' );
			}
			self::$post[$k] = $v;
		}

		// TODO: do these need urldecode?
		foreach( $_GET as $k => $v )
		{
			if( $mq ) {
				$k = stripslashes( $k );
				$v = self::recurse( $v, 'stripslashes' );
			}
			self::$get[$k] = $v;
		}
	}

	private static function recurse( $value, $func )
	{
		if( is_array( $value ) ) {
			return array_map( $func, $value );
		}
		else {
			return $func( $value );
		}
	}
}

?>

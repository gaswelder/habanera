<?php
/*
 * Session interface for storing authentication results and other data.
 */
class user
{
	/*
	 * Since we share a global storage, we localize our data by adding
	 * a prefix, so saving data with key "key" is really saving it with
	 * key KEY_PREFIX/$type/key.
	 */
	const KEY_PREFIX = '_userdata_';
	private static $type = 'guest';

	/*
	 * Returns actual session data key for the given user key.
	 */
	private static function prefix( $key ) {
		return self::KEY_PREFIX . '/' . self::$type . '/' . $key;
	}

	/*
	 * Switches to another user type. If the user was not authenticated
	 * for that type, all data values will be null.
	 */
	static function select( $type )
	{
		if( !self::type_valid( $type ) ) {
			trigger_error( "Invalid type name" );
			return;
		}
		self::$type = $type;
	}

	/*
	 * Tells whether the type name is valid.
	 */
	private static function type_valid( $type )
	{
		/*
		 * Type names must be non-empty strings without the slash
		 * character since we use it to build session keys.
		 */
		return ( is_string( $type ) && $type != ''
			&& strpos( $type, '/' ) === false );
	}

	/*
	 * Sets the user type and identifier. All previous data is cleaned.
	 */
	static function auth( $type, $id = null )
	{
		self::sclean();
		self::sset( 'type', $type );
		if( $type === null ) {
			return;
		}

		if( !self::type_valid( $type ) ) {
			trigger_error( "Invalid type name: $type" );
			return;
		}
		self::sset( 'id', $id );
	}

	/*
	 * Returns user type set with the "authorise" function.
	 */
	static function type() {
		return self::sget( 'type' );
	}

	/*
	 * Returns user identifier set with the "authorise" function.
	 */
	static function id() {
		return self::sget( 'id' );
	}

	/*
	 * Store arbitrary key-value pair.
	 */
	static function set( $key, $value ) {
		self::sset( 'data-'.$key, $value );
	}

	/*
	 * Retrieve arbitrary key-value pair.
	 */
	static function get( $key ) {
		return self::sget( 'data-'.$key );
	}

	/*
	 * Initializes the session if needed.
	 * Returns a reference to the $_SESSION superglobal.
	 */
	private static function &s()
	{
		if( !isset( $_SESSION )  ) {
			session_start();
		}
		return $_SESSION;
	}

	/*
	 * Save a key-value pair. $value set to null means "delete".
	 */
	private static function sset( $key, $value )
	{
		$key = self::prefix( $key );
		$s = &self::s();
		if( $value === null ) {
			unset( $s[$key] );
		} else {
			$s[$key] = $value;
		}
	}

	/*
	 * Returns the value stored with the given key. Returns $default if
	 * the value is not set.
	 */
	private static function sget( $key, $default = null )
	{
		$key = self::prefix( $key );
		$s = &self::s();
		if( !isset( $s[$key] ) ){
			return $default;
		} else {
			return $s[$key];
		}
	}

	/*
	 * Unsets all data that has been set by this module.
	 */
	private static function sclean()
	{
		$s = &self::s();
		$pref = self::prefix( '' );
		foreach( $s as $k => $v )
		{
			if( strpos( $k, $pref ) === 0 ) {
				unset( $s[$k] );
			}
		}
	}
}

?>

<?php
/*
 * Session interface for storing authentication results and other data.
 */
class user
{
	/*
	 * Since we share a global storage, we localise our data under a
	 * single key.
	 */
	const KEY_PREFIX = '_userdata_';
	/*
	 * The key is used as a prefix, so saving data under key "key" is
	 * really saving it under key "KEY_PREFIX"+"key".
	 */

	/*
	 * Set the user type and identifier. All previous data is cleaned.
	 */
	static function auth( $type, $id = null )
	{
		self::sclean();
		self::sset( 'type', $type );
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
		$key = self::KEY_PREFIX . $key;
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
		$key = self::KEY_PREFIX . $key;
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
		foreach( $s as $k => $v )
		{
			if( strpos( self::KEY_PREFIX, $k ) === 0 ) {
				unset( $s[$k] );
			}
		}
	}

}

?>

<?php

function setting( $name, $default = null ) {
	return settings::get( $name, $default );
}

function debug() {
	return setting( 'debug' );
}

class settings
{
	/*
	 * Values container.
	 */
	private static $data = null;

	/*
	 * Gets a value.
	 */
	static function get( $key, $default = null )
	{
		if( self::$data === null ){
			self::init();
		}
		if( isset( self::$data[$key] ) ){
			return self::$data[$key];
		} else {
			return $default;
		}
	}

	/*
	 * Reads the settings.ini file. Also checks whether
	 * "settings-<hostname>.ini" file exists and reads it too.
	 */
	private static function init()
	{
		if( self::$data ) return;

		self::$data = array();

		/*
		 * Load the default settings file, if it is present, and then
		 * load all settings specific to the host.
		 */
		self::load( 'settings.ini' );
		self::load_specifics();
	}

	/*
	 * Host-specific file name has form
	 * "settings.<host name postfix>.ini".
	 * For host "foo.example.com" these files will be loaded
	 * (if present) in this sequence:
	 * settings.com.ini, settings.com.example.ini,
	 * settings.com.example.foo.ini.
	 */
	private static function load_specifics()
	{
		$parts = array_reverse( explode( '.', $_SERVER['HTTP_HOST'] ) );
		$name = 'settings';
		foreach( $parts as $part ) {
			$name .= ".$part";
			self::load( "$name.ini" );
		}
	}

	private static function load( $path )
	{
		$path = APP_DIR.$path;
		if( !file_exists( $path ) ) {
			return false;
		}
		self::$data = array_merge( self::$data, parse_ini_file( $path ) );
		return true;
	}
}

?>

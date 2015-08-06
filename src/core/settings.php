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

		$path = APP_DIR.'settings.ini';
		if( file_exists( $path ) ) {
			self::$data = array_merge( self::$data, parse_ini_file( $path ) );
		}

		$h = strtolower( php_uname( 'n' ) );
		$path = APP_DIR.'settings-'.$h.'.ini';
		if( file_exists( $path ) ) {
			self::$data = array_merge( self::$data, parse_ini_file( $path ) );
		}
	}
}

?>

<?php

function t( $text ) {
	return lang::lookup( $text );
}

lang::init();

/*
 * Gettext turned out to be too complicated and unreliable on one
 * hosting. This is a simpler alternative.
 */
class lang
{
	private static $lang = null;
	private static $dicts = array();

	/*
	 * When the extension is included, set the default language from
	 * the settings.
	 */
	static function init()
	{
		$lang = setting( 'lang' );
		if( !$lang ) return;
		if( !self::valid( $lang ) ) {
			error( "Invalid language id in settings: '$lang'" );
			return;
		}
		self::$lang = $lang;
	}

	/*
	 * Returns true if there is a file for the given language.
	 */
	static function have( $lang )
	{
		if( !self::valid( $lang ) ) {
			error( "Invalid language id: '$lang'" );
			return false;
		}
		$path = self::path( $lang );
		return file_exists( $path );
	}

	static function set( $lang )
	{
		if( !self::valid( $lang ) ) {
			error( "Invalid language id: '$lang'" );
			return;
		}
		self::$lang = $lang;
	}

	static function get() {
		return self::$lang;
	}

	static function lookup( $msgid, $lang = null )
	{
		if( $lang && !self::valid( $lang ) ) {
			error( "Invalid language id: '$lang'" );
			return;
		}
		if( !$lang ) {
			$lang = self::$lang;
		}
		if( !$lang ) {
			return $msgid;
		}

		if( !isset( self::$dicts[$lang] ) ) {
			self::load_dict( $lang );
		}

		if( array_key_exists( $msgid, self::$dicts[$lang] ) ) {
			return self::$dicts[$lang][$msgid];
		}

		return $msgid;
	}

	private static function load_dict( $lang )
	{
		$path = self::path( $lang );
		if( file_exists( $path ) ) {
			$dict = self::parse( $path );
		}
		else {
			$dict = array();
		}
		self::$dicts[$lang] = $dict;
	}

	/*
	 * Returns true if the given language identifier is valid.
	 */
	private static function valid( $lang )
	{
		/*
		 * The form of HTTP 'accept-language' token:
		 * 1*8ALPHA *( "-" 1*8ALPHA)
		 * Valid examples are "havaho-funky-dialect" and "en-US".
		 */
		$alpha8 = '[a-zA-Z]{1,8}';
		return preg_match( "/$alpha8(-$alpha8)*/", $lang );
	}

	/*
	 * Parses the language file with the given path and returns the
	 * dictionary.
	 */
	private static function parse( $path )
	{
		$dict = array();
		$lines = array_map( 'trim', file( $path ) );
		$n = count( $lines );

		$i = 0;
		while( $i < $n - 1 )
		{
			$msgid = $lines[$i++];
			$text = $lines[$i++];
			$dict[$msgid] = $text;

			if( $i >= $n ) break;

			if( $lines[$i++] ) {
				warning( "Empty line expected at file $path, line ".($i+1) );
				break;
			}
		}
		return $dict;
	}

	/*
	 * Returns path for the given language file.
	 */
	private static function path( $lang ) {
		$path = APP_DIR . 'lang/' . strtolower( $lang );
	}
}

?>

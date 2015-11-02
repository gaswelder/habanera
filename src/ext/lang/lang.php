<?php

function set_default_language( $lang ) {
	return lang::set_default_language( $lang );
}

function get_default_language() {
	return lang::get_default_language();
}

function t( $text ) {
	return lang::get_message( $text );
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
	static function init() {
		self::$lang = setting( 'lang' );
	}

	static function set_default_language( $lang ) {
		self::$lang = $lang;
	}

	static function get_default_language() {
		return self::$lang;
	}

	static function get_message( $msgid, $lang = null )
	{
		if( !$lang ) $lang = self::$lang;
		if( !$lang ) {
			return $msgid;
		}

		if( !isset( self::$dicts[$lang] ) ) {
			self::load_dict( $lang );
		}

		if( array_key_exists( $msgid, self::$dicts[$lang] ) ) {
			return self::$dicts[$lang][$msgid];
		}
		else {
			return $msgid;
		}
	}

	private static function load_dict( $lang )
	{
		/*
		 * Someone could pass $lang='../{...}../etc/whatever', so we
		 * ensure that $lang can have only letters and '_'.
		 */
		if( preg_match( '/[^a-z_]/', $lang ) ) {
			warning( "Invalid language requested: '$lang'." );
			self::$dicts[$lang] = array();
			return;
		}

		$dict = array();
		$path = APP_DIR . 'lang/' . $lang;
		if( file_exists( $path ) )
		{
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
		}
		self::$dicts[$lang] = $dict;
	}
}

?>

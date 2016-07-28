<?php

/*
 * Writes a message to the given log.
 */
function log_message( $message ) {
	return h2_logs::log( $message );
}

/*
 * Write log messages on disk at the end of the script.
 */
register_shutdown_function( 'h2_logs::flush' );

class h2_logs
{
	/*
	 * Buffer for messages.
	 */
	private static $buf = '';

	static function log( $msg )
	{
		/*
		 * Compose the log line.
		 */
		$cols = array(
			date( 'd.m.Y H:i:s' ),
			self::usertag(),
			$msg,
			current_url(),
			req_header( 'User-Agent' )
		);
		$out = implode( "\t", $cols ) . PHP_EOL;

		/*
		 * Put the line to the buffer.
		 */
		self::$buf .= $out;
	}

	/*
	 * Returns a string tag describing the user identity.
	 */
	private static function usertag()
	{
		$tag = user::type();
		if( !$tag ) $tag = 'nobody';

		$id = user::id();
		if( $id ) $tag .= "#$id";

		$tag .= '@' . $_SERVER['REMOTE_ADDR'];
		return $tag;
	}

	static function flush()
	{
		if( self::$buf == '' ) return;
		files::append( '', "log.log", self::$buf );
		self::$buf = '';
	}
}

?>

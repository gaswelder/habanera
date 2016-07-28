<?php

/*
 * Writes a message to the given log.
 */
function log_message( $message, $logname = 'log' ) {
	return h2_logs::log( $message, $logname );
}

/*
 * A shortcut for debug messages.
 */
function msg( $message ) {
	log_message( $message, 'debug' );
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
	private static $buf = array();

	static function log( $msg, $logname = 'log' )
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
		if( !isset( self::$buf[$logname] ) ) {
			self::$buf[$logname] = $out;
		}
		else {
			self::$buf[$logname] .= $out;
		}
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
		foreach( self::$buf as $logname => $lines ) {
			files::append( 'logs', "$logname.log", $lines );
		}
		self::$buf = array();
	}
}

?>

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


register_shutdown_function( 'h2_logs::flush' );

class h2_logs
{
	private static $buf = array();

	static function log( $msg, $logname = 'log' )
	{
		$id = user::get_id();
		$type = user::get_type();
		if( !$type ) {
			$type = 'nobody';
		}

		$cols = array(
			date( 'd.m.Y H:i:s' ),
			$msg,
			"($type#$id)",
			$_SERVER['REMOTE_ADDR'],
			req_header( 'User-Agent' )
		);
		$out = implode( "\t", $cols ) . PHP_EOL;

		if( !isset( self::$buf[$logname] ) ) {
			self::$buf[$logname] = $out;
		}
		else {
			self::$buf[$logname] .= $out;
		}
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

<?php
/*
 * Centralised error handling.
 */

set_error_handler( '_error_handlers::on_error', -1 );

/*
 * Add a function to be called on error. The function will be called
 * with arguments: $message, $source.
 */
function on_error( $func ) {
	_error_handlers::add( $func );
}

function warning( $message ) {
	if( debug() ) {
		error( $message );
	}
	log_message( 'Warning: '.$message, 'errors' );
}

function error( $message ) {
	log_message( 'Error: '.$message, 'errors' );
	trigger_error( $message );
}

class _error_handlers
{
	private static $F = array();

	static function on_error( $type, $msg, $file, $line, $context )
	{
		if( !error_reporting() ) {
			return false;
		}

		foreach( self::$F as $f ) {
			if( call_user_func( $f, $msg, "$file:$line" ) === true ) {
				return true;
			}
		}

		self::default_func( $msg, $file, $line );
	}

	private static function default_func( $msg, $file, $line )
	{
		$a = array(
			date( 'Y.m.d H:i:s' ),
			"$msg at $file:$line",
			USER_AGENT,
			$_SERVER['REMOTE_ADDR'],
			$_SERVER['REQUEST_URI']
		);
		error_log( implode( "\t", $a ) );
		error_server();
	}

	static function add( $func ) {
		self::$F[] = $func;
	}
}

?>

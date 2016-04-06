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
	else {
		log_message( 'Warning: '.$message, 'errors' );
	}
}

function error( $message ) {
	trigger_error( $message );
}

class _error_handlers
{
	private static $F = array();

	static function add( $func ) {
		array_unshift( self::$F, $func );
	}

	static function on_error( $type, $msg, $file, $line, $context )
	{
		if( !error_reporting() ) {
			return false;
		}

		self::log_error( $msg, $file, $line );

		foreach( self::$F as $f ) {
			if( call_user_func( $f, $msg, "$file:$line" ) === true ) {
				return true;
			}
		}
		error_server();
	}

	private static function log_error( $msg, $file, $line )
	{
		$str = implode( "\t", array(
			"$msg at $file:$line",
			$_SERVER['REQUEST_URI'],
			USER_AGENT,
			$_SERVER['REMOTE_ADDR']
		));
		error_log( $str );
		log_message( $str, 'errors' );
	}
}

?>

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
	log_message( 'Warning: '.$message );
	if( debug() ) {
		error( $message );
	}
}

function error( $message ) {
	trigger_error( $message, E_USER_ERROR );
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

		log_message( "Error: $msg at $file:$line" );

		foreach( self::$F as $f ) {
			if( call_user_func( $f, $msg, "$file:$line" ) === true ) {
				return true;
			}
		}
		error_server();
	}
}

?>

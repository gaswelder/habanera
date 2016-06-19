<?php
/*
 * Writes a message to the given log.
 */
function log_message( $message, $logname = 'log' )
{
	$id = user::get_id();
	$type = user::get_type();
	if( !$type ) {
		$type = 'nobody';
	}

	$cols = array(
		date( 'd.m.Y H:i:s' ),
		$message,
		"($type#$id)",
		$_SERVER['REMOTE_ADDR'],
		req_header( 'User-Agent' )
	);
	$out = implode( "\t", $cols ) . PHP_EOL;
	return files::append( 'logs', "$logname.log", $out );
}

/*
 * A shortcut for debug messages.
 */
function msg( $message ) {
	log_message( $message, 'debug' );
}

?>

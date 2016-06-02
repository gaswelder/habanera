<?php
/*
 * Writes a message to the given log.
 */
function log_message( $message, $logname = 'log' ) {
	$cols = array(
		date( 'd.m.Y H:i:s' ),
		$_SERVER['REMOTE_ADDR'],
		$message,
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

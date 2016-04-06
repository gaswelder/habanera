<?php
/*
 * Writes a message to the given log.
 */
function log_message( $message, $logname ) {
	$out = date( 'd.m.Y H:i:s' ) . "\t"	. $message.PHP_EOL;
	return files::append( 'logs', "$logname.log", $out );
}

/*
 * A shortcut for debug messages.
 */
function msg( $message ) {
	log_message( $message, 'debug' );
}

?>

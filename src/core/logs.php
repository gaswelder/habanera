<?php
/*
 * Writes a message to the given log. Empty logname means "ignore".
 */
function log_message( $message, $logname ) {
	logs::write( $message, $logname );
}
/*
 * A shortcut for debug messages.
 */
function msg( $message ) {
	logs::write( $message, 'debug' );
}

class logs
{
	static function write( $message, $logname )
	{
		if( !$logname ) return;
		$out = date( 'd.m.Y H:i:s' ) . "\t"	. $message.PHP_EOL;
		return files::append( 'logs', "$logname.log", $out );
	}

	private static function id()
	{
		static $id = null;
		if( !$id ) {
			$id = sprintf( '%d-%03d', time() % 300, rand( 1, 999 ) );
		}
		return $id;
	}
}

?>

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
		if( setting( 'debug' ) != '1' ) {
			error_server();
		}

		ob_destroy();
		echo "<samp>$msg</samp> at <code>$file:$line</code>";
		self::print_backtrace();
		exit;
	}

	private static function print_backtrace()
	{
		$a = array_reverse( debug_backtrace() );
		$table = array();
		foreach( $a as $r )
		{
			$table[] = array(
				'<code><b>'.self::format_call( $r ).'</b></code>',
				'<code>'.self::format_src( $r ).'</code>'
			);
		}
		$s = '<table>';
		foreach( $table as $row ) {
			$s .= '<tr><td>'.implode( '</td><td>', $row )
				. '</td></tr>';
		}
		$s .= '</table>';
		echo $s;
	}

	private static function format_call( $r )
	{
		$f = '';
		if( isset( $r['class'] ) ) {
			$f .= "$r[class]$r[type]";
		}
		$f .= $r['function'];
		$f .= "(...)";
		return $f;
	}

	private static function format_src( $r )
	{
		if( !isset( $r['file'] ) ) {
			return '';
		}
		return "$r[file]:$r[line]";
	}

	static function add( $func ) {
		self::$F[] = $func;
	}
}

?>

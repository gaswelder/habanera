<?php

if( setting( 'debug' ) == 1 )
{
	on_error( 'ext_debug::on_error' );
}

class ext_debug
{
	static function on_error( $msg, $line )
	{
		ob_destroy();
		echo "<samp>$msg</samp> at <code>$line</code>";
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
		$f .= '(' . self::format_args( $r['args'] ) . ')';
		return $f;
	}

	private static function format_args( $args ) {
		$parts = array();
		foreach( $args as $arg )
		{
			if( !is_scalar( $arg ) ) {
				$parts[] = gettype( $arg );
				continue;
			}

			if( is_string( $arg ) ) {
				if( mb_strlen( $arg ) > 20 ) {
					$arg = mb_substr( $arg, 0, 17 ) . '...';
				}
				$arg = "'" . $arg . "'";
			}
			$parts[] = $arg;
		}
		return implode( ', ', $parts );
	}

	private static function format_src( $r )
	{
		if( !isset( $r['file'] ) ) {
			return '';
		}
		return "$r[file]:$r[line]";
	}
}

?>

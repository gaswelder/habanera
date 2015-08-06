<?php
/*
 * Represents URL as a sequence of steps.
 */
class req_url
{
	/*
	 * Array of path parts.
	 */
	private $args;


	/*
	 * We might need to omit parts of the URL. For example, path like
	 * '/en/some/path' might be transformed into '/some/path' with
	 * current language set to 'en'. But we will need the omitted parts
	 * later to construct full URLs.
	 */
	/*
	 * Number of omitted parts. Omitted parts are
	 * ignored by all functions except '__toString'.
	 */
	private $omitted = 0;


	function __construct( $url )
	{
		$path = parse_url( $url, PHP_URL_PATH );
		$this->args = array_slice( explode( '/', $path ), 1 );
	}


	/*
	 * "Omit" 'n' more parts of the URL.
	 */
	function omit( $n = 1 )
	{
		if( $this->omitted + $n > count($this->args) ) {
			trigger_error( "Can't omit($n)" );
			return;
		}
		$this->omitted += $n;
	}

	/*
	 * Returns i-th part of the URL. Omitted parts are not taken into
	 * account.
	 */
	function arg( $i )
	{
		$i += $this->omitted;
		if( $i >= count($this->args) ) {
			return null;
		}
		return urldecode( $this->args[$i] );
	}

	function argsnum() {
		return count($this->args) - $this->omitted;
	}

	/*
	 * Returns the omitted part.
	 */
	function prefix()
	{
		if( !$this->omitted ) {
			return '/';
		}

		$part = array_slice($this->args, 0, $this->omitted);
		return '/' . implode( '/', $part ) . '/';
	}

	function join() {
		$part = array_slice($this->args, $this->omitted);
		return '/' . implode('/', $part);
	}

	function __toString() {
		return '/' . implode( '/', $this->args );
	}

}
?>

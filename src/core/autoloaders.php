<?php
/*
 * Class loader.
 */

spl_autoload_register( 'autoloaders::seek' );

/*
 * Adds a classes directory to the search list.
 */
function add_classes_dir( $dir ) {
	autoloaders::add_dir( $dir );
}

class autoloaders
{
	private static $dirs = array();

	static function add_dir( $path ) {
		self::$dirs[] = $path;
	}

	static function seek( $class )
	{
		$name = strtolower( $class ).'.php';
		foreach( self::$dirs as $path )
		{
			$p = $path.'/'.$name;
			if( file_exists( $p ) )
			{
				include $p;
				return;
			}
		}
	}
}

?>

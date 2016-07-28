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

function load_ext( $names__ )
{
	$args = func_get_args();
	foreach( $args as $name )
	{
		$path = _PATH . 'ext/'.$name;
		if( !is_dir( $path ) ) {
			error( "No extension '$name' ($path)" );
		}
		require_dir( $path );
	}
}

/*
 * Load a library from the "lib" directory inside appdir.
 */
function lib( $names__ ) {
	$args = func_get_args();
	foreach( $args as $name ) {
		require h2::appdir()."lib/$name.php";
	}
}

/*
 * Include all PHP files in the given directory.
 */
function require_dir( $path )
{
	if( !is_dir( $path ) ) {
		trigger_error( "No dir '$path'" );
		return;
	}

	$dir = opendir( $path );
	while( $fn = readdir( $dir ) )
	{
		if( $fn[0] == '.' ) continue;
		if( substr( $fn, -4 ) == '.php' ) {
			require "$path/$fn";
		}
	}
	closedir( $dir );
}

class autoloaders
{
	private static $dirs = array();

	static function add_dir( $path ) {
		self::$dirs[] = $path;
	}

	static function seek( $class )
	{
		$name = $class . '.php';
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

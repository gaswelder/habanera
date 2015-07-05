<?php
/*
 * Convenient functions.
 * This file should be as small as possible.
 */

/*
 * Discards all previously created buffers.
 */
function ob_destroy()
{
	while( ob_get_level() ){
		ob_end_clean();
	}
}

function warning( $message ) {
	log_message( 'Warning: '.$message, 'errors' );
}

function error( $message ) {
	log_message( 'Error: '.$message, 'errors' );
	trigger_error( $message );
}

function fatal( $message ) {
	log_message( 'Fatal: '.$message, 'errors' );
	die( $message );
}

function ext( $name )
{
	$path = _PATH . 'ext/'.$name.'.php';
	if( !file_exists( $path ) ) {
		fatal( "No extension '$name' ($path)" );
	}
	require $path;
}
/*
 * Load a library from the "lib" directory inside APP_DIR.
 */
function lib( $name ) {
	require APP_DIR."lib/$name.php";
}

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


/*
 * Cleans the output, dumps the given variable and stops the script.
 */
function e( $var )
{
	ob_destroy();
	$vars = func_get_args();
	foreach( $vars as $var ){
		var_dump( $var );
	}
	exit;
}

function parse_template( $path, $variables = array() ) {
	$__path = $path;
	extract( $variables );
	ob_start();
	require $__path;
	return ob_get_clean();
}


?>

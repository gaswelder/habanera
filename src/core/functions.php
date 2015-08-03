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

<?php

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

function parse_template( $path, $variables = array() ) {
	$__path = $path;
	extract( $variables );
	ob_start();
	require $__path;
	return ob_get_clean();
}


?>

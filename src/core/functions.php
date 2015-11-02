<?php

function parse_template( $path, $variables = array() ) {
	$__path = $path;
	extract( $variables );
	ob_start();
	require $__path;
	return ob_get_clean();
}


?>

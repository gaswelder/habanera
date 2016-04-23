<?php
/*
 * Functions dealing with output to the browser and redirects.
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

?>

<?php

/*
 * Sends message to the given address.
 * $msg can be a plain text or a 'mail' object.
 */
function send_mail( $addr, $msg, $subj )
{
	if( $msg instanceof mail ) {
		$msg->set_subject( $subj );
	}
	else {
		$m = new mail( $subj );
		$m->set_text( $msg );
		$msg = $m;
	}

	if( !$msg->get_header( 'From' ) ) {
		$msg->set_header( 'From', "noreply@$_SERVER[HTTP_HOST]" );
	}

	log_message( "Mail: $addr ($subj)" );

	if( setting( 'debug' ) ) {
		$path = WRITE_DIR . '/' . time() . '.msg';
		file_put_contents( $path, $msg );
		return true;
	}

	ob_start();
	$msg->send( $addr );
	$errors = ob_get_clean();

	if( $errors != '' ) {
		$errors = strip_tags( $errors );
		warning( "Error while sending mail: $errors" );
		return false;
	}

	return true;
}

?>

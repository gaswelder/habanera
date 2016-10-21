<?php

function send_mail( $address, $text, $subject = null, $add_headers = null ){
	return mails::send_mail( $address, $text, $subject, $add_headers );
}

class mails
{
	static function send_mail( $address, $body, $title = null, $add_headers = null )
	{
		if( !$address ) {
			error( 'Empty email at send_mail' );
			return false;
		}
		if( !$title ){
			$title = $_SERVER['SERVER_NAME'].' notification';
		}
		if( !is_array( $add_headers ) ) {
			$add_headers = array();
		}

		log_message( "Mail to $address ($title)" );

		// development mock
		if( setting( 'debug' ) ) {
			return self::mock_send( $address, $body, $title, $add_headers );
		}
		else {
			return self::real_send( $address, $body, $title, $add_headers );
		}
	}

	private static function mock_send( $address, $body, $title, $headers )
	{
		$bom = "\xEF\xBB\xBF";
		$path = time().'_'.uniqid().'.txt';
		$headers['To'] = $address;
		$headers['Subject'] = $title;

		$src = $bom;
		foreach( $headers as $k => $v ) {
			$src .= "$k: $v\r\n";
		}
		$src .= "\r\n" . $body;
		file_put_contents( h2::appdir().$path, $src );
		return true;
	}

	private static function real_send( $address, $body, $title, $add_headers )
	{
		$headers = array(
			'Content-Type: text/plain; charset="UTF-8"',
			'Date: '.date( 'r' )
		);

		/*
		 * On Windows "From" header in the form of
		 * "User name <user-address>" gets transformed to
		 * "<User name <user-address>>".
		 */

		if( !isset( $add_headers['From'] ) ) {
			$headers[] = "From: noreply@$_SERVER[HTTP_HOST]";
		}

		if( $add_headers ) {
			foreach( $add_headers as $k => $v ) {
				$headers[] = "$k: $v";
			}
		}

		if( self::is_ascii( $title ) ){
			$subject = $title;
		} else {
			$subject = "=?UTF-8?B?".base64_encode( $title )."?=";
		}

		ob_start();
		$r = mail( $address, $subject, $body, implode( "\r\n", $headers ) );
		$errors = ob_get_clean();

		if( $errors != '' )
		{
			$errors = strip_tags( $errors );
			warning( "Error while sending mail: $errors" );
			return false;
		}
		return $r;
	}

	/*
	 * Tells whether all characters in the given string are
	 * printable ASCII.
	 */
	private static function is_ascii( $s )
	{
		$n = strlen( $s );
		for( $i = 0; $i < $n; $i++ )
		{
			$code = ord( $s[$i] );
			if( $code < 32 || $code > 127 ){
				return false;
			}
		}
		return true;
	}
}

?>

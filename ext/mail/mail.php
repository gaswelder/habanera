<?php

class mail
{
	// Map of mail headers. Most common are "To", "From", "Subject",
	// "BCC".
	private $headers = array();

	// MIME parts, arrays with "headers" and "body" keys.
	private $parts = array();

	function __construct()
	{
		$this->headers = array(
			'Date' => date( 'r' ),
			'MIME-Version' => '1.0'
		);
	}

	function set_subject( $text ) {
		$this->headers['Subject'] = self::encode_string( $text );
	}

	function set_header( $name, $value ) {
		$this->headers[$name] = $value;
	}

	function get_header( $name ) {
		if( isset( $this->headers[$name] ) ) {
			return $this->headers[$name];
		}
		return null;
	}

	function set_text( $src, $mime_type = null )
	{
		if( !$mime_type ) {
			$mime_type = 'text/plain; charset="UTF-8"';
		}
		else {
			if( !strpos( $mime_type, 'charset' ) ) {
				$mime_type .= '; charset="UTF-8';
			}
		}

		$headers = array(
			'Content-Type' => $mime_type
			//'Content-Transfer-Encoding' => 'base64'
		);

		//$body = chunk_split( base64_encode( $src ) );
		$body = $src;

		$this->parts[] = array(
			'headers' => $headers,
			'body' => $body
		);
	}

	function attach( $src, $filename = null, $mime_type = null )
	{
		if( !$mime_type ) {
			$mime_type = 'application/octet-stream';
		}

		$headers = array(
			'Content-Type' => $mime_type,
			'Content-Disposition' => 'attachment',
			'Content-Transfer-Encoding' => 'base64'
		);

		if( $filename ) {
			$filename = self::encode_string( $filename );
			$headers['Content-Disposition'] .= '; filename="'.$filename.'"';
		}

		$body = chunk_split( base64_encode( $src ) );

		$this->parts[] = array(
			'headers' => $headers,
			'body' => $body
		);
	}

	function __toString()
	{
		$s = '';
		$headers = $this->headers;
		if( count( $this->parts ) == 1 )
		{
			// If there is only one part (plain text assumed), add its
			// headers to the mail headers.
			$headers = array_merge( $headers, $this->parts[0]['headers'] );

			// Write down all the headers.
			foreach( $headers as $name => $value ){
				$s .= "$name: $value\r\n";
			}

			// Add blank line before the body.
			$s .= "\r\n";

			$s .= $this->parts[0]['body'];
		}
		else
		{
			$boundary = '===='.uniqid().'====';
			$headers['Content-Type'] = "multipart/mixed; boundary=\"$boundary\"";

			// Write down all the headers.
			foreach( $headers as $name => $value ){
				$s .= "$name: $value\r\n";
			}

			// Add blank line before the body.
			$s .= "\r\n";

			foreach( $this->parts as $part )
			{
				$s .= "--$boundary\r\n";

				$h = $part['headers'];
				foreach( $h as $name => $value ){
					$s .= "$name: $value\r\n";
				}
				$s .= "\r\n";
				$s .= $part['body'];
				$s .= "\r\n";
			}

			$s .= "--$boundary--";
		}

		return $s;
	}

	function send( $to )
	{
		/* If we add "To" header here, PHP's "mail" will merge it with
		its $to argument, and the same address will appear twice (like
		"foo@b.ar, foo@b.ar"). The same is with "Subject" header. */

		$subject = $this->headers['Subject'];
		unset( $this->headers['Subject'] );
		$mail = $this->__toString();
		$this->headers['Subject'] = $subject;

		$pos = strpos( $mail, "\r\n\r\n" );
		$headers = substr( $mail, 0, $pos );
		$body = trim( substr( $mail, $pos ) );

		/* To send the letter "From" header or sendmail_from parameter
		of php.ini will be used. If none is present, a warning will be
		given. */

		return mail( $to, $subject, $body, $headers );
	}

	private static function encode_string( $src )
	{
		return $src;
		/*
		if( self::is_ascii( $src ) ) {
			return $src;
		}
		$charset = 'UTF-8';
		$encoding = 'B';
		$s = "=?$charset?$encoding?" . base64_encode( $src ) . '?=';
		return $s; */
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

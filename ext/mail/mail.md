# The `mail` extension

The extension defines the `mail` class that allows to compose
MIME-formatted messages and the `send_mail` function.

	send_mail($addr, $msg, $subj)

The `send_mail` function sends message `msg` to the address `addr`.
`msg` may be a plain text string or a `mail` object.

There are two typical use cases. The simple one is just sending some
text:

	```php
	send_mail("user@example.net", "Hello, user", "Test mail");
	```

The complicated one is sending mail with attachments and custom headers:

	```php
	$mail = new mail();
	$mail->set_text("Hello, user, see the attachment");

	$data = file_get_contents("file.zip");
	$mail->attach($data, "file.zip", "application/zip");

	$mail->set_header("Reply-To", "feedback@site.com");

	send_mail("user@example.net", $mail, "Test mail");
	```

The `send_mail` function logs a message to the application log on each
sending.

If the `debug` configuration parameter is set to `1`, `send_mail`
function doesn't send mail, but instead puts it to the application's
writable directory.

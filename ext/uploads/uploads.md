# File uploads

* `$files = uploads::get($input_name)`

	Returns descriptions of uploaded files stored in the `$_FILES`
	global array under the index `$input_name`. The single-file case is
	converted to the general multiple-files form. Each element of the
	returned array is an associative array with fields `name`, `type`,
	`tmp_name` and `size`. Files with errors are filtered out.

* `$paths = uploads::save($files, $dir)`

	Saves files described in the `files` array to the directory `dir`,
	assigning them random names, and returns the array of corresponding
	local file paths.

* `$paths = accept_uploads($input_name, $dir)`

	Combines `uploads::get` and `uploads::save` functions.


Example:

```php
// Get info about the uploaded files
$uploads = uploads::get($name);

// Omit files we don't accept
$errors = array();
$accept = array();
foreach($uploads as $up) {
	if($up['type'] != 'image/jpeg') {
		$errors[] = "$up[name]: doesn't look like a JPEG image";
		continue;
	}
	$accept[] = $up;
}

// Save the files we do accept
$dir = "uploads/" . date("Y-m");
$paths = uploads::save($accept, $dir);
```

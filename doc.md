# Habanera

## Goal

The goal for the framework is to be as unobtrusive as possible. There
should not be demands to create special classes or files to be able to
output a simple page to the browser. Ideally, one should be able to
take a standalone PHP script and have the framework run it without
minimal changes. After that the script could be gradually rewriten
using the functions of the framework, splitting other parts into
actions or templates, and so on.


## Concepts

A typical site deals with two types of requests: requests that return a
page and requests that result in some action being done on the server
side. The second kind of requests typically ends with a redirect to the
relevant "post-action" page, so the user sees seamless transition
between pages with the action done in between.

Here we name the scripts that produce pages "pages" and scripts
dedicated to some work like processing POST requests, without producing
a page, "actions".


## Pages

Pages are created from templates stored in the "pages" directory of the
application directory. The URL path is mapped to the directories
hierarchy inside the pages directory. If the requested URL is
`/foo/bar`, then the following files will be checked:

1. `pages/foo/bar.php`;
2. `pages/foo/default.php`;
3. `pages/foo.php`;
4. `pages/default.php`.

The first file that is found will be processed as a regular PHP file and its output will be served as the page.

The trailing part of the URL that is left after the path matching will
be converted to "arguments" accessible through the `arg` function.

For example, if the request for `/foo/bar/42` has resulted in the file
`pages/foo/default.php` being processed, then the arguments will be
`"default"` (`argv(0)`), `"bar"` (`argv(1)`), `"42"` (`argv(2)`).


### Page URLs

There is always a "current pages directory", which is analogous to the
current working directory, but applies only to page URLs. In the
example above, where the file `pages/foo/default.php` was used to serve
the page, current pages directory would be `/foo`.

The `url` function that builds page URLs, accepts relative and
absolute paths as its single argument. The path is absolute if it
starts with a slash. If the path is relative, then current pages
directory is implicitly added before it. In our example, the call
`url('woe')` would return `/foo/woe` because the current pages
directory would be `/foo`. On the other hand, the call `url('/woe')`
would return `/woe` regardless of the current pages directory.

In a special case, where the site lives in a subdirectory of the host,
the subdirectory is implicitly taken into account. If the site is
served from `http://example.com/subdir/`, then the call `url('/')` will
return `"/subdir"` instead of `"/"`. It is possible thus to deploy a
complete site in a subdirectory without rewriting the `url` calls.

(The subdirectory `"subdir"` would have to be explicitly specified in
the call to `hmain`.)

* `set_title($title)`

	Sets the page title which will be substituted in the generated HTML
	output in the `title` tag. If the template already has the `title`
	tag with a title in it, the title will be replaced.

* `$title = get_title()`

	Returns the page title that was set using the `set_title` function,
	or `null` if the title wasn't set.

	This function may be useful, for example, to duplicate the title in
	the page body like in the example:

	```php
	<h1><?= htmlspecialchars(get_title()) ?></h1>
	```

* `set_page_meta($name, $content)`

	Adds a `meta` tag with given name and content attributes to the
	`head` section of the generated HTML page.

* `add_link($rel, $href)`

	Adds a `link` element to the `head` section of the page, with `rel`
	and `href` attributes set to the given values.

* `add_js($path, ...)` and `add_css($path, ...)`

	The `add_js` function adds references to Javascript files as
	`script` tags at the end of the `body` section with `src`
	attributes corresponding to the given paths.

	The `add_css` function adds references to CSS files as `link` tags
	in the `head` section with `href` attributes corresponding to the
	given paths.

	The given paths must be relative to the site root. Paths may have
	query strings, for example:

	```php
	<?php
	add_js("js/main.js?arg=314");
	?>
	```

	The functions may be called at any time during the script execution
	as the page formatting and output happens at the end of the script.

* `url_t($path=null)` and `url($path=null)`

	The `url_t` function returns page URL which is a concatenation of current base URL with the given path.

	For example, if current URL is `example.net/one/two` and the page
	script is `pages/foo/bar.php`, then `url('zwei/drei')` will return
	`example.net/one/zwei/drei`.

* `template($name, $vars=array())`

	Returns the result of parsing the file "<name>.t" in the directory
	of the current page script as PHP file with variables `vars` in the
	context.

	The most common use is including top and bottom parts of pages:

	```php
	<?= template('top') ?>
	Page body
	<?= template('bottom') ?>
	```


### Actions

* `declare_action($name, $users, $func)`

	Declares an action `name` and assigns `func` as the action function
	and `users` as the list of types of users who can call that action.
	The `users` argument should contain a comma-separated list of user
	types.

	As all users have at least the `guest` credentials, specifying
	`"guest"` as `users` will allow everyone to run the action.

	If the user has credentials of more than one type from the list,
	the first such credentials will be used. Thus, the action `foo` in
	the example below will be run with the `customer` identity for
	authorized customers, and with the `guest` identity for everyone
	else:

	```php
	declare_action('foo', 'customer, guest', function() {
		...
	});
	```

* `last_action()`

	Returns name of the preceding action after which the user was
	redirected to the current page. The function returns `null` if
	there was no preceding action.

* `action_result()`

	Returns `true` if the preceding action was successful, `false` if
	it returned one or more errors, and `null` if there was no action.

* `action_errors()`

	Returns an array of errors returned by the preceding action. If
	there was no action, an empty array is returned.

* `aurl_t($name, $ok_url=null, $fail_url=null)` and `aurl`

	The `aurl_t` function returns the URL of the action `name` which
	will redirect back to URL `ok_url` on success or to URL `fail_url`
	on error. If `ok_url` is not specified, the current url is assumed.
	If `fail_url` is not specified, the value of `ok_url` is assumed.

	The `aurl` function is the same as `aurl_t` except the returned URL
	is escaped for use in HTML output.

* `action_button($title, $action_name, $args, $ok_url, $fail_url)`

	The `action_button` function returns HTML code for a form with
	submit button that directs the user to the action URL
	`url_t($action_name, $ok_url, $fail_url)` passing values given in
	the associative array `args` as POST parameters.

If the configuration parameter `log_action` is set to `1`, each action
will be logged in the application log.


## User sessions

A website often requires the user to be identified through a login
form. After the identification the obtained result is stored in the
session data.

After the authorisation the app will call the `user::auth` function to
store the user type and identifier in the session. After that the
stored parameters may be checked using the `user::type` and `user::id`
functions.

* `user::auth($type, $id=null)`

	Adds the pair (type, id) to the user's list of credentials. The
	identifier is secondary to the user type and may be omitted.

	If there is a pair with the same type, it is replaced. All the
	additional data related to the discarded pair is cleared.

* `$type = user::type()`

	Returns the type of the user's current identity.

* `$id = user::id()`

	Returns the identifier of the user's current identity.


### Multiple credentials

Suppose a single site hosts two admin interfaces, one for the editor
and one for the reviewer. Suppose there is a person who happens to work
as both. In order for them to work with both interfaces simultaneously,
multiple credentials have to be supported by the system.

To add multiple credentials just call `user::auth` multiple times. Each
call must have different `type` argument.

Now the 'editor' page will have to declare that an 'editor' is
expected, and the reviewer page will expect a 'reviewer'. This can be
declared with the `user::select` function.

* `user::select($type)`

	Selects the pair with the given type from the current user's
	credentials, if such exists, and returns `true` on success or
	`false` on failure.

	Applications typically call `user::select` to check that the user
	was authorized as a particular type and to get other values
	attached to that type using `user::id` and `user::get`.

	```php
	// Check that the user is an editor
	if(!user::select('editor')) {
		error_forbidden("Only editors can view this");
	}

	// Use session data associated with the editor credentials
	$id = user::get('last-article');
	if($id) {
		redirect(url_t("articles/$id"));
	}
	...
	```


### Session data

Besides the credentials, sessions store other data, like a "shopping
cart". The data is bound to credentials, so that data stored in the
'editor' context doesn't interfere with the data stored in the
'reviewer' context.

It follows then that a user must have at least one role to be allowed
to store any data, so there is a special "guest" role which all users
always have.

* `user::set($key, $val)`

	Associates the given key-value pair with the current user identity.

* `user::get($key)`

	Returns data associated with the given key under the current
	identity. Returns `null` if there was no data set.

* `user::transfer($type)`

	Allows to move all session data associated with the guest identity
	to another identity of the given type.

	This might be needed, for example, to preserve the shopping cart a
	user has created before logging in. In that case the authorisation
	might look like:

	```php
	// Add new identity
	user::auth('customer', 42);
	// Move the session data to the new identity
	user::transfer('customer');
	```

### Logging out

* `user::clear($type)`

	Removes the credentials pair with the given type along with
	additional data that might be attached to it.

	In other words, this is the logout function.


## Core functions

* `$url = current_url()`

	Returns the URL that is being processed. The URL is returned as
	text, without HTML escaping.

* `$arg = poparg()`

	Returns next part of the parsed URL and shifts the arguments
	pointer one step further. If there are no more parts, return
	`null`.

* `log_message($msg)`

	Writes the given message to the logfile `<appdir>/log.log`, adding
	several context columns to it.


## Request data

* `vars::get($name)`

	Returns unescaped plain text value of the GET parameter `name`,
	or `null` if there is no such parameter.

* `vars::post($name)`

	Returns unescaped plain text value of the POST parameter `name`,
	or `null` if there is no such parameter.


## Error handling

Habanera doesn't differentiate between error levels. All PHP errors are
caught and treated the same. An error is just an error regardless of
its level.

* `error($message)`

	Prints the message to the errors log and terminates the script with
	the "Internal Server" error.

* `warning($message)`

	Intended to report potential errors. Prints the message to the
	error log. A warning doesn't terminate the script, unless the
	`debug` configuration parameter is set to `1`.


### HTTP header functions

* `redirect($url, $code=302)`

	Writes appropriate HTTP headers to redirect the browser to the
	given URL and terminates the script.

	By default the redirect is done with the `302 Found` HTTP status.
	The `code` variable may specify another code, like `303` for `See
	Other` or `307` for `Temporary Redirect` status.

	Note that the `302` status code was historically ambiguous and
	doesn't guarantee any semantics nowadays. The `303` and `307` codes
	should be used when specific behaviour is needed.

* `error_notfound()`

	404
	Could not find the requested resource.

* `error_bad_request()`

	400
	Something wrong with the URL or POST data.

* `error_forbidden()`

	403
	Access denied. The client probably has to login.

* `error_gone()`

	410
	The page was removed.

* `error_server()`

	500
	We screwed up.

* `announce_json()`

	Sends `Content-Type` header appropriate for JSON output.

* `announce_txt($charset='UTF-8')`

	Sends `Content-Type` header appropriate for plain text output.
	Optional `charset` parameter specifies the encoding of the text.

* `announce_file($filename, $size=null)`

	Sends headers that trigger a download dialog in the browser.
	`size`, if present, is in bytes.
	```php
		$size = filesize($fpath);
		$f = fopen($fpath, "rb");
		announce_file($fpath, $size);
		fpassthru($f);
		fclose($f);
		exit;
	```


## Libraries and classes

* `add_classes_dir($dir)`

	Habanera provides a simple classes loader for the application. The
	`<appdir>/classes` directory is added to its list of search paths
	by default. The application may add more directories using the
	`add_classes_dir` function.

	The files are searched using simple matching by name, so the class
	Foo would be expected to be in a file `foo.php` in one of the
	search directories.


* `lib($name)`

	The lib function includes the file $name.php in the application's
	lib directory. This is a way to declaratively include libraries,
	typically used in application init scripts.

	The `<appdir>/lib` directory is dedicated to old-school PHP
	libraries which can't be "auto-loaded". Such libraries can be, of
	course, included using the `require` statements too, but the `lib`
	function takes only the name and infers the full path itself.

	For example, calling `lib("bcrypt")` would include the file
	`<appdir>/lib/bcrypt.php`.


## Settings

Some configuration parameters may be defined in INI files in the
application directory. This is intended for read-only values like
database parameters, service URLs and any other such data.

The `setting` function retrieves values from INI files:

	$val = setting($name);

If the given parameter is not defined, `null` is returned.

The appropriate files are loaded on the first call to the `setting`
function.

The INI files are located in the application directory. The default
file name is `settings.ini` (which gives the path
`<appdir>/settings.ini`).

Other files may be present to add or override parameters based on host
names. If the host name is `foo.example.com`, then following files will
be loaded, if present, in the given order:

1. `settings.ini`;
2. `settings.com.ini`;
3. `settings.com.example.ini`;
4. `settings.com.example.foo.ini`.

The most common use for this scheme is to separate development
configuration from the published site's configuration.


## Extensions

Extensions are optional blocks of functionality. If an application
relies on some of the extensions, it should "load" them using the
`load_ext` function:

	load_ext($name, ...);

The `load_ext` function is similar to the `lib` function except the
libraries are loaded from the internal extensions directory of
framework.

The extensions are different from libraries in that they typically
interact with internal API of the framework.

The standard extensions are:

* [`images`](ext/images/images.md) (images subserver)
* [`lang`](ext/lang/lang.md) (`gettext` alternative)
* `mysql`
* [`mail`](ext/mail/mail.md)


## Internal functions

The following functions are intended for extensions.

* `$val = req_header($name)`

	Returns the value of the request header `name`, if one is given by
	the client. Returns `null` otherwise.

* `http_status($code)`

	Outputs an HTTP status header corresponding to the given status code.

* `ob_destroy()`

	Discards all buffered output. This can be used to prevent other
	output from hiding debug messages.

* `e($var...)`

	Calls `ob_destroy`, `var_dump($var...)` and `exit`.

* `add_subserver($func)`

	Adds the function `func` to the list of request processing
	functions. The `func` function must have form:

		$ok = $func($request)

	If the function `func` will be called, it will receive a request
	object request and will have to determine whether to process it. If
	the function recognizes the request and processes it, it must
	return `true`.

* `on_error($func)`

	Adds the given function `func` to the list of functions called when errors occur. The last added function will be called first.

	The function `func` must have form:

		$ok = $func($msg, $line);

	where `msg` is an error message and `line` is a string in form
	"<filepath>:<linenumber>" specifying where the error occured.

	If `func` returns `true`, the rest of the error functions will not
	be called.

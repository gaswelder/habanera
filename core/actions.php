<?php
/*
 * "actions" subserver. Actions are specially declared functions with
 * defined access lists.
 */

add_subserver( 'actions::serve_action' );

/*
 * Declare an action and define its access list and function.
 * $users is a comma-separated list of user types.
 * Set to 'all' to allow access to everybody.
 */
function declare_action( $name, $users, $func )
{
	if( isset( actions::$funcs[$name] ) ) {
		trigger_error( "Action '$name' is already defined" );
		return;
	}

	$list = array_map( 'trim', explode( ',', $users ) );
	actions::$users[$name] = $list;
	actions::$funcs[$name] = $func;
}

/*
 * Returns action name that was called before redirecting
 * to the current page. If there was no action, null is returned.
 */
function last_action() {
	if( isset( $_GET[actions::ACTION_NAME] ) ) {
		return $_GET[actions::ACTION_NAME];
	}
	else {
		return null;
	}
}

/*
 * Returns result of the last action: true if the action was successful,
 * false if there were errors, and null if there was no action.
 */
function action_result()
{
	if( !isset( $_GET[actions::ACTION_RESULT] ) ) {
		return null;
	}
	return $_GET[actions::ACTION_RESULT] == '1';
}

function action_errors()
{
	if( !isset( $_GET[actions::ACTION_ID] ) ) {
		return array();
	}

	$id = $_GET[actions::ACTION_ID];
	$data = user::get( $id );
	if( !$data ) return array();
	return $data['errors'];
}

function aurl_t( $name, $redirect_ok = null, $redirect_fail = null )
{
	if( !$redirect_ok ) {
		$redirect_ok = current_url();
	}
	if( !$redirect_fail ) {
		$redirect_fail = $redirect_ok;
	}

	$vars = array( 'rs', 'rf', 'ares', 'aname' );
	$redirect_ok = _url_clean( $redirect_ok, $vars );
	$redirect_fail = _url_clean( $redirect_fail, $vars );

	$redirect_ok = str_replace( h2::domain(), '', $redirect_ok );
	$redirect_fail = str_replace( h2::domain(), '', $redirect_fail );

	return h2::base() . '/a/'.$name.'?rs='.urlencode($redirect_ok)
		.'&rf='.urlencode($redirect_fail);
}

function aurl( $args, $redirect_ok = null, $redirect_fail = null )
{
	return htmlspecialchars( aurl_t( $args, $redirect_ok, $redirect_fail ) );
}

/*
 * Removes given query variables from the url and returns the result.
 */
function _url_clean( $url, $vars )
{
	/*
	 * Parse the URL. If there is no query string, don't bother
	 * and return the original URL.
	 */
	$u = parse_url( $url );
	if( !isset( $u['query'] ) ) return $url;

	/*
	 * Remove the variables.
	 */
	parse_str( $u['query'], $q );
	foreach( $vars as $v ) {
		unset( $q[$v] );
	}

	/*
	 * Compose the URL back.
	 */
	$url = '';
	if( isset( $u['scheme'] ) ) $url .= $u['scheme'] . '://';
	$url .= $u['host'];
	if( isset( $u['port'] ) ) $url .= ':'.$u['port'];
	$url .= $u['path'];
	if( !empty( $q ) ) {
		$url .= '?' . http_build_query( $q, '', '&' );
	}
	if( isset( $u['fragment'] ) ) $url .= '#'.$u['fragment'];
	return $url;
}

function action_button( $button_title, $action_name, $args,
	$redirect_ok = null, $redirect_fail = null )
{
	ob_start();
	$url = aurl( $action_name, $redirect_ok, $redirect_fail );
	?>
	<form method="post" action="<?= $url ?>">
	<?php
	foreach( $args as $name => $val )
	{
		$val = htmlspecialchars( $val );
		$name = htmlspecialchars( $name );
		?>
		<input type="hidden" name="<?= $name ?>" value="<?= $val ?>">
		<?php
	}
	?>
		<button type="submit"><?= htmlspecialchars( $button_title ) ?></button>
	</form>
	<?php
	return ob_get_clean();
}

class actions
{
	/*
	 * Return redirect is needed to pass results to the page.
	 * The information is coded in the query variables and in the
	 * session data.
	 */
	/*
	 * URL parameters used for saving action results.
	 */
	const ACTION_NAME = 'aname';
	const ACTION_RESULT = 'ares';
	const ACTION_ID = 'aid';

	/*
	 * action name => array of user types.
	 * action name => function to call.
	 */
	static $users = array();
	static $funcs = array();

	static function serve_action( $req )
	{
		/*
		 * Check that this is an action URL.
		 */
		if( $req->arg(0) != 'a' || $req->arg(2) ) {
			return false;
		}
		self::run( $req->arg(1) );
	}

	/*
	 * Serves the given action.
	 */
	private static function run( $action_name )
	{
		/*
		 * Check that the action exists and the user may access it.
		 */
		$func = self::find_action( $action_name );
		if( !$func ) {
			error_notfound();
		}
		if( !self::action_allowed( $action_name ) ) {
			error_forbidden();
		}

		ob_start();
		$result = call_user_func( $func );
		$out = ob_get_clean();

		$errors = array();
		/*
		 * If the buffer is not empty, we assume it has PHP error
		 * messages.
		 */
		if( strlen( $out ) > 0 ) {
			warning( "Action function '$action_name' produced output: $out" );
			$errors[] = 'Unspecified internal error';
		}

		/*
		 * To indicate an error, the action may return false, an error
		 * message (a string), or an array of error messages.
		 */
		if( $result === false ){
			$errors[] = 'Unspecified action error';
		}
		else if( is_string( $result ) ) {
			$errors[] = $result;
		}
		else if( is_array( $result ) ) {
			$errors = $result;
		}

		if( setting( 'log_actions' ) ) {
			self::log( $action_name, $errors );
		}

		/*
		 * Do the redirect or output depending on what is needed.
		 */
		if( isset( $_GET['ajax'] ) ) {
			self::action_output( $action_name, $errors );
		}
		else {
			self::action_redirect( $action_name, $errors );
		}
	}

	private static function find_action( $action_name )
	{
		/*
		 * Load files from the actions directory until the needed
		 * function is defined.
		 */
		$paths = glob( h2::appdir() . 'actions/'.'*.php' );
		foreach( $paths as $path )
		{
			require( $path );
			if( isset( self::$funcs[$action_name] ) ) {
				return self::$funcs[$action_name];
			}
		}
		return null;
	}

	/*
	 * Returns true if the given user type has access to the given
	 * action.
	 */
	private static function action_allowed( $action_name )
	{
		$list = self::$users[$action_name];
		foreach( $list as $type ) {
			if( user::select( $type ) ) {
				return true;
			}
		}
		return false;
	}

	/*
	 * Make the result output. Called if the action is called without
	 * a return redirect.
	 */
	private static function action_output( $name, $errors )
	{
		$data = array(
			'name' => $name,
			'ok' => empty( $errors ),
			'errors' => $errors
		);
		header( 'Content-Type: application/json; encoding="UTF-8"' );
		echo json_encode( $data );
	}

	/*
	 * Make a redirect and pass all the data to the destination.
	 */
	private static function action_redirect( $name, $errors )
	{
		$redirect_success = isset( $_GET['rs'] ) ? $_GET['rs'] : null;
		$redirect_failure = isset( $_GET['rf'] ) ? $_GET['rf'] : null;

		/*
		 * If the action was called without redirect URL, we
		 * only can stop. Either both URLs are present, or none.
		 */
		if( !$redirect_success ) {
			exit;
		}

		$data = array(
			self::ACTION_NAME => $name,
			self::ACTION_RESULT => empty( $errors ) ? '1' : '0'
		);

		if( !empty( $errors ) )
		{
			$id = uniqid();
			user::set( $id, array(
				'errors' => $errors,
				'context' => array_merge( $_GET, $_POST )
			));

			$url = $redirect_failure;
			$data[self::ACTION_ID] = $id;
		}
		else
		{
			$url = $redirect_success;
		}

		foreach( $data as $k => $v ) {
			$url .= strpos( $url, '?' ) ? '&' : '?';
			$url .= $k . '=' . urlencode( $v );
		}

		redirect( $url, 303 );
	}

	/*
	 * Writes a record about the action to a log file.
	 */
	private static function log( $action_name, $errors )
	{
		$url = current_url();
		if( empty( $errors ) ) {
			$status = 'OK';
		}
		else {
			$status = count( $errors ) . ' errors: ' . implode( '; ', $errors );
		}

		log_message( "Action: $action_name ($status)" );
	}
}
?>

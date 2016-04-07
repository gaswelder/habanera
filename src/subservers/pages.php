<?php
/*
 * Set page <title>.
 */
function set_page_title( $title ) {
	pages::set_title( $title );
}

function set_page_meta( $name, $content ) {
	pages::$meta[$name] = $content;
}

function add_link( $rel, $href ) {
	pages::$links[] = array( 'rel' => $rel, 'href' => $href );
}

/*
 * Add CSS links to the page.
 */
function add_css( $href__ )
{
	$args = func_get_args();
	foreach( $args as $href ) {
		$href = pages::check_href( $href );
		add_link( 'stylesheet', $href );
	}
}

function add_js( $src )
{
	$args = func_get_args();
	foreach( $args as $src ) {
		$src = pages::check_href( $src );
		pages::$scripts[] = $src;
	}
}

function template( $name, $vars = array() ) {
	return pages::get_template( $name, $vars );
}

function url_t( $path = '' ) {
	return pages::url_t( $path );
}

function url( $path = '' ) {
	return htmlspecialchars( url_t( $path ) );
}

add_subserver( 'pages::serve_page' );

class pages
{

	private static $req;
	/*
	 * Current paths stack.
	 */
	private static $paths = array();

	private static $title = '';
	/*
	 * meta name => content
	 */
	static $meta = array();

	/*
	 * Array of dicts {rel, href}.
	 */
	static $links = array();

	static $scripts = array();

	static function serve_page( $req )
	{
		if( $req->arg(0) == 'a' ) {
			return false;
		}

		self::$req = $req;
		$i = self::traverse( $req );
		if( $i < 0 ) {
			return false;
		}

		$path = self::filepath( $req );
		if( !file_exists( $path ) ) {
			return false;
		}

		self::run( $path );
		return true;
	}

	/*
	 * Enters the needed directory for the URL, returns index
	 * pointing to non-directory part of the URL.
	 */
	private static function traverse( $req )
	{
		/*
		 * Start from the root of the pages directory.
		 */
		$path = APP_DIR.'pages';
		self::enter_dir( $path );
		$i = 0;

		while( $arg = $req->arg($i) )
		{
			$p = $path .'/'.$req->arg($i);
			if( file_exists( $p.'.php' ) ) {
				break;
			}

			if( !is_dir( $p ) ) {
				break;
			}

			$path = $p;
			/*
			 * Omit this part from the URL since we have used it.
			 * We have to do it now because enter_dir can trigger an
			 * init script which might need to use the url() function.
			 */
			$req->omit();
			self::enter_dir( $p );
			$i++;
		}
		return $i;
	}

	private static function enter_dir( $p )
	{
		if( file_exists( $p.'/__init.php' ) ) {
			include $p.'/__init.php';
		}
		self::$paths[] = $p;
	}

	private static function filepath( $req )
	{
		$path = self::current_path();
		$i = 0;

		if( $req->arg($i) )
		{
			$p = $path.'/'.$req->arg($i).'.php';
			if( file_exists( $p ) ) {
				return $p;
			}
		}

		$p = $path.'/default.php';
		if( file_exists( $p ) ) {
			return $p;
		}

		return null;
	}

	private static function current_path()
	{
		$n = count( self::$paths );
		return self::$paths[$n-1];
	}

	private static function run( $path )
	{
		$src = self::parse( $path );
		self::postprocess( $src );
		echo $src;
	}

	/*
	 * Runs the given script (template) in buffer and returns the output.
	 */
	static function parse( $path, $vars = array() )
	{
		/* Rename path in case there is "path" in the $vars. */
		$__path = $path;
		unset( $path );

		ob_start();
		extract( $vars );
		require( $__path );
		$src = ob_get_clean();

		return $src;
	}

	private static function postprocess( &$src )
	{
		self::insert_title( $src );
		self::insert_head( $src );
		self::insert_scripts( $src );
	}

	private static function insert_title( &$src )
	{
		if( !self::$title ) return;

		$c = '<title>'.self::$title.'</title>';
		if( strpos( $src, '<title>' ) === false ) {
			$src = str_replace( '</head>', $c."\n</head>", $src );
		}
		else {
			$src = preg_replace( '@<title>.*?</title>@', $c, $src );
		}
	}

	static function set_title( $title ) {
		self::$title = $title;
	}

	private static function insert_head( &$src )
	{
		$lines = array();

		foreach( self::$meta as $name => $content ) {
			$lines[] = sprintf( '<meta name="%s" content="%s">',
				$name, $content );
		}

		foreach( self::$links as $link ) {
			$lines[] = '<link rel="'.$link['rel'].'" href="'.$link['href'].'">';
		}

		$s = implode( PHP_EOL . "\t", $lines ) . PHP_EOL;
		$src = str_replace( '</head>', "\t".$s.PHP_EOL.'</head>', $src );
	}

	static function check_href( $href )
	{
		if( strpos( $href, '://' ) !== false ) {
			return $href;
		}

		$url = parse_url( $href );
		if( !isset( $url['query'] ) ) {
			$url['query'] = '';
		}

		if( !isset( $url['path'] ) || count( $url ) != 2 ) {
			warning( "Unknown href format: $href" );
			return $href;
		}

		if( !file_exists( $url['path'] ) ) {
			warning( "Referenced file '$url[path]' does not exist." );
			return $href;
		}

		$time = filemtime( $url['path'] ) - strtotime( '2015-04-01' );
		if( $url['query'] ) {
			$url['query'] .= '&amp;';
		}
		$url['query'] .= 'v='.$time;

		$href = SITE_ROOT . $url['path'] . '?'. $url['query'];
		return $href;
	}

	private static function insert_scripts( &$src )
	{
		// Insert scripts at the bottom.
		$lines = array();
		foreach( self::$scripts as $path ){
			$lines[] = '<script src="'.$path.'"></script>';
		}
		$src = str_replace( '</body>',
			implode( PHP_EOL, $lines ).PHP_EOL.'</body>', $src );
	}

	/*
	 * Processes the given template with the given context.
	 * The view's init is executed on the first access.
	 */
	static function get_template( $name, $vars = array() )
	{
		$path = self::current_path() . '/' . $name . '.tpl';
		return self::parse( $path, $vars );
	}

	static function url_t( $path = '' )
	{
		/*
		 * We have to get the omitted part too to build the correct
		 * URL.
		 */
		return SITE_DOMAIN . self::$req->prefix() . $path;
	}

}
?>

<?php

function image_url( $path, $width = null, $height = null ) {
	return images::create_url( $path, $width, $height );
}

add_subserver( 'images::serve_image' );

/*
 * Sometimes we need to dynamically create image previews. The idea is
 * to generate URLs for previews in special form and catch them when
 * requested to create the copy and serve it.
 */

class images
{
	/*
	 * Maximum file size of an image we can resize.
	 */
	const MAX_IMAGE_SIZE = 2097152;

	/*
	 * Recognized extensions. Here they should be in lower case.
	 */
	private static $extensions = array( '.png', '.jpeg', '.jpg', '.gif' );

	private static $mime = array(
		'.gif' => 'image/gif',
		'.jpg' => 'image/jpeg',
		'.png' => 'image/png',
		'.jpeg' => 'image/jpeg'
	);

	static function serve_image( $req )
	{
		$spec = self::get_image_spec( $req );
		if( !$spec ) {
			return false;
		}

		list( $path, $width, $height ) = $spec;

		/*
		 * If there is no original image, return 404.
		 */
		if( !file_exists( $path ) ) {
			error_notfound();
		}

		/*
		 * Create the copy in a cache, if it doesn't exist yet.
		 */
		$hashpath = self::hashpath( $path, $width, $height );
		if( !file_exists( $hashpath ) )
		{
			/*
			 * If for some reason we couldn't create the copy, resort
			 * to 404.
			 */
			if( !self::create_copy( $path, $width, $height, $hashpath ) ) {
				error_notfound();
			}
		}

		/*
		 * Return the file.
		 */
		return self::serve_file( $hashpath );
	}

	/*
	 * The dimensions are specified using markers:
	 * .../`image.jpg` --- original,
	 * .../`image_w200.jpg` --- fit to 200 px width,
	 * .../`image_h400.jpg` --- fit to 400 px height,
	 * .../`image_w200_h400.jpg` --- fit both dimensions.
	 */
	static function get_image_spec( $req )
	{
		$path = $req->join();
		$ext = self::get_ext( $path );
		if( !in_array( $ext, self::$extensions ) ) {
			return null;
		}

		$width = 0;
		$height = 0;

		/*
		 * Remove backslash at the beginning and the extension at the
		 * end.
		 */
		$path = substr( $path, 1 );
		$path = substr( $path, 0, strrpos( $path, '.' ) );

		/*
		 * Read and remove height and width specifications.
		 */
		$pos = strrpos( $path, '_', -1 );
		if( $pos && $path[$pos+1] == 'h' )
		{
			$height = substr( $path, $pos + 2 );
			$path = substr( $path, 0, $pos );
			$pos = strrpos( $path, '_', -1 );
		}

		if( $pos && $path[$pos+1] == 'w' )
		{
			$width = substr( $path, $pos + 2 );
			$path = substr( $path, 0, $pos );
		}

		$path .= $ext;
		return array( $path, $width, $height );
	}

	/*
	 * We could use the pathinfo function, but it behaves slightly
	 * differently from what we need in case of missing extension.
	 */
	private static function get_ext( $path )
	{
		$pos = strrpos( $path, '.' );
		if( !$pos ) {
			return '';
		}
		return strtolower( substr( $path, $pos ) );
	}

	/*
	 * Returns URL for the copy of the given image which fits the given
	 * dimensions.
	 */
	static function create_url( $path, $width = null, $height = null )
	{
		$ext = self::get_ext( $path );

		if( !$ext || !in_array( $ext, self::$extensions ) ) {
			warning( "Wrong extension in image path: '$path'" );
			return $path;
		}

		/*
		 * If dimensions are provided, insert them before the extension.
		 */
		if( $width || $height )
		{
			$marker = '';
			if( $width ) $marker .= '_w'.$width;
			if( $height ) $marker .= '_h'.$height;
			$path = substr( $path, 0, -strlen( $ext ) ) . $marker . $ext;
		}

		return SITE_DOMAIN . SITE_ROOT . $path;
	}

	/*
	 * Returns path to a generated preview copy of the given image
	 * fitted to the given dimensions.
	 */
	private static function hashpath( $path, $width, $height )
	{
		$ext = self::get_ext( $path );
		return WRITE_DIR . 'image-previews/'
			. md5_file( $path ).'_'.$width.'x'.$height . $ext;
	}

	/*
	 * Create a fitted copy and save to the the "hashpath".
	 */
	private static function create_copy( $path, $width, $height, $hashpath )
	{
		if( filesize( $path ) > self::MAX_IMAGE_SIZE ) {
			warning( "image too big to create a copy: $path" );
			return false;
		}

		/*
		 * Create directory, if needed.
		 */
		$dir = dirname( $hashpath );
		if( !file_exists( $dir ) && !mkdir( $dir, 0777, true ) ) {
			warning( "Could not create previews directory: $dir" );
			return false;
		}

		$img = new _gd_image( $path );
		if( !$img->valid() ) {
			warning( "Could not open image file: $path" );
			return false;
		}

		if( !$img->limit( $width, $height ) ) {
			warning( "Could not create thumbnail for $path" );
			$img->close();
			return false;
		}

		if( !$img->save( $hashpath ) ) {
			warning( "Could not save thumbnail to $hashpath" );
			$img->close();
			return false;
		}

		return true;
	}

	private static function serve_file( $path )
	{
		$mime = self::get_mime( $path );
		$size = filesize( $path );

		header( 'Content-Type: '.$mime );
		header( 'Content-Length: '.$size );
		header( 'Last-Modified: ' . date( 'r', filemtime( $path ) ) );
		$f = fopen( $path, 'rb' );
		fpassthru( $f );
		fclose( $f );
		return true;
	}

	private static function get_mime( $path )
	{
		foreach( self::$mime as $ext => $mime ) {
			if( substr( $path, -strlen( $ext ) ) == $ext ) {
				return $mime;
			}
		}
		warning( "Could not detemine MIME type for image: '$path'" );
		return 'application/octet-stream';
	}

}
?>

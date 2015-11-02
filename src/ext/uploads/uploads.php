<?php

function accept_uploads( $input_name, $dest_dir, $allowed_exts = null ) {
	return uploads::accept_uploads( $input_name, $dest_dir, $allowed_exts );
}

function get_uploads( $input_name ) {
	return uploads::get_uploads( $input_name );
}

/*
 * This module helps processing file uploads.
 */
class uploads
{
	static function get_uploads( $input_name )
	{
		if( !isset( $_FILES[$input_name] ) ) {
			return array();
		}

		return self::get_files( $input_name );
	}

	static function accept_uploads( $input_name, $dest_dir,
		$allowed_extensions = null )
	{
		if( !isset( $_FILES[$input_name] ) ) {
			return array();
		}

		$files = self::get_files( $input_name );
		$accepted = array();

		foreach( $files as $file )
		{
			$path = self::process_file( $file, $dest_dir, $allowed_extensions );
			if( !$path ) {
				continue;
			}
			$accepted[] = $path;
		}

		return $accepted;
	}

	/*
	 * Returns array of "dicts" {type, tmp_name, error, size, name}
	 * uniformly for single-file and multi-file inputs.
	 * Returns null of there is no such input.
	 */
	private static function get_files( $input_name )
	{
		$inputs = array();

		/* If the input is a single file, return it. */
		if( !is_array( $_FILES[$input_name]["name"] ) )
		{
			if( $_FILES[$input_name]['name'] != '' ){
				$inputs[] = $_FILES[$input_name];
			}
			return $inputs;
		}

		$fields = array( "type", "tmp_name", "error", "size", "name" );
		foreach( $_FILES[$input_name]["name"] as $i => $name )
		{
			if( $_FILES[$input_name]['name'][$i] == '' ) {
				continue;
			}
			// TODO: there must be some array_*** magick for that.
			$input = array();
			foreach( $fields as $f ){
				$input[$f] = $_FILES[$input_name][$f][$i];
			}
			$inputs[$i] = $input;
		}

		return $inputs;
	}

	private static function process_file( $file, $dir, $exts )
	{
		if( $file['error'] || !$file['size'] ) {
			warning( "Upload failed: $file[name]" );
			return null;
		}

		$ext = strtolower( self::ext( $file['name'] ) );

		if( $ext && !preg_match( '/^.[a-z0-9\-_]+$/', $ext ) ) {
			warning( "Unacceptable file extension: $file[name]" );
			return null;
		}

		if( is_array( $exts ) && !in_array( $ext, $exts ) ) {
			warning( "File rejected by filter: $file[name]" );
			return null;
		}

		$name = self::noext( $file['name'] );
		$name = self::filter_name( $name );
		$path = self::get_path( $dir, $name, $ext );

		if( !move_uploaded_file( $file['tmp_name'], $path ) ) {
			return null;
		}

		log_message( 'Accepted file '.$path, 'uploads' );
		return $path;
	}

	private static function filter_name( $name )
	{
		$filter = '[^a-zA-Z\-_\d.]';
		$name = str_replace( ' ', '-', $name );
		$name = preg_replace( $filter, '', $name );

		// Replace multiple dashes with a single one.
		$name = preg_replace( '/-{2,}/', '-', $name );

		/*
		 * If after the filtering there is not much left, generate
		 * a random name.
		 **/
		if( strlen( $name ) < 4 ){
			$name = uniqid();
		}

		return $name;
	}

	private static function get_path( $dir, $name, $ext )
	{
		$path = $dir.'/'.$name;

		if( file_exists( $path.$ext ) )
		{
			$i = 1;
			while( file_exists( "$path-$i".$ext ) ) {
				$i++;
				if( $i == 10 ) {
					warning( "Could not get file name for $dir/$name".$ext );
					$path = $dir.'/'.uniqid();
					break;
				}
			}
		}

		if( !file_exists( $dir ) ) {
			mkdir( $dir, 0777, true );
		}

		return $path.$ext;
	}

	private static function ext( $filename )
	{
		$ext = pathinfo( $filename, PATHINFO_EXTENSION );
		if( $ext != '' ) $ext = '.'.$ext;
		return $ext;
	}

	private static function noext( $filename )
	{
		$pos = strrpos( $filename, '.' );
		// Cases like 'readme' and '.htaccess'.
		if( $pos === false || $pos === 0 ){
			return $filename;
		} else {
			return substr( $filename, 0, $pos );
		}
	}
}
?>

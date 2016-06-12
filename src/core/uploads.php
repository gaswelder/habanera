<?php

function accept_uploads( $input_name, $dest_dir ) {
	return uploads::accept_uploads( $input_name, $dest_dir );
}

function uploaded_files( $input_name ) {
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

	static function accept_uploads( $input_name, $dest_dir )
	{
		if( !isset( $_FILES[$input_name] ) ) {
			return array();
		}

		/*
		 * Make sure dest_dir ends with a slash.
		 */
		if( substr( $dest_dir, -1 ) != '/' ) {
			$dest_dir .= '/';
		}

		$accepted = array();
		$files = self::get_files( $input_name );
		foreach( $files as $file )
		{
			if( $file['error'] || !$file['size'] ) {
				warning( "File upload failed: $file[name]" );
				continue;
			}

			$path = self::newpath( $file, $dest_dir );
			if( !$path ) {
				continue;
			}

			if( !move_uploaded_file( $file['tmp_name'], $path ) ) {
				warning( "Could not move uploaded file $f[tmp_name]" );
				continue;
			}

			$accepted[] = $path;
		}

		return $accepted;
	}

	/*
	 * Returns array of "dicts" {type, tmp_name, error, size, name}
	 * describing files uploaded through the input with the given
	 * Returns null if there is no such input.
	 */
	private static function get_files( $input_name )
	{
		$inputs = array();

		/*
		 * Single-file case.
		 */
		if( !is_array( $_FILES[$input_name]["name"] ) )
		{
			if( $_FILES[$input_name]['name'] != '' ){
				$inputs[] = $_FILES[$input_name];
			}
			return $inputs;
		}

		/*
		 * Multiple-file case.
		 */
		$fields = array( "type", "tmp_name", "error", "size", "name" );
		foreach( $_FILES[$input_name]["name"] as $i => $name )
		{
			if( $_FILES[$input_name]['name'][$i] == '' ) {
				continue;
			}
			$input = array();
			foreach( $fields as $f ){
				$input[$f] = $_FILES[$input_name][$f][$i];
			}
			$inputs[$i] = $input;
		}

		return $inputs;
	}

	/*
	 * Generates a name for the given file to be stored in the
	 * 'dest_dir' directory.
	 */
	private static function newpath( $file, $dest_dir )
	{
		/*
		 * Determine the extension based on the MIME type and file name
		 * given by the user agent.
		 */
		$ext = _mime::ext( $file['type'] );
		if( $ext === null ) {
			warning( "Unknown uploaded file type: $file[type]" );
			$ext = self::ext( $file['name'] );
		}
		if( $ext == '' && strpos( $file['name'], '.' ) !== false ) {
			warning( "File '$file[name]' uploaded as octet-stream" );
			$ext = self::ext( $file['name'] );
		}
		if( $ext == '.php' ) {
			warning( ".php file uploaded" );
			$ext .= '.txt';
		}

		/*
		 * Generate a path for the new file.
		 */
		$path = $dest_dir . uniqid() . $ext;
		$i = 0;
		while( file_exists( $path ) ) {
			$i++;
			warning( "Filename collision in uploads::newpath: $path" );
			if( $i >= 3 ) {
				return null;
			}
			$path = $pref . uniqid() . $ext;
		}
		return $path;
	}

	private static function ext( $filename )
	{
		$ext = pathinfo( $filename, PATHINFO_EXTENSION );
		if( $ext != '' ) $ext = '.'.$ext;
		return $ext;
	}
}
?>

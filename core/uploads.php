<?php

function accept_uploads( $input_name, $dest_dir ) {
	$files = uploads::get( $input_name );
	return uploads::save( $files, $dest_dir );
}

function uploaded_files( $input_name ) {
	return uploads::get( $input_name );
}

/*
 * This module helps processing file uploads.
 */
class uploads
{
	/*
	 * Returns array of "dicts" {type, tmp_name, error, size, name}
	 * describing files uploaded through the input with the given
	 * Returns null if there is no such input.
	 */
	static function get( $input_name )
	{
		if( !isset( $_FILES[$input_name] ) ) {
			return array();
		}

		$files = array();

		/*
		 * Single-file case.
		 */
		if( !is_array( $_FILES[$input_name]['name'] ) )
		{
			if( $_FILES[$input_name]['name'] != '' ) {
				$files[] = $_FILES[$input_name];
			}
			return $files;
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
			$files[$i] = $input;
		}

		return $files;
	}

	static function save( $files, $dest_dir )
	{
		/*
		 * Make sure dest_dir ends with a slash.
		 */
		if( substr( $dest_dir, -1 ) != '/' ) {
			$dest_dir .= '/';
		}

		/*
		 * Create the directory if needed.
		 */
		if( !is_dir( $dest_dir ) && !@mkdir( $dest_dir ) ) {
			error( "Could not create upload directory '$dest_dir'" );
			return array();
		}

		$paths = array();
		foreach( $files as $file )
		{
			if( $file['error'] || !$file['size'] ) {
				$errstr = self::errstr( $file['error'] );
				warning( "Upload of file '$file[name]' failed ($errstr, size=$file[size])" );
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

			if( setting( 'log_uploads' ) ) {
				$size = round( $file['size'] / 1024, 2 );
				log_message( "Upload: $file[name] to $path ($size KB)" );
			}

			$paths[] = $path;
		}
		return $paths;
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
		return strtolower( $ext );
	}

	private static function errstr( $errno )
	{
		switch( $errno )
		{
			case UPLOAD_ERR_OK:
				return "no error";
			case UPLOAD_ERR_INI_SIZE:
				return "the file exceeds the 'upload_max_filesize' limit";
			case UPLOAD_ERR_FORM_SIZE:
				return "the file exceeds the 'MAX_FILE_SIZE' directive that was specified in the HTML form";
			case UPLOAD_ERR_PARTIAL:
				return "the file was only partially uploaded";
			case UPLOAD_ERR_NO_FILE:
				return "no file was uploaded";
			case UPLOAD_ERR_NO_TMP_DIR:
				return "missing temporary folder";
			case UPLOAD_ERR_CANT_WRITE:
				return "failed to write file to disk";
			default:
				return "unknown error ($errno)";
		}
	}
}
?>

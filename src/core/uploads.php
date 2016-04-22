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

		$accepted = array();

		$files = self::get_files( $input_name );
		foreach( $files as $file )
		{
			if( $file['error'] || !$file['size'] ) {
				warning( "Upload failed: $file[name]" );
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

	private static function newpath( $file, $dest_dir )
	{
		$ext = self::ext( $file['name'] );
		$pref = $dest_dir;
		if( substr( $dest_dir, -1 ) != '/' ) {
			$pref .= '/';
		}

		$path = $pref . uniqid() . $ext;
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

	/*
	 * Returns array of "dicts" {type, tmp_name, error, size, name}
	 * uniformly for single-file and multi-file inputs.
	 * Returns null if there is no such input.
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
			$input = array();
			foreach( $fields as $f ){
				$input[$f] = $_FILES[$input_name][$f][$i];
			}
			$inputs[$i] = $input;
		}

		return $inputs;
	}

	private static function ext( $filename )
	{
		$ext = pathinfo( $filename, PATHINFO_EXTENSION );
		if( $ext != '' ) $ext = '.'.$ext;
		return $ext;
	}
}
?>

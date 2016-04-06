<?php
class files
{
	static function save( $dir, $name, $value ) {
		return self::write( $dir, $name, $value ) !== false;
	}

	static function append( $dir, $name, $value ) {
		return self::write( $dir, $name, $value, FILE_APPEND ) !== false;
	}

	static function get( $dir, $name ) {
		$path = WRITE_DIR.'/'.$dir . '/' . $name;
		if( !file_exists( $path ) ) return null;
		return file_get_contents( $path );
	}

	/*
	 * Returns time of the cached file or zero if there is no such file.
	 */
	static function time( $dir, $name ) {
		$path = WRITE_DIR.'/'.$dir . '/' . $name;
		if( !file_exists( $path ) ) {
			return 0;
		}
		return filemtime( $path );
	}

	private static function write( $dir, $name, $value, $flags = 0 )
	{
		$dirname = WRITE_DIR.'/'.$dir;
		if( !file_exists( $dirname ) ) {
			if( !mkdir( $dirname, 0777, true ) ) {
				return false;
			}
		}
		return file_put_contents( $dirname.'/'.$name, $value, $flags );
	}
}
?>

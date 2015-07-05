<?php
/*
 * This is a file-based data storage. Intended for cases where MySQL or
 * anything else is overkill. "Directories" are like "tables", and
 * "objects" are like "table rows". Objects are associative arrays. No
 * schema is defined or enforced: it writes whatever is given as long
 * as it is a {string=>string} dict.
 * Example:
	$db = new tdb();
	$d = array(
		'hello' => 'world',
		'howdy' => 'globe'
	);
	$db->save_object( 'articles', $d );
 */
class file_database
{
	private $path;

	function __construct( $path = 'tdb/' )
	{
		$this->path = $path;
	}

	/*
	 * Returns ids of objects in the given directory.
	 */
	function list_objects( $dir )
	{
		$path = $this->path . $dir;
		if( !file_exists( $path ) ) {
			return null;
		}

		$list = array();
		$h = opendir( $path );
		while( $fn = readdir( $h ) )
		{
			if( $fn[0] == '.' ) continue;
			$list[] = $fn;
		}
		closedir( $h );
		return $list;
	}

	/*
	 * Returns the object with the given id in the given directory.
	 */
	function get_object( $dir, $id )
	{
		$path = $this->path . $dir . '/' . $id;
		if( !file_exists( $path ) ) {
			return null;
		}
		return self::read_file( $path );
	}

	/*
	 * Saves new object. Returns its id.
	 */
	function save_object( $dir, $data )
	{
		$list = $this->list_objects( $dir );
		if( !$list || empty( $list ) ) {
			$id = 1;
		}
		else if( count( $list ) == 1 ) {
			$id = $list[0] + 1;
		}
		else $id = call_user_func_array( 'max', $list ) + 1;

		$path = $this->path . $dir . '/' . $id;
		self::save_file( $path, $data );
		return $id;
	}

	/*
	 * Overwrites the object with the new one.
	 */
	function update_object( $dir, $id, $data )
	{
		$path = $this->path . $dir . '/' . $id;
		self::save_file( $path, $data );
		return $id;
	}

	/*
	 * Deletes the object.
	 */
	function delete_object( $dir, $id )
	{
		$path = $this->path . $dir . '/' . $id;
		if( file_exists( $path ) ) {
			return unlink( $path );
		}
		return false;
	}

	/*
	 * Parse the file into the object.
	 */
	private static function read_file( $path )
	{
		$data = array();
		$key = null;
		$value = '';

		$f = fopen( $path, "r" );
		while( $line = fgets( $f ) )
		{
			if( $line[0] == '#' )
			{
				if( $key )
				{
					if( substr( $value, -2 ) == "\r\n" ) {
						$value = substr( $value, 0, -2 );
					}
					else if( substr( $value, -1 ) == "\n" ) {
						$value = substr( $value, 0, -1 );
					}
					$data[$key] = $value;
					$value = '';
				}
				$key = substr( trim( $line, "\r\n" ), 1 );
				continue;
			}

			if( $line[0] == "\\" ) {
				$line = substr( $line, 1 );
			}

			$value .= $line;
		}
		if( $key )
		{
			if( substr( $value, -2 ) == "\r\n" ) {
				$value = substr( $value, 0, -2 );
			}
			else if( substr( $value, -1 ) == "\n" ) {
				$value = substr( $value, 0, -1 );
			}
			$data[$key] = $value;
		}
		fclose( $f );
		return $data;
	}

	/*
	 * Write the object to the file.
	 */
	private static function save_file( $path, $data )
	{
		$dir = dirname( $path );
		if( !file_exists( $dir ) ) {
			mkdir( $dir, 0777, true );
		}

		$f = fopen( $path, "w" );
		foreach( $data as $key => $value )
		{
			fwrite( $f, '#'.$key."\n" );
			$lines = preg_split( '/\r?\n/', $value );
			foreach( $lines as $line )
			{
				if( strlen( $line ) > 0 )
				{
					if( $line[0] == "\\" || $line[0] == '#' ) {
						fwrite( $f, "\\" );
					}
				}
				fwrite( $f, $line."\n" );
			}
		}
		fclose( $f );
	}
}
?>

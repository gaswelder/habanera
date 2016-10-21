<?php
class tdb
{
	private static $tdb = null;

	private static function db()
	{
		if( !self::$tdb ) {
			self::$tdb = new file_database( APPDIR.'tdb/' );
		}
		return self::$tdb;
	}

	static function list_objects( $dir )
	{
		$l = self::db()->list_objects( $dir );
		if( !$l ) $l = array();
		return $l;
	}

	static function get_object( $dir, $id ) {
		return self::db()->get_object( $dir, $id );
	}

	static function update_object( $dir, $id, $data ) {
		return self::db()->update_object( $dir, $id, $data );
	}

	static function save_object( $dir, $data ) {
		return self::db()->save_object( $dir, $data );
	}

	static function delete_object( $dir, $id ) {
		return self::db()->delete_object( $dir, $id );
	}
}
?>

<?php
/*
 * This module stores an instance of MySQL class and redirects calls to
 * it. The instance is created on demand.
 */
class DB
{
	// The MySQL object
	private static $db = null;

	/*
	 * Returns MySQL object connected using "mysql_*" settings.
	 */
	static function c()
	{
		if( self::$db ) return self::$db;

		$host = settings::get( 'mysql_host' );
		$user = settings::get( 'mysql_user' );
		$pass = settings::get( 'mysql_pass' );
		$dbname = settings::get( 'mysql_dbname' );

		if( !$host ) {
			error( 'Database connection parameters are not defined.' );
			return false;
		}

		try {
			self::$db = new MySQL( $host, $user, $pass, $dbname, 'UTF-8' );
		}
		catch( Exception $e ) {
			error( "MySQL exception: " . $e->getMessage() );
		}

		self::$db->add_error_callback( 'error' );
		self::$db->add_warning_callback( 'warning' );
		return self::$db;
	}

	/*
	 * Redirects method calls from this static object to the MySQL
	 * class instance in the $db variable.
	 */
	private static function proxy( $name, $args ) {
		return call_user_func_array( array( self::c(), $name ), $args );
	}

	static function exec( $query, $__args__ = null ) {
		$args = func_get_args();
		return self::proxy( 'exec', $args );
	}

	static function escape( $query, $__args__ = null ){
		$args = func_get_args();
		return self::proxy( 'escape', $args );
	}

	static function getValues( $query, $args = null ){
		$args = func_get_args();
		return self::proxy( 'getValues', $args );
	}

	static function getValue( $query, $__args__ = null ){
		$args = func_get_args();
		return self::proxy( 'getValue', $args );
	}

	static function getRecord( $query, $__args__ = null ){
		$args = func_get_args();
		return self::proxy( 'getRecord', $args );
	}

	static function getRecords( $query, $__args__ = null ){
		$args = func_get_args();
		return self::proxy( 'getRecords', $args );
	}

	static function exists( $table, $filter ) {
		$args = func_get_args();
		return self::proxy( 'exists', $args );
	}

	static function updateRecord( $query, $__args__ = null ){
		$args = func_get_args();
		return self::proxy( 'updateRecord', $args );
	}

	static function updateRecords( $query, $__args__ = null ){
		$args = func_get_args();
		return self::proxy( 'updateRecords', $args );
	}

	static function deleteRecord( $__args__ = null ){
		$args = func_get_args();
		return self::proxy( 'deleteRecord', $args );
	}

	static function deleteRecords( $__args__ = null ){
		$args = func_get_args();
		return self::proxy( 'deleteRecords', $args );
	}

	static function insertRecord( $query, $__args__ = null ){
		$args = func_get_args();
		return self::proxy( 'insertRecord', $args );
	}

	static function insertRecords( $query, $__args__ = null ){
		$args = func_get_args();
		return self::proxy( 'insertRecords', $args );
	}

	static function buildCondition( $query, $__args__ = null ){
		$args = func_get_args();
		return self::proxy( 'buildCondition', $args );
	}
}

?>

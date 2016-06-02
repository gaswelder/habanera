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
	 * Returns MySQL object.
	 */
	static function c()
	{
		if( self::$db ) return self::$db;
		$url = setting( 'database' );
		if( !$url ) {
			error( "Missing 'database' parameter" );
			return null;
		}
		
		self::$db = new __mysql( $url );
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

	static function begin() {
		return self::exec( "START TRANSACTION" );
	}

	static function end() {
		return self::exec( "COMMIT" );
	}

	static function cancel() {
		return self::exec( "ROLLBACK" );
	}
}

?>

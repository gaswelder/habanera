<?php
/*
 * A high-level wrapper for MySQLi functions.
 */
class __mysql
{
	private $connection = null; // mysqli instance

	private $host;
	private $port;
	private $user;
	private $pass;
	private $dbname;

	private $connection_charset;

	private $connected = false;

	function __construct( $url, $connection_charset = 'UTF8' )
	{
		$url = parse_url( $url );

		$host = $url['host'];
		if( !isset( $url['port'] ) ) {
			$port = null;
		}
		$user = $url['user'];
		$pass = $url['pass'];
		$dbname = basename( $url['path'] );

		$this->host = $host;
		$this->port = $port;
		$this->user = $user;
		$this->pass = $pass;
		$this->dbname = $dbname;

		/*
		 * The charset name is "UTF-8", but MySQL call it "UTF8".
		 */
		if( strtoupper( $connection_charset ) == 'UTF-8' ){
			$connection_charset = 'UTF8';
		}
		$this->connection_charset = $connection_charset;
	}

	function connect()
	{
		if( $this->port ) {
			$this->connection = new mysqli( $this->host, $this->user,
				$this->pass, $this->dbname, $this->port );
		}
		else {
			$this->connection = new mysqli( $this->host, $this->user,
				$this->pass, $this->dbname );
		}

		if( mysqli_connect_error() ) {
			trigger_error( "MySQL: could not connect to the host." );
			return false;
		}

		$this->connected = true;
		mysqli_set_charset( $this->connection, $this->connection_charset );
		return true;
	}

	function close()
	{
		if( !$this->connection ) {
			trigger_error( "Can't close: no connection", E_USER_WARNING );
			return false;
		}
		$ok = $this->connection->close();
		$this->connection = null;
		return null;
	}

	function checkConnection()
	{
		if( !$this->connection || !$this->connection->ping() ){
			$this->connect();
		}
	}

	/*
	 * Executes a query using 'sprintf' to substitute arguments.
	 * Every argument is escaped before being substituted.
	 * Returns 'true' or a 'mysqli_result' object depending on the query.
	 * Returns 'null' on error.
	 *
	 * Example:
	 * 	$mysql->exec( "UPDATE table SET field = %d
	 * 		WHERE field2 = '%s'", 12, 'howdy, globe' );
	*/
	function exec( $query, $args___ = null )
	{
		if( !$this->connected ) {
			$this->connect();
		}

		$args = func_get_args();
		$query = $this->construct_query( $args );
		$r = $this->connection->query( $query );

		return $this->check_result( $r, $query );
	}

	/*
	 * Same as exec, but disables caching. Used for streaming.
	 */
	private function execs( $template, $args___ = null )
	{
		if( !$this->connected ) {
			$this->connect();
		}
		$args = func_get_args();
		$query = $this->constructQuery( $args );
		$r = $this->connection->query( $query, MYSQLI_USE_RESULT );
		return $this->check_result( $r, $query );
	}

	private function check_result( $r, $query )
	{
		if( $r === false )
		{
			$Q = $this->display_query( $query );
			$error_message = $this->connection->error . ':'.$Q;
			trigger_error( $error_message, E_USER_ERROR );
			return null;
		}

		if( $this->connection->warning_count )
		{
			$Q = $this->display_query( $query );
			$warnings = $this->getRecords( "SHOW WARNINGS" );
			foreach( $warnings as $warning )
			{
				// The columns are "Level", "Code", "Message".
				$msg = $warning['Message'];
				$msg .= ' *** The query: ' . $Q;
				trigger_error( $msg, E_USER_WARNING );
			}
		}
		return $r;
	}

	private function display_query( $q )
	{
		$lines = preg_split( '/\r\n?/', $q );

		/*
		 * Calculate common indent.
		 */
		$indent = 999;
		foreach( $lines as $line ) {
			if( $line == "" ) continue;
			$i = 0;
			$n = strlen( $line );
			while( $i < $n && $line[$i] == "\t" ) $i++;
			if( $i < $indent ) $indent = $i;
		}

		/*
		 * Remove the indent and add numbers.
		 */
		foreach( $lines as $i => $line ) {
			$lines[$i] = ($i + 1) . "\t". substr( $line, $indent );
		}
		return "\n" . implode( "\n", $lines ) . "\n";
	}

	/*
	 * Returns a stream object (see below) for the given query.
	 */
	function getStream( $template, $args = null )
	{
		$args = func_get_args();
		$query = $this->construct_query( $args );
		return new __mysql_stream( $this->execs( $query ) );
	}

	private function construct_query( $args )
	{
		$n = count( $args );
		if( $n == 1 ) {
			return $args[0];
		}
		/*
		 * First argument ($template) is a sprintf template and is
		 * considered safe (without injections). All other arguments
		 * ($__args__) are to be escaped.
		 */
		for( $i = 1; $i < $n; $i++ ) {
			$args[$i] = $this->escape( $args[$i] );
			if( is_string( $args[$i] ) ) {
				$args[$i] = str_replace( '%', '%%', $args[$i] );
			}
		}
		return call_user_func_array( 'sprintf', $args );
	}

	function prepare( $query ) {
		if( !$this->connected ) {
			$this->connect();
		}
		return new __mysql_statement( $this->connection, $query );
	}

	/* Escapes given value or array of values. */
	function escape( $var )
	{
		if( is_array( $var ) )
		{
			foreach( $var as $k => $v ){
				$var[$k] = $this->escape( $v );
			}
			return $var;
		}
		if( $var === null ){
			return null;
		}
		if( !$this->connected ) {
			$this->connect();
		}
		return str_replace( '%', '%%',
			$this->connection->real_escape_string( $var ) );
	}

	function insertId() {
		return $this->connection->insert_id;
	}

	/* Fetches one associative array with the given query. */
	function getRecord( $mysql_query, $_args_ = null )
	{
		$args = func_get_args();
		$r = call_user_func_array( array( $this, 'exec' ), $args );

		$row = $r->fetch_assoc();
		if( $row ) {
			$row = $this->cast_types( $row, $this->typeinfo( $r ) );
		}
		$r->free();
		return $row;
	}

	/* Fetches array of associative arrays from the query. */
	function getRecords( $mysql_query, $_args_ = null )
	{
		$args = func_get_args();
		$r = call_user_func_array( array( $this, 'exec' ), $args );

		$ae = array();
		$info = $this->typeinfo( $r );
		while( $e = $r->fetch_assoc() ) {
			$ae[] = $this->cast_types( $e, $info );
		}
		$r->free();
		return $ae;
	}

	private $ints = array(
		MYSQLI_TYPE_TINY,
		MYSQLI_TYPE_SHORT,
		MYSQLI_TYPE_LONG,
		MYSQLI_TYPE_LONGLONG,
		MYSQLI_TYPE_INT24
	);

	private $floats = array(
		MYSQLI_TYPE_FLOAT,
		MYSQLI_TYPE_DOUBLE,
		MYSQLI_TYPE_DECIMAL,
		MYSQLI_TYPE_NEWDECIMAL
	);

	private function typeinfo( $result )
	{
		$info = array();
		$F = $result->fetch_fields();
		foreach( $F as $f )
		{
			$k = $f->name;
			if( in_array( $f->type, $this->ints ) ) {
				$info[$k] = 'int';
				continue;
			}
			if( in_array( $f->type, $this->floats ) ) {
				$info[$k] = 'flt';
				continue;
			}
			$info[$k] = $f->type;
		}
		return $info;
	}

	private function cast_types( $a, $info )
	{
		foreach( $a as $k => $v )
		{
			if( $v === null ) continue;

			switch( $info[$k] )
			{
				case "int":
					$a[$k] = intval( $v );
					break;
				case "flt":
					$a[$k] = floatval( $v );
					break;
			}
		}
		return $a;
	}


	function getValue( $mysql_query, $_args_ = null )
	{
		$args = func_get_args();
		$r = call_user_func_array( array( $this, 'exec' ), $args );

		$row = $r->fetch_row();
		if( $row )
		{
			$info = array_values( $this->typeinfo( $r ) );
			$row = $this->cast_types( $row, $info );
		}
		$r->free();
		if( $row ) {
			return $row[0];
		}
		return null;
	}

	/* Fetches queried scalar values. */
	function getValues( $mysql_query, $args = null )
	{
		$args = func_get_args();
		$result = call_user_func_array( array( $this, 'exec' ), $args );
		$values = array();
		$info = array_values( $this->typeinfo( $result ) );
		while( $row = $result->fetch_row() ) {
			$row = $this->cast_types( $row, $info );
			$values[] = $row[0];
		}
		$result->free();
		return $values;
	}

	/*
	 * Inserts a row into a table.
	 * Returns primary key of the inserted row.
	 */
	function insertRecord( $table, $record, $ignore = false )
	{
		$record = $this->escape( $record );

		$header = $this->header_string( array_keys( $record ) );
		$tuple = $this->tuple_string( $record );

		$this->exec(
			"INSERT " . ( $ignore ? " IGNORE" : "" )
			. " INTO `$table` $header VALUES $tuple"
		);

		return $this->insertId();
	}

	/* Inserts multiple rows into a table. */
	function insertRecords( $table, $records )
	{
		if( empty( $records ) ){
			return false;
		}

		$records = $this->escape( $records );

		$header = $this->header_string( array_keys( $records[0] ) );

		$tuples = array();
		foreach( $records as $record ){
			$tuples[] = $this->tuple_string( $record );
		}
		$tuples = implode( ', ', $tuples );

		return $this->exec( "INSERT INTO `$table` $header
			VALUES $tuples"
		);
	}

	/*
	 * Returns true if at least one record conforming to the given
	 * filter exists in the given table.
	 */
	function exists( $table, $filter )
	{
		$table = $this->escape( $table );

		$q = "SELECT 1 FROM `$table` WHERE "
			. $this->buildCondition( $filter );
		return (bool) $this->getValue( "SELECT EXISTS ($q)" );
	}

	/*
	 * Updates records of the table with given name.
	 *
	 * This method escapes everything in the $record and $filter,
	 * so don't escape them.
	 *
	 * The two examples below are equivalent:
	 * // 1
	 * updateRecords( 'tbl',
	 * 	array( 'id' => 42, 'field' => 'new-value' ), // the update
	 *  'id' // the filter
	 * );
	 * // 2
	 * updateRecords( 'tbl',
	 * 	array( 'field' => 'new-value' ), // the update
	 * 	array( 'id' => 42 ) // the filter
	 * );
	 *
	 * Form 2 can have more complex filters though
	 */
	function updateRecords( $table, $record, $filter, $limit = null )
	{
		// if the filter is a string, convert it to array
		if( is_string( $filter ) )
		{
			$filter_field = $filter;
			$filter = array( $filter => $record[$filter] );

			// we don't need this value anymore
			// since it's a part of the filter
			unset( $record[$filter_field] );
		}

		// build the condition
		$where = $this->buildCondition( $filter );

		// escape the values
		$record = $this->escape( $record );

		// build the update statement
		$tmp = array();
		foreach( $record as $field => $value )
		{
			if( $value === null ){
				$tmp[] = "`$field` = NULL";
				continue;
			}
			$tmp[] = "`$field` = '$value'";
		}
		$set = implode( ', ', $tmp );

		// run the update
		$q = "UPDATE $table SET $set WHERE $where";
		if( $limit ) $q .= ' LIMIT '.intval( $limit );
		$r = $this->exec( $q );
		if( $r ){
			return $this->connection->affected_rows;
		} else {
			return $r;
		}
	}

	function updateRecord( $table, $record, $filter ){
		return $this->updateRecords( $table, $record, $filter, 1 );
	}

	function deleteRecords( $table_name, $filter, $value = null, $limit = null )
	{
		if( is_string( $filter ) ){
			$filter = array( $filter => $value );
		}

		$condition = $this->buildCondition( $filter );
		$q = "DELETE FROM $table_name WHERE $condition";
		if( $limit ) $q .= ' LIMIT '.intval( $limit );
		$this->exec( $q );
		return $this->connection->affected_rows;
	}

	function deleteRecord( $table_name, $filter, $value = null )
	{
		if( is_string( $filter ) ){
			$filter = array( $filter => $value );
		}
		return $this->deleteRecords( $table_name, $filter, null, 1 );
	}

	/* Creates condition clause for a query. */
	function buildCondition( $filter )
	{
		$filter = $this->escape( $filter );
		$parts = array();
		foreach( $filter as $field_name => $field_value )
		{
			if( $field_value === null )
			{
				$parts[] = "`$field_name` IS NULL";
				continue;
			}

			if( is_array( $field_value ) ){
				$parts[] = "`$field_name` IN ( '".implode( "', '", $field_value )."' )";
			} else {
				$parts[] = "`$field_name` = '$field_value'";
			}
		}
		$condition = implode( ' AND ', $parts );
		return $condition;
	}

	private function tuple_string( $tuple )
	{
		$values = array();
		foreach( $tuple as $value )
		{
			if( $value === null ){
				$values[] = 'NULL';
			} else {
				$values[] = "'$value'";
			}
		}

		$t = '(' . implode( ', ', $values ) .  ')';
		return $t;
	}

	private function header_string( $header )
	{
		return '(`' . implode( "`, `", $header ) . '`)';
	}
}

class __mysql_stream
{
	private $result;

	function __construct( $result ) {
		$this->result = $result;
	}

	function __destruct() {
		$this->free();
	}

	function free() {
		if( $this->result ) {
			$this->result->free();
			$this->result = null;
		}
	}

	function getRecord() {
		return $this->result->fetch_assoc();
	}

	function getValue() {
		$r = $this->result->fetch_row();
		return $r[0];
	}
}

class __mysql_statement
{
	private $s;

	function __construct( $conn, $query ) {
		$this->s = $conn->prepare( $query );
	}

	function __destruct() {
		$this->free();
	}

	function free() {
		if( !$this->s ) return;
		$this->s->close();
		$this->s = null;
	}

	function exec( $args___ )
	{
		$args = func_get_args();
		$types = $this->bind_types( $args );
		/*
		 * Make an array of references for the bind_param call.
		 */
		$a = array( $types );
		$n = count( $args );
		for( $i = 0; $i < $n; $i++ ) {
			$a[] = &$args[$i];
		}
		call_user_func_array( array( $this->s, 'bind_param' ), $a );
		return $this->s->execute();
	}

	private function bind_types( $args )
	{
		$types = "";
		foreach( $args as $arg )
		{
			if( is_int( $arg ) ) {
				$types .= "i";
				continue;
			}

			if( is_double( $arg ) ) {
				$types .= "d";
				continue;
			}

			if( is_string( $arg ) ) {
				$types .= "s";
				continue;
			}

			// blob (b) is not used
			trigger_error( "Unsupported argument type" );
		}
		return $types;
	}

	function scan( &$arg1,
		&$arg2 = null, &$arg3 = null, &$arg4 = null,
		&$arg5 = null, &$arg6 = null, &$arg7 = null, &$arg8 = null )
	{
		/*
		 * As of PHP 5, there is no obvious way to accept a variable
		 * number of arguments by references. func_get_args seems to
		 * return copies. So we hardcode some maximum number of
		 * arguments.
		 */
		$n = func_num_args();
		if( $n > 8 ) {
			trigger_error( "Too many arguments" );
		}
		$args = array();

		for( $i = 1; $i <= $n; $i++ ) {
			$name = 'arg'.$i;
			$args[] = &$$name;
		}

		call_user_func_array( array( $this->s, 'bind_result' ), $args );
		return $this->s->fetch();
	}
}

?>

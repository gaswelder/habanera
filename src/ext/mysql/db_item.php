<?php
/*
 * This class represents a row in a database table. Extend this class
 * redefining $table_name and $table_key (the primary key column's
 * name).
 */

abstract class db_item
{
	/*
	 * Name of the table with items.
	 */
	protected $table_name = 'items';
	/*
	 * Primary key name for the items.
	 */
	protected $table_key = 'id';
	/*
	 * Column with parent identifiers for parent-children queries.
	 */
	protected $parent_key = 'parent_id';


	private $child_nodes = null;
	private $ancestor_nodes = null;

	/*
	 * Parent node. False means "unknown", null means "no parent".
	 */
	private $parent_node = false;


	/*
	 * Primary key of this item, a value to the table's key.
	 */
	protected $id = null;

	protected $edited = false;
	protected $valid = null;

	// data cache
	protected $data = array();

	/*
	 * $preload is a comma-separated list of fields to load from the
	 * table. If later some field will be requested that was not
	 * preloaded, it will be loaded in a separate query.
	 */
	function __construct( $item_id = null, $preload = '' )
	{
		/* If this is a new record, we handle validity differently. */
		if( $item_id == null )
		{
			/*
			 * Requesting preload on a new item does not make sense.
			 */
			if( !empty( $preload ) )
			{
				error( "Trying to preload data ($preload) from undefined item_id" );
				return;
			}

			$this->valid = true;
			return;
		}

		/*
		 * Only numeric ids are allowed. String keys never were used
		 * anyway.
		 */
		if( is_string( $item_id ) && is_numeric( $item_id ) ) {
			$item_id = intval( $item_id );
		}
		if( !is_int( $item_id ) )
		{
			error( 'item_id passed to db_item constructor has wrong type ('.gettype( $item_id ).')' );
			return;
		}

		$this->id = $item_id;

		if( !trim( $preload ) ) return;

		// Get the data
		$data = DB::getRecord(
			"SELECT $preload FROM $this->table_name
			WHERE $this->table_key = '%s'",
				$this->id
		);

		// If got data, save it.
		if( $data )
		{
			$this->data = $data;
			$this->valid = true;
		}
		else
		{
			$this->value = false;
		}
	}

	function id() {
		return $this->id;
	}

	function valid()
	{
		if( $this->valid === null )
		{
			$c = DB::getValue( "SELECT COUNT(*) FROM $this->table_name
				WHERE $this->table_key = '$this->id'"
			);
			$this->valid = ( intval( $c ) > 0 );
		}
		return $this->valid;
	}

	// General template for getset
	function __call( $name, $arguments )
	{
		if( count( $arguments ) > 0 ){
			$arg = $arguments[0];
		} else {
			$arg = false;
		}
		return $this->getset( $name, $arg );
	}

	// Save changes to the database.
	function save()
	{
		if( !$this->id ) {
			$this->id = DB::insertRecord( $this->table_name, $this->data );
		}
		else if( $this->edited == true )
		{
			$filter = array( $this->table_key => $this->id );
			DB::updateRecord(
				$this->table_name, $this->data, $filter
			);
			$this->edited = false;
		}
		return $this->id;
	}

	private function set( $key, $value )
	{
		if( !$this->valid() ) {
			error( 'Trying to modify invalid item.' );
			return null;
		}

		$this->data[$key] = $value;
		$this->edited = true;
		return $value;
	}

	/* Getter/setter. Returns value corresponding to the key.
	If a value is provided, sets it to the key.
	Keys are database fields for the item. */
	protected function getset( $key, $value = false )
	{
		if( $value !== false ) {
			return $this->set( $key, $value );
		}

		if( array_key_exists( $key, $this->data ) ) {
			return $this->data[$key];
		}

		if( !$this->id ) {
			return null;
		}

		$t = $this->table_name;
		$k = $this->id;
		$col = $key;

		$this->data[$key] = DB::getValue(
			"SELECT `$key` FROM $this->table_name
			WHERE $this->table_key = '%s'",
			$this->id
		);

		return $this->data[$key];
	}

	/*
	 * Returns array of child nodes as instances of this class.
	 */
	function child_nodes()
	{
		if( !isset( $this->child_nodes ) )
		{
			$this->child_nodes = array();
			$class = get_class( $this );

			$ids = DB::getValues( "
				SELECT $this->table_key
				FROM $this->table_name
				WHERE $this->parent_key = %d", $this->id
			);

			foreach( $ids as $id ) {
				$this->child_nodes[] = new $class( $id );
			}
		}
		return $this->child_nodes;
	}

	/*
	 * Returns this node's parent node, or null.
	 */
	function parent_node()
	{
		if( $this->parent_node === false )
		{
			$id = DB::getValue("
				SELECT $this->parent_key
				FROM $this->table_name
				WHERE $this->table_key = %d
				", $this->id
			);
			$class = get_class( $this );
			$this->parent_node = $id ? new $class( $id ) : null;
		}
		return $this->parent_node;
	}

	/*
	 * Returns array of ancestor nodes in order from the root to this
	 * node's parent.
	 */
	function ancestor_nodes()
	{
		if( !$this->ancestor_nodes )
		{
			$this->ancestor_nodes = array();
			$node = $this->parent_node();
			while( $node )
			{
				array_unshift( $this->ancestor_nodes, $node );
				$node = $node->parent_node();
			}
		}
		return $this->ancestor_nodes;
	}
}
?>

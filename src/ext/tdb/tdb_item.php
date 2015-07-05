<?php
class tdb_item
{
	protected $dir = 'objects';
	private $data = array();
	protected $id;

	function __construct( $id = null )
	{
		$this->id = $id;
		if( $id ) {
			$this->data = tdb::get_object( $this->dir, $id );
		}
	}

	function valid()
	{
		return !$this->id || is_array( $this->data );
	}

	function __call( $name, $arguments )
	{
		if( count( $arguments ) > 0 )
		{
			$value = $arguments[0];
			return $this->set( $name, $value );
		}
		else
		{
			return $this->get( $name );
		}
	}

	protected function get( $key )
	{
		if( !$this->data ) return null;
		if( array_key_exists( $key, $this->data ) ) {
			return $this->data[$key];
		}
		else return null;
	}

	protected function set( $key, $value )
	{
		$this->data[$key] = $value;
	}

	function save()
	{
		if( $this->id )
		{
			return tdb::update_object(
				$this->dir, $this->id, $this->data );
		}
		else {
			return tdb::save_object(
				$this->dir, $this->data );
		}
	}
}

?>

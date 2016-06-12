<?php

class _mime
{
	private static $types = array(
		'.xls' => 'application/vnd.ms-excel',
		'.xlsx' => 'application/vnd.openxmlformats-officedocument'.
			'.spreadsheetml.sheet',
		'.zip' => 'application/zip',
		'.sh' => 'application/x-shellscript',
		'.png' => 'image/png',
		'.jpg' => 'image/jpeg',
		'.gif' => 'image/gif',
		'' => 'application/octet-stream'
	);

	static function type( $ext ) {
		$ext = strtolower( $ext );
		if( isset( self::$types[$ext] ) ) {
			return self::$types[$ext];
		}
		return null;
	}

	static function ext( $type ) {
		$type = strtolower( $type );
		foreach( self::$types as $ext => $ptype ) {
			if( $ptype == $type ) {
				return $ext;
			}
		}
		return null;
	}
}

?>

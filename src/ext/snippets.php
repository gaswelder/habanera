<?php

//
// A collection of commonly used HTML "idioms".
// These are very specific to the engine and are not reusable in other
// places. For general HTML idioms, see HTMLSnippets.
//

class snippets
{
	static function pagebar( $url_template, $total_pages, $current_page = 1 )
	{
		if( $total_pages < 2 ) return '';

		if( $current_page > 1 )
		{
			$prev_url = str_replace( 'PAGE', $current_page - 1, $url_template );
			add_link( 'prev', $prev_url );
		}

		if( $current_page + 1 <= $total_pages )
		{
			$next_url = str_replace( 'PAGE', $current_page + 1, $url_template );
			add_link( 'next', $next_url );
		}

		$numbers = self::page_numbers( $current_page, $total_pages );
		ob_start();
		?>
		<nav class="pagebar">
			<ol>
				<?php foreach( $numbers as $num ){
					if( $num == $current_page ){
						?><li><a><?= $num ?></a></li><?php
					} else if( $num == 0 ){
						?><li>...</li><?php
					} else{
						$url = str_replace( 'PAGE', $num, $url_template );
						?><li><a href="<?= $url ?>"><?= $num ?></a></li><?php
					}
				}?>
			</ol>
		</nav>
		<?php
		return ob_get_clean();
	}

	private static function page_numbers( $current_page, $total_pages, $marker = 0 )
	{
		if( $total_pages <= 10 ){
			return range( 1, $total_pages );
		}

		$x = $current_page;
		$n = $total_pages;
		$m = 3; // margin

		$xrange = array();
		if( $x == $total_pages )
		{
			$xrange[] = $x-2;
			$xrange[] = $x-1;
			$xrange[] = $x;
		}
		else if( $x > 1 )
		{
			$xrange[] = $x-1;
			$xrange[] = $x;
			$xrange[] = $x+1;
		}

		$a = (int)round( $x/2 );

		if( 3 + $m + 1 + $m < $x-1 )
		{
			$pages = range( 1, 3 );
			$pages[] = $marker;
			$pages[] = $a;
			$pages[] = $marker;
			$pages = array_merge( $pages, $xrange );
		}
		else if( $x > 3 + $m + 1 ){
			$pages = range( 1, 3 );
			$pages[] = $marker;
			$pages = array_merge( $pages, $xrange );
		}
		else{
			$pages = range( 1, $x );
			if( $x < $total_pages ){
				$pages[] = $x+1;
			}
			if( $x == 1 ){
				$pages[] = $x+2;
			}
		}

		$b = (int)round( ($n+$x)/2 );

		if( $x + 1 + $m + 1 + $m + 3 < $n )
		{
			$pages[] = $marker;
			$pages[] = $b;
			$pages[] = $marker;
			$pages = array_merge( $pages, range( $n-2, $n ) );
		}
		else if( $x + 1 + $m < $n ){
			$pages[] = $marker;
			$pages = array_merge( $pages, range( $n-2, $n ) );
		}
		else if( $xrange[2] < $n ){
			$pages = array_merge( $pages, range( max( $n-2, $xrange[2] )+1, $n ) );
		}
		return $pages;
	}

	static function breadcrumbs( $elements )
	{
		?><nav class="breadcrumbs">
			<ul>
				<?php foreach( $elements as $e )
				{
					list( $title, $url ) = $e;
					if( $url ){
						?><li><a href="<?= $url ?>"><?= $title ?></a></li><?php
					} else {
						?><li><?= $title ?></li><?php
					}
				}?>
			</ul>
		</nav>
		<?php
	}

	static function action_errors( $action_name = null )
	{
		$errors = action_errors( $action_name );
		if( !$errors ) return;
		ob_start();
		foreach( $errors as $error ){
			?><p class="error"><?= $error ?></p><?php
		}
		return ob_get_clean();
	}

	/*
	Creates a listbox. $items can be:
	1) indexed array (option titles, numbers will be used as values),
	2) associative array (option value => option title),
	3) array of two arrays (option keys, option titles).

	$selected_key tells the index of the item that should be selected.
	It can be also an array of selected keys (in case of multiple
	select).

	By default an empty entry is added to the array unless $add_empty is
	set to false or the array already has element with index ''.

	$name can be:
	1) string value for "name" attribute
	2) associative array (attrbute name => attribute value) for
	arbitrary attributes.

	$empty_option has the default title for the option that has empty
	value. If set to null, no empty option is added, unless it is
	present in $items.
	*/
	static function select( $name, $items, $selected_key = '',
		$empty_option = '' )
	{
		if( isset( $items[0] ) && is_array( $items[0] ) ){
			$keys = $items[0];
			$titles = $items[1];
		} else {
			$keys = array_keys( $items );
			$titles = array_values( $items );
		}

		if( is_array( $selected_key ) ){
			$selected_keys = $selected_key;
		} else {
			$selected_keys = array( $selected_key );
		}

		$lines = array( '<select '.self::tagparameters( $name ).'>' );

		if( $empty_option !== null && !isset( $items[''] ) ){
			$lines[] = '<option value="">'.$empty_option.'</option>';
		}

		foreach( $keys as $i => $key )
		{
			$s = '<option value="'.htmlspecialchars( $key ).'"';
			if( in_array( $key, $selected_keys ) ){
				$s .= ' selected';
			}
			$s .= '>'.$titles[$i] .'</option>';
			$lines[] = $s;
		}
		$lines[] = '</select>';
		return PHP_EOL . implode( PHP_EOL, $lines );
	}

	static function checkbox( $name, $checked = false, $value = null )
	{
		$s = '<input type="checkbox" '.self::tagparameters( $name );
		if( $checked ) $s .= ' checked';
		if( $value !== null ){
			$s .= ' value="'.$value.'"';
		}
		$s .= '>';
		return $s;
	}

	/*
	 * Array of generated "id" values that have already been used.
	 */
	private static $used_ids = array();

	private static function generate_id( $name, $prefix = '' )
	{
		$id = $prefix . str_replace( '[]', '', $name );
		if( isset( self::$used_ids[$id] ) ) {
			$id .= self::$used_ids[$id]++;
		} else {
			self::$used_ids[$id] = 1;
		}
		return $id;
	}

	/*
	 * Creates a checkbox with a label that has reference ("for"
	 * attribute) to the checkbox. If "id" parameter is not given in the
	 * $other array, it is generated from the $name.
	 */
	static function labelled_checkbox( $label, $name,
		$value = '1', $checked = false, $other = array() )
	{
		$props = array(
			'name' => $name,
			'value' => $value,
			'checked' => (bool)$checked
		);
		$props = array_merge( $props, $other );
		if( !isset( $props['id'] ) ) {
			$props['id'] = self::generate_id( $name, '_checkbox_' );
		}

		$s = '<input type="checkbox" ';
		$s .= self::tagparameters( $props );
		$s .= '>'.PHP_EOL;
		$s .= '<label for="'.$props['id'].'">'.$label.'</label>';
		return $s;
	}

	static function pubdate( $time, $format = 'd.m.Y, H:i' )
	{
		$datetime = date( 'Y-m-d\TH:i:sO', $time );
		$formatted = date( $format, $time );
		return '<time datetime="'.$datetime.'">'.$formatted.'</time>';
	}

	static function link( $href, $text ){
		return sprintf( '<a href="%s">%s</a>', $href, $text );
	}

	static function ul( $items ){
		return '<ul><li>'.implode( '</li><li>', $items ).'</li></ul>';
	}

	static function ol( $items ){
		return '<ol><li>'.implode( '</li><li>', $items ).'</li></ol>';
	}

	static function image( $src, $alt = "" ){
		return '<img src="'.$src.'" alt="'.htmlspecialchars( $alt ).'">';
	}

	static function dl( $items )
	{
		if( !count( $items ) ) return '';

		$s = '<dl>';
		foreach( $items as $t => $d ){
			$s .= "<dt>$t</dt><dd>$d</dd>";
		}
		$s .= '</dl>';
		return $s;
	}

	private static function tagparameters( $name_or_array )
	{
		if( !is_array( $name_or_array ) ){
			return ' name="'.$name_or_array.'"';
		}

		$s = array();
		foreach( $name_or_array as $name => $value )
		{
			if( is_bool( $value ) ){
				if( $value ){
					$s[] = $name;
				}
			} else {
				$s[] = sprintf( '%s="%s"', $name, $value );
			}
		}
		return implode( ' ', $s );
	}
}

?>

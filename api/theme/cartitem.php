<?php
/**
 * cartitem.php
 *
 * ShoppCartItemThemeAPI provides shopp('cartitem') Theme API tags
 *
 * @api
 * @copyright Ingenesis Limited, 2012-2013
 * @package shopp
 * @since 1.2
 * @version 1.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

add_filter('shopp_cartitem_input_data', 'wpautop');

/**
 * Provides support for the shopp('cartitem') tags
 *
 * @author Jonathan Davis, John Dillick
 * @since 1.2
 *
 **/
class ShoppCartItemThemeAPI implements ShoppAPI {
	static $register = array(
		'_cartitem',
		'id' => 'id',
		'product' => 'product',
		'name' => 'name',
		'type' => 'type',
		'link' => 'url',
		'url' => 'url',
		'sku' => 'sku',
		'description' => 'description',
		'discount' => 'discount',
		'unitprice' => 'unitprice',
		'unittax' => 'unittax',
		'discounts' => 'discounts',
		'tax' => 'tax',
		'total' => 'total',
		'taxrate' => 'taxrate',
		'quantity' => 'quantity',
		'remove' => 'remove',
		'onsale' => 'onsale',
		'optionlabel' => 'option_label',
		'options' => 'options',
		'price' => 'price',
		'prices' => 'prices',
		'hasaddons' => 'has_addons',
		'addons' => 'addons',
		'addon' => 'addon',
		'addonslist' => 'addons_list',
		'hasinputs' => 'has_inputs',
		'incategory' => 'in_category',
		'inputs' => 'inputs',
		'input' => 'input',
		'inputslist' => 'inputs_list',
		'coverimage' => 'coverimage',
		'thumbnail' => 'coverimage',
		'saleprice' => 'saleprice',
		'saleprices' => 'saleprices',
	);

	public static function _apicontext () {
		return 'cartitem';
	}

	/**
	 * _setobject - returns the global context object used in the shopp('cartitem) call
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 **/
	public static function _setobject ( $Object, $object ) {
		if ( is_object($Object) && is_a($Object, 'Item') ) return $Object;

		if (strtolower($object) != 'cartitem') return $Object; // not mine, do nothing
		else {
			$Cart = ShoppOrder()->Cart;
			$Item = false;
			if ( isset($Cart->_item_loop) ) { $Item = $Cart->current(); $Item->_id = $Cart->key(); return $Item; }
			elseif ( isset($Cart->_shipped_loop) ) { $Item = current($Cart->shipped); $Item->_id = key($Cart->shipped); return $Item; }
			elseif ( isset($Cart->_downloads_loop) ) { $Item = current($Cart->downloads); $Item->_id = key($Cart->downloads); return $Item; }
			return false;
		}
	}

	public static function _cartitem ( $result, $options, $property, $O ) {

		// Passthru for non-monetary results
		$monetary = array('discount', 'unitprice', 'unittax', 'discounts', 'tax', 'total', 'price', 'prices', 'saleprice', 'saleprices');
		if ( ! in_array($property, $monetary) || ! is_numeric($result) ) return $result;

		// @deprecated currency parameter
		if ( isset($options['currency']) ) $options['money'] = $options['currency'];

		$defaults = array(
			'money' => 'on',
			'number' => false,
			'show' => ''
		);
		$options = array_merge($defaults, $options);
		extract($options);

		if ( in_array($show, array('%', 'percent')) )
			return $result; // Pass thru percentage rendering

		if ( Shopp::str_true($number) ) return $result;
		if ( Shopp::str_true($money)  ) $result = money( roundprice($result) );

		return $result;

	}

	public static function id ( $result, $options, $O ) {
		return $O->_id;
	}

	public static function product ( $result, $options, $O ) {
		if ( isset($options['priceline']) && Shopp::str_true($options['priceline']) )
			return $O->priceline;
		return $O->product;
	}

	public static function name ( $result, $options, $O ) {
		return $O->name;
	}

	public static function type ( $result, $options, $O ) {
		return $O->type;
	}

	public static function url ( $result, $options, $O ) {
		return Shopp::url( '' == get_option('permalink_structure') ? array(ShoppProduct::$posttype => $O->slug ) : $O->slug, false );
	}

	public static function sku ( $result, $options, $O ) {
		return $O->sku;
	}

	public static function description ( $result, $options, $O ) {
		return $O->description;
	}

	public static function discount ( $result, $options, $O ) {

		$defaults = array(
			'catalog' => false,
			'show' => ''
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		// Item unit discount
		$discount = $O->discount;

		if ( Shopp::str_true($catalog) )
			$discount = $O->option->price - $O->option->promoprice;

		if ( in_array($show, array('%', 'percent')) )
			return percentage( ( $discount / $O->option->price ) * 100, array('precision' => 0) );

		return (float) $discount;
	}

	public static function discounts ( $result, $options, $O ) {
		$defaults = array(
			'catalog' => false,
			'show' => ''
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		// Unit discount * quantity
		$discounts = $O->discounts;

		if ( Shopp::str_true($catalog) )
			$discounts =  $O->quantity * self::discount('', array('catalog' => true, 'number' => true), $O);

		if ( in_array($show, array('%', 'percent')) )
			return percentage( ( $discounts / self::prices('', array('number' => true), $O) ) * 100, array('precision' => 0) );

		return (float) $discounts;
	}

	public static function unitprice ( $result, $options, $O ) {

		$taxes = isset( $options['taxes'] ) ? Shopp::str_true( $options['taxes'] ) : null;

		$unitprice = (float) $O->unitprice;
		$unitprice = self::_taxes($unitprice, $O, $taxes);
		return (float) $unitprice;
	}

	public static function unittax ( $result, $options, $O ) {
		return (float) $O->unittax;
	}


	public static function tax ( $result, $options, $O ) {
		return (float) $O->tax;
	}

	public static function total ( $result, $options, $O ) {
		$taxes = isset( $options['taxes'] ) ? Shopp::str_true( $options['taxes'] ) : null;

		$total = (float) $O->total;
		$total = self::_taxes($total, $O, $taxes, $O->quantity);

		return (float) $total;
	}

	public static function taxrate ( $result, $options, $O ) {
		return percentage( $O->taxrate * 100, array( 'precision' => 1 ) );
	}

	public static function quantity ( $result, $options, $O ) {
		$result = $O->quantity;
		if ( 'Donation' === $O->type && 'on' === $O->donation['var'] ) return $result;
		if ( 'Subscription' === $O->type || 'Membership' === $O->type ) return $result;
		if ( 'Download' === $O->type && ! shopp_setting_enabled('download_quantity') ) return $result;
		if ( isset($options['input']) && 'menu' === $options['input'] ) {
			if ( ! isset($options['value']) ) $options['value'] = $O->quantity;
			if ( ! isset($options['options']) )
				$values = '1-15,20,25,30,35,40,45,50,60,70,80,90,100';
			else $values = $options['options'];

			if ( strpos($values, ',') !== false ) $values = explode(',', $values);
			else $values = array($values);
			$qtys = array();
			foreach ( $values as $value ) {
				if ( false !== strpos($value,'-') ) {
					$value = explode("-", $value);
					if ($value[0] >= $value[1]) $qtys[] = $value[0];
					else for ($i = $value[0]; $i < $value[1]+1; $i++) $qtys[] = $i;
				} else $qtys[] = $value;
			}
			$result = '<select name="items['.$O->_id.'][quantity]">';
			foreach ( $qtys as $qty )
				$result .= '<option' . ( ($qty == $O->quantity) ? ' selected="selected"' : '' ) . ' value="' . $qty . '">' . $qty . '</option>';
			$result .= '</select>';
		} elseif ( isset($options['input']) && Shopp::valid_input($options['input']) ) {
			if ( ! isset($options['size']) ) $options['size'] = 5;
			if ( ! isset($options['value']) ) $options['value'] = $O->quantity;
			$result = '<input type="' . $options['input'] . '" name="items[' . $O->_id . '][quantity]" id="items-' . $O->_id . '-quantity" ' . inputattrs($options) . '/>';
		} else $result = $O->quantity;
		return $result;
	}

	public static function remove ( $result, $options, $O ) {
		$label = __('Remove', 'Shopp');
		if ( isset($options['label']) ) $label = $options['label'];
		if ( isset($options['class']) ) $class = ' class="'.$options['class'].'"';
		else $class = ' class="remove"';
		if ( isset($options['input']) ) {
			switch ($options['input']) {
				case "button":
					$result = '<button type="submit" name="remove[' . $O->_id . ']" value="' . $O->_id . '"' . $class . ' tabindex="">' . $label . '</button>'; break;
				case "checkbox":
				    $result = '<input type="checkbox" name="remove[' . $O->_id . ']" value="' . $O->_id . '"' . $class . ' tabindex="" title="' . $label . '"/>'; break;
			}
		} else {
			$result = '<a href="' . href_add_query_arg(array('cart' => 'update', 'item' => $O->_id, 'quantity' => 0), Shopp::url(false, 'cart')) . '"' . $class . '>' . $label . '</a>';
		}
		return $result;
	}

	public static function option_label ( $result, $options, $O ) {
		return $O->option->label;
	}

	public static function options ( $result, $options, $O ) {
		$class = "";
		if ( ! isset($options['before']) ) $options['before'] = '';
		if ( ! isset($options['after']) ) $options['after'] = '';
		if ( isset($options['show']) &&	strtolower($options['show']) == "selected" )
			return ( ! empty($O->option->label) ) ? $options['before'] . $O->option->label . $options['after'] : '';
		if ( isset($options['class']) ) $class = ' class="' . $options['class'] . '" ';
		if ( count($O->variants) > 1 ) {
			$result .= $options['before'];
			$result .= '<input type="hidden" name="items[' . $O->_id . '][product]" value="' . $O->product . '"/>';
			$result .= ' <select name="items[' . $O->_id . '][price]" id="items-' . $O->_id . '-price"' . $class . '>';
			$result .= $O->options($O->priceline);
			$result .= '</select>';
			$result .= $options['after'];
		}
		return $result;
	}

	public static function has_addons ( $result, $options, $O ) {
		reset($O->addons);
		return (count($O->addons) > 0);
	}

	public static function addons ( $result, $options, $O ) {
		if ( ! isset($O->_addons_loop) ) {
			reset($O->addons);
			$O->_addons_loop = true;
		} else next($O->addons);

		if ( false !== current($O->addons) ) return true;

		unset($O->_addons_loop);
		reset($O->addons);
		return false;
	}

	public static function addon ( $result, $options, $O ) {
		if ( empty($O->addons) ) return false;
		$addon = current($O->addons);
		$defaults = array(
			'separator' => ' '
		);
		$options = array_merge($defaults, $options);

		$fields = array('id', 'type', 'menu', 'label', 'sale', 'saleprice', 'price', 'inventory', 'stock', 'sku', 'weight', 'shipfee', 'unitprice');

		$fieldset = array_intersect($fields, array_keys($options));
		if ( empty($fieldset) ) $fieldset = array('label');

		$_ = array();
		foreach ($fieldset as $field) {
			switch ($field) {
				case 'menu':
					list($menus, $menumap) = self::_addon_menus();
					$_[] = isset( $menumap[ $addon->options ]) ? $menus[ $menumap[ $addon->options ] ] : '';
					break;
				case 'weight': $_[] = $addon->dimensions['weight'];
				case 'saleprice':
				case 'price':
				case 'shipfee':
				case 'unitprice':
					if ( $field === 'saleprice' ) $field = 'promoprice';
					if ( isset($addon->$field) ) {
						$_[] = ( isset($options['currency']) && Shopp::str_true($options['currency']) ) ?
							 money($addon->$field) : $addon->$field;
					}
					break;
				default:
					if (isset($addon->$field))
						$_[] = $addon->$field;
			}

		}
		return join($options['separator'],$_);
	}

	public static function addons_list ( $result, $options, $O ) {
		if ( empty($O->addons) ) return false;
		$defaults = array(
			'before' => '',
			'after' => '',
			'class' => '',
			'exclude' => '',
			'separator' => ': ',
			'prices' => true,
			'taxes' => shopp_setting('tax_inclusive')
		);
		$options = array_merge($defaults, $options);
		extract($options);

		$classes = !empty($class) ? ' class="' . esc_attr($class) . '"' : '';
		$excludes = explode(',', $exclude);
		$prices = Shopp::str_true($prices);
		$taxes = Shopp::str_true($taxes);

		// Get the menu labels list and addon options to menus map
		list($menus, $menumap) = self::_addon_menus();

		$result .= $before . '<ul' . $classes . '>';
		foreach ( $O->addons as $id => $addon ) {

			if ( in_array($addon->label, $excludes) ) continue;
			$menu = isset( $menumap[ $addon->options ]) ? $menus[ $menumap[ $addon->options ] ] . $separator : false;

			$price = ( Shopp::str_true($addon->sale) ? $addon->promoprice : $addon->price );
			if ($taxes && $O->taxrate > 0)
				$price = $price + ( $price * $O->taxrate );

			if ($prices) $pricing = " (" . ( $addon->price < 0 ?'-' : '+' ) . money($price) . ')';
			$result .= '<li>' . $menu . $addon->label . $pricing . '</li>';
		}
		$result .= '</ul>' . $after;
		return $result;
	}

	/**
	 * Helper function that maps the current cart item's addons to the cart item's configured product menu options
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return array A combined list of the menu labels list and addons menu map
	 **/
	private static function _addon_menus () {
		$menus = shopp_meta(shopp('cartitem.get-product'), 'product', 'options');
		$addonmenus = array();
		$menulabels = array();
		if ( isset($menus['a']) ) {
			foreach ($menus['a'] as $addonmenu) {
				$menulabels[ $addonmenu['id'] ] = $addonmenu['name'];
				foreach ( $addonmenu['options'] as $menuoption)
					$addonmenus[ $menuoption['id'] ] = $addonmenu['id'];
			}
		}
		return array($menulabels, $addonmenus);
	}

	public static function has_inputs ( $result, $options, $O ) {
		reset($O->data);
		return (count($O->data) > 0);
	}

	public static function in_category ( $result, $options, $O ) {
		if ( empty($O->categories) ) return false;
		if ( isset($options['id']) ) $field = "id";
		if ( isset($options['name']) ) $field = "name";
		foreach ( $O->categories as $id => $name ) {
			switch ( strtolower($field) ) {
				case 'id': if ($options['id'] == $id) return true;
				case 'name': if ($options['name'] == $name) return true;
			}
		}
		return false;
	}

	public static function inputs ( $result, $options, $O ) {
		if ( ! isset($O->_data_loop) ) {
			reset($O->data);
			$O->_data_loop = true;
		} else next($O->data);

		if ( false !== current($O->data) ) return true;

		unset($O->_data_loop);
		reset($O->data);
		return false;
	}

	public static function input ( $result, $options, $O ) {
		$data = current($O->data);
		$name = key($O->data);
		if ( isset($options['name']) ) return $name;
		return apply_filters('shopp_cartitem_input_data', $data);
	}

	public static function inputs_list ( $result, $options, $O ) {
		if ( empty($O->data) ) return false;
		$defaults = array(
			'class' => '',
			'exclude' => array(),
			'before' => '',
			'after' => '',
			'separator' => '<br />'
		);
		$options = array_merge($defaults, $options);
		extract($options);

		if ( ! empty($exclude) ) $exclude = explode(',', $exclude);

		$classes = '';
		if ( ! empty($class) ) $classes = ' class="' . $class . '"';

		$result .= $before . '<ul' . $classes . '>';
		foreach ( $O->data as $name => $data ) {
			if (in_array($name,$exclude)) continue;
			if (is_array($data)) $data = join($separator, $data);
			$result .= '<li><strong>' . $name . '</strong>: ' . apply_filters('shopp_cartitem_input_data', $data) . '</li>';
		}
		$result .= '</ul>'.$after;
		return $result;
	}

	public static function coverimage ( $result, $options, $O ) {
		if ( false === $O->image ) return false;
		$O->images = array($O->image);
		$options['index'] = 0;
		return ShoppStorefrontThemeAPI::image( $result, $options, $O );
	}

	public static function onsale ( $result, $options, $O ) {
		return Shopp::str_true( $O->sale );
	}

	// Returns the non-discounted price on an item
	public static function price ( $result, $options, $O ) {
		return $O->option->price;
	}

	// Returns the non-discounted line item total
	public static function prices ( $result, $options, $O ) {
		return ( $O->option->price * $O->quantity );
	}

	// Returns the sale price on an item
	public static function saleprice ( $result, $options, $O ) {
		return $O->option->promoprice;
	}

	// Returns the total sale price on a line item
	public static function saleprices ( $result, $options, $O ) {
		return ( $O->option->promoprice * $O->quantity );
	}

	private static function _inclusive_taxes ( ShoppCartItem $O ) {
		return shopp_setting_enabled('tax_inclusive') && $O->includetax;
	}

	/**
	 * Helper to apply or exclude taxes from a single amount based on inclusive tax settings and the tax option
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param float $amount The amount to add taxes to, or exclude taxes from
	 * @param ShoppProduct $O The product to get properties from
	 * @param boolean $istaxed Whether the amount can be taxed
	 * @param boolean $taxoption The Theme API tax option given the the tag
	 * @param array $taxrates A list of taxrates that apply to the product and amount
	 * @return float The amount with tax added or tax excluded
	 **/
	private static function _taxes ( $amount, ShoppCartItem $O, $taxoption = null, $quantity = 1) {
		// if ( empty($taxrates) ) $taxrates = Shopp::taxrates($O);

		if ( ! $O->istaxed ) return $amount;
		if ( 0 == $O->unittax ) return $amount;

		$inclusivetax = self::_inclusive_taxes($O);
		if ( isset($taxoption) && ( $inclusivetax ^ $taxoption ) ) {

			if ( $taxoption ) $amount += ( $O->unittax * $quantity );
			else $amount = $amount -= ( $O->unittax * $quantity );
		}

		return (float) $amount;
	}

}
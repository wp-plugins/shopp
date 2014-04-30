<?php
/**
 * product.php
 *
 * ShoppProductThemeAPI provides shopp('product') Theme API tags
 *
 * @api
 * @copyright Ingenesis Limted, 2012-2013
 * @package shopp
 * @since 1.2
 * @version 1.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

add_filter('shopp_themeapi_context_name', array('ShoppProductThemeAPI', '_context_name'));

// WP auto embed support
global $wp_embed;
if ( ! is_object($wp_embed) ) $wp_embed = new WP_Embed;

// Default text filters for product Theme API tags
add_filter('shopp_themeapi_product_name','convert_chars');
add_filter('shopp_themeapi_product_summary','convert_chars');
add_filter('shopp_themeapi_product_description', 'wptexturize');
add_filter('shopp_themeapi_product_description', 'convert_chars');
add_filter('shopp_themeapi_product_description', 'wpautop');
add_filter('shopp_themeapi_product_description', array($wp_embed, 'run_shortcode'), 11);
add_filter('shopp_themeapi_product_description', array($wp_embed, 'autoembed'), 11);
add_filter('shopp_themeapi_product_description', 'do_shortcode',12);
add_filter('shopp_themeapi_product_spec', 'wptexturize');
add_filter('shopp_themeapi_product_spec', 'convert_chars');
add_filter('shopp_themeapi_product_spec', array($wp_embed, 'run_shortcode'), 11);
add_filter('shopp_themeapi_product_spec', array($wp_embed, 'autoembed'), 11);
add_filter('shopp_themeapi_product_spec', 'do_shortcode', 12);

/**
 * Provides shopp('product') template API functionality
 *
 * @author Jonathan Davis, John Dillick
 * @since 1.2
 *
 **/
class ShoppProductThemeAPI implements ShoppAPI {
	static $context = 'ShoppProduct';
	static $register = array(
		'addon' => 'addon',
		'addons' => 'addons',
		'addtocart' => 'add_to_cart',
		'availability' => 'availability',
		'buynow' => 'buy_now',
		'categories' => 'categories',
		'category' => 'category',
		'coverimage' => 'coverimage',
		'description' => 'description',
		'donation' => 'quantity',
		'amount' => 'quantity',
		'quantity' => 'quantity',
		'found' => 'found',
		'freeshipping' => 'free_shipping',
		'gallery' => 'gallery',
		'hasaddons' => 'has_addons',
		'hascategories' => 'has_categories',
		'hassavings' => 'has_savings',
		'hasvariations' => 'has_variations',
		'hasimages' => 'has_images',
		'hasspecs' => 'has_specs',
		'hastags' => 'has_tags',
		'id' => 'id',
		'image' => 'image',
		'thumbnail' => 'image',
		'images' => 'images',
		'incart' => 'in_cart',
		'incategory' => 'in_category',
		'input' => 'input',
		'isfeatured' => 'is_featured',
		'link' => 'url',
		'url' => 'url',
		'name' => 'name',
		'onsale' => 'on_sale',
		'outofstock' => 'out_of_stock',
		'price' => 'price',
		'saleprice' => 'saleprice',
		'relevance' => 'relevance',
		'savings' => 'savings',
		'schema' => 'schema',
		'slug' => 'slug',
		'spec' => 'spec',
		'specs' => 'specs',
		'summary' => 'summary',
		'sku' => 'sku',
		'stock' => 'stock',
		'tag' => 'tag',
		'tagged' => 'tagged',
		'tags' => 'tags',
		'taxrate' => 'taxrate',
		'type' => 'type',
		'variation' => 'variation',
		'variations' => 'variations',
		'weight' => 'weight'
	);

	public static function _apicontext () {
		return 'product';
	}

	public static function _context_name ( $name ) {
		switch ( $name ) {
			case 'product':
			case 'shoppproduct':
				return 'product';
				break;
		}
		return $name;
	}

	/**
	 * _setobject - returns the global context object used in the shopp('product') call
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 **/
	public static function _setobject ($Object, $object) {
		if ( is_object($Object) && is_a($Object, 'ShoppProduct') ) return $Object;

		if ( strtolower($object) != 'product' ) return $Object; // not mine, do nothing
		else {
			return ShoppProduct();
		}
	}

	public static function addon ( $result, $options, $O ) {

		$defaults = array(
			'separator' => ' ',
			'units' => 'on',
			// 'promos' => 'on', @deprecated option use 'discounts'
			'discounts' => 'on',
			'taxes' => null,
			'input' => null,
			'money' => 'on',
			'number' => 'off'
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		if ( isset($promos) ) $discounts = $promos; // @deprecated support for deprecated `promos` option

		$types = array('hidden','checkbox','radio');

		$addon = current($O->prices);

		$taxes = isset($taxes) ?  Shopp::str_true($taxes) : null;
		$weightunit = Shopp::str_true($units) ? shopp_setting('weight_unit') : '';

		$_ = array();
		if ( array_key_exists('id', $options) ) 		$_[] = $addon->id;
		if ( array_key_exists('label', $options) ) 		$_[] = $addon->label;
		if ( array_key_exists('type', $options) ) 		$_[] = $addon->type;
		if ( array_key_exists('sku', $options) ) 		$_[] = $addon->sku;

		if ( array_key_exists('price', $options) ) {
			$price = Shopp::roundprice(self::_taxed((float)$addon->price, $O, $addon->tax, $taxes));
			if ( Shopp::str_true($money) ) $_[] = Shopp::money($price);
			else $_[] = $price;
		}

		if ( array_key_exists('saleprice', $options) ) {
			$saleprice = Shopp::str_true($discounts) ? $addon->promoprice : $addon->saleprice;
			$saleprice = Shopp::roundprice( self::_taxed((float)$addon->promoprice, $O, $addon->tax, $taxes) );
			if ( Shopp::str_true($money) ) $_[] = Shopp::money($saleprice);
			else $_[] = $saleprice;
		}

		if ( array_key_exists('stock', $options) ) 		$_[] = $addon->stock;
		if ( array_key_exists('weight', $options) )
			$_[] = round($addon->weight, 3) . (false !== $weightunit ? " $weightunit" : false);

		if ( array_key_exists('shipfee', $options) ) {
			$shipfee = Shopp::roundprice($addon->shipfee);
			if ( Shopp::str_true($money) ) $_[] = Shopp::money($shipfee);
			else $_[] = $shipfee;
		}

		if ( array_key_exists('sale', $options) )
			return Shopp::str_true($addon->sale);
		if ( array_key_exists('shipping', $options) )
			return Shopp::str_true($addon->shipping);
		if ( array_key_exists('tax', $options) )
			return Shopp::str_true($addon->tax);
		if ( array_key_exists('inventory', $options) )
			return Shopp::str_true($addon->inventory);

		if ( in_array($input, $types) )
			$_[] = '<input type="' . $input . '" name="products[' . (int)$O->id . '][addons][]" value="' . esc_attr($addon->id) . '"' . inputattrs($options) . ' />';

		return join($separator, $_);
	}

	public static function addons ( $result, $options, $O ) {

		// Default mode is: loop
		if ( ! isset($options['mode']) ) {
			if ( ! isset($O->_addon_loop) ) {
				reset($O->prices);
				$O->_addon_loop = true;
			} else next($O->prices);

			$addon = current($O->prices);

			while ( false !== $addon && ('N/A' == $addon->type || 'addon' != $addon->context) )
				$addon = next($O->prices);

			if ( false !== current($O->prices) ) return true;
			else {
				$O->_addon_loop = false;
				return false;
			}
		}

		if ( shopp_setting_enabled('inventory') && $O->outofstock ) return false; // Completely out of stock, hide menus
		if ( ! isset($O->options['a']) ) return false; // There are no addons, don't render menus

		$defaults = array(
			'defaults' => '',
			'disabled' => 'show',
			'before_menu' => '',
			'after_menu' => '',
			'mode' => 'menu',
			'label' => true,
			'format' => '%l (+%p)',
			'required' => false,
			'required_error' => __('You must select addon options for this item before you can add it to your shopping cart.','Shopp'),
			'taxes' => null,
			'class' => '',
			);

		$options = array_merge($defaults, $options);
		extract($options);

		$addons = $O->options['a'];
		$idprefix = 'product-addons-';
		if ($required) $class = trim("$class validate");

		if ( isset($taxes) ) $taxes = Shopp::str_true($taxes);

		$markup = array();
		if ( 'single' == $mode ) {
			if ( ! empty($before_menu) ) $markup[] = $before_menu;
			$menuid = $idprefix . $O->id;

			if ( Shopp::str_true($label) ) $markup[] = '<label for="' . esc_attr($menuid) . '">'. Shopp::esc_html__('Options') . ': </label> ';

			$markup[] = '<select name="products[' . (int)$O->id . '][price]" id="' . esc_attr($menuid) . '">';
			if ( ! empty($defaults) ) $markup[] = '<option value="">' . esc_html($defaults) . '</option>';

			foreach ( $O->prices as $pricing ) {
				if ( 'addon' != $pricing->context ) continue;

				$currently = Shopp::str_true($pricing->sale) ? $pricing->promoprice : $pricing->price;
				$disabled = Shopp::str_true($pricing->inventory) && $pricing->stock == 0 ? ' disabled="disabled"' : '';

				$currently = self::_taxed((float)$currently, $O, $pricing->tax, $taxes);

				$discount = 100 - round($pricing->promoprice * 100 / $pricing->price);
				$_ = new StdClass();
				$_->p = 'Donation' != $pricing->type ? money($currently) : false;
				$_->l = $pricing->label;
				$_->i = Shopp::str_true($pricing->inventory);
				$_->s = $_->i ? (int)$pricing->stock : false;
				$_->u = $pricing->sku;
				$_->tax = Shopp::str_true($pricing->tax);
				$_->t = $pricing->type;
				$_->r = $pricing->promoprice != $pricing->price ? money($pricing->price) : false;
				$_->d = $discount > 0 ? $discount : false;

				if ( 'N/A' != $pricing->type )
					$markup[] = '<option value="' . (int)$pricing->id . '"' . $disabled . '>' . esc_html(self::_variant_formatlabel($format, $_)) . '</option>' . "\n";

			}

			$markup[] = '</select>';

			if ( ! empty($after_menu) ) $markup[] = $after_menu;

		} else {
			if ( ! isset($O->options['a']) ) return; // Bail if there are no addons

			// Index addon prices by option
			$index = array();
			foreach ($O->prices as $pricetag) {
				if ($pricetag->context != "addon") continue;
				$index[$pricetag->optionkey] = $pricetag;
			}

			foreach ( $addons as $id => $menu ) {
				if ( ! empty($before_menu) ) $markup[] = $before_menu;
				$menuid = $idprefix . $menu['id'];
				if ( Shopp::str_true($label) ) $markup[] = '<label for="' . esc_attr($menuid) . '">' . esc_html($menu['name']) . '</label> ';
				$category_class = shopp('collection', 'get-slug');
				$classes = array($class, $category_class, 'addons');

				$markup[] = '<select name="products[' . $O->id . '][addons][]" class="' . trim(join(' ', $classes)). '" id="' . esc_attr($menuid) . '" title="' . esc_attr($menu['name']) . '">';
				if ( ! empty($defaults) ) $markup[] = '<option value="">' . $defaults . '</option>';

				foreach ( $menu['options'] as $key => $option ) {
					$pricing = $index[ $O->optionkey(array($option['id'])) ];

					$currently = Shopp::str_true($pricing->sale) ? $pricing->promoprice : $pricing->price;
					$disabled = Shopp::str_true($pricing->inventory) && $pricing->stock == 0 ? ' disabled="disabled"' : '';

					$currently = self::_taxed((float)$currently, $O, $pricing->tax, $taxes);

					$discount = 100 - round($pricing->promoprice * 100 / $pricing->price);
					$_ = new StdClass();
					$_->p = 'Donation' != $pricing->type ? money($currently) : false;
					$_->l = $pricing->label;
					$_->i = Shopp::str_true($pricing->inventory);
					$_->s = $_->i ? (int)$pricing->stock : false;
					$_->u = $pricing->sku;
					$_->tax = Shopp::str_true($pricing->tax);
					$_->t = $pricing->type;
					$_->r = $pricing->promoprice != $pricing->price ? money($pricing->price) : false;
					$_->d = $discount > 0 ? $discount : false;

					if ( 'N/A' != $pricing->type )
						$markup[] = '<option value="' . (int)$pricing->id . '"' . $disabled . '>' . esc_html(self::_variant_formatlabel($format, $_)) . '</option>' . "\n";

				}

				$markup[] = '</select>';

				if ( ! empty($after_menu) ) $markup[] = $after_menu;
			}

		}


		if ( $required )
			add_storefrontjs("$('#".$menuid."').parents('form').bind('shopp_validate',function () { if ('' == $('#".$menuid."').val()) this.shopp_validation = ['".$required_error."', $('#".$menuid."').get(0) ]; }); ");

		return join('', $markup);
	}

	public static function add_to_cart ( $result, $options, $O ) {
		if (!shopp_setting_enabled('shopping_cart')) return '';
		$defaults = array(
			'ajax' => false,
			'class' => 'addtocart',
			'label' => __('Add to Cart','Shopp'),
			'redirect' => false,
		);
		$options = array_merge($defaults,$options);
		extract($options);

		$classes = array();
		if (!empty($class)) $classes = explode(' ',$class);

		$string = "";
		if ( shopp_setting_enabled('inventory') && $O->outofstock )
			return apply_filters('shopp_product_outofstock_text', '<span class="outofstock">'.esc_html(shopp_setting('outofstock_text')).'</span>');

		if ($redirect)
			$string .= '<input type="hidden" name="redirect" value="'.esc_attr($redirect).'" />';

		$string .= '<input type="hidden" name="products['.$O->id.'][product]" value="'.$O->id.'" />';

		if ( ! Shopp::str_true($O->variants) && ! empty($O->prices) ) { // If variants are off, locate the default price line
			foreach ($O->prices as $price) {
				if ('product' == $price->context) {
					$default = $price; break;
				}
			}
			if ('N/A' == $default->type) return false; // Disable add to cart if the default price is disabled
			$string .= '<input type="hidden" name="products['.$O->id.'][price]" value="'.$default->id.'" />';
		}

		$collection = isset(ShoppCollection()->slug)?shopp('collection','get-slug'):false;
		if (!empty($collection)) {
			$string .= '<input type="hidden" name="products['.$O->id.'][category]" value="'.esc_attr($collection).'" />';
		}

		$string .= '<input type="hidden" name="cart" value="add" />';
		if (!$ajax) {
			$options['class'] = join(' ',$classes);
			$string .= '<input type="submit" name="addtocart" '.inputattrs($options).' />';
		} else {
			if ('html' == $ajax) $classes[] = 'ajax-html';
			else $classes[] = 'ajax';
			$options['class'] = join(' ',$classes);
			$string .= '<input type="hidden" name="ajax" value="true" />';
			$string .= '<input type="button" name="addtocart" '.inputattrs($options).' />';
		}

		return $string;
	}

	public static function availability ( $result, $options, $O ) {
		return ! ( shopp_setting_enabled('inventory') && $O->outofstock );
	}

	public static function buy_now ( $result, $options, $O ) {
		if ( ! isset($options['value']) ) $options['value'] = Shopp::__('Buy Now');
		return self::addtocart( $result, $options, $O );
	}

	public static function categories ( $result, $options, $O ) {
		if ( ! isset($O->_categories_loop) ) {
			reset($O->categories);
			$O->_categories_loop = true;
		} else next($O->categories);

		if ( false !== current($O->categories) ) return true;
		else {
			unset($O->_categories_loop);
			return false;
		}
	}

	public static function category ( $result, $options, $O ) {

		$category = current($O->categories);
		$show = isset($options['show']) ? strtolower($options['show']) : false;
		switch ( $show ) {
			case 'id':		return (int)$category->id;
			case 'slug':	return $category->slug;
			default: 		return $category->name;
		}

	}

	public static function coverimage ( $result, $options, $O ) {
		// Force select the first loaded image
		unset($options['id']);
		$options['index'] = 0;
		$options['load'] = 'coverimages';
		return self::image( $result, $options, $O );
	}

	public static function description ( $result, $options, $O ) {
		return $O->description;
	}

	public static function found ( $result, $options, $O ) {
		if (empty($O->id)) return false;
		// Prevent re-loading individual product data in category loops
		if ( ShoppCollection() && shopp('collection.products', 'looping=true') ) return true;
		$loadable = array('prices', 'coverimages', 'images', 'specs', 'tags', 'categories', 'summary');
		$defaults = array(
			'load' => false
		);
		$options = array_merge($defaults, $options);
		extract($options);

		if ( false !== strpos($load, ',') ) $load = explode(',', $load);
		$load = array_intersect($loadable,(array)$load);
		if ( empty($load) ) $load = array('summary', 'prices', 'images', 'specs', 'tags', 'categories');
		$O->load_data($load);
		return true;
	}

	public static function free_shipping ( $result, $options, $O ) {
		if (empty($O->prices)) $O->load_data(array('prices'));
		return Shopp::str_true($O->freeship);
	}

	public static function gallery ( $result, $options, $O ) {
		if ( empty($O->images) ) $O->load_data(array('images'));
		if ( empty($O->images) ) return false;

		$_size = 240;
		$_width = shopp_setting('gallery_small_width');
		$_height = shopp_setting('gallery_small_height');

		if (!$_width) $_width = $_size;
		if (!$_height) $_height = $_size;

		$defaults = array(

			// Layout settings
			'margins' => 20,
			'rowthumbs' => false,
			// 'thumbpos' => 'after',

			// Preview image settings
			'p_setting' => false,
			'p_size' => false,
			'p_width' => false,
			'p_height' => false,
			'p_fit' => false,
			'p_sharpen' => false,
			'p_quality' => false,
			'p_bg' => false,
			'p_link' => true,
			'rel' => '',

			// Thumbnail image settings
			'thumbsetting' => false,
			'thumbsize' => false,
			'thumbwidth' => false,
			'thumbheight' => false,
			'thumbfit' => false,
			'thumbsharpen' => false,
			'thumbquality' => false,
			'thumbbg' => false,

			// Effects settings
			'zoomfx' => 'shopp-zoom',
			'preview' => 'click',
			'colorbox' => '{}'

		);

		// Populate defaults from named settings, if provided
		$ImageSettings = ImageSettings::object();

		if ( ! empty($options['p_setting']) ) {
			$settings = $ImageSettings->get($options['p_setting']);
			if ( $settings ) $defaults = array_merge($defaults, $settings->options('p_'));
		}

		if ( ! empty($options['thumbsetting']) ) {
			$settings = $ImageSettings->get($options['thumbsetting']);
			if ( $settings ) $defaults = array_merge($defaults, $settings->options('thumb'));
		}

		$optionset = array_merge($defaults, $options);

		// Translate dot-notation options to underscore
		$options = array();
		$keys = array_keys($optionset);
		foreach ( $keys as $key )
			$options[ str_replace('.', '_',$key) ] = $optionset[ $key ];
		extract($options);

		if ( $p_size > 0 )
			$_width = $_height = $p_size;

		$width = $p_width > 0 ? $p_width : $_width;
		$height = $p_height > 0 ? $p_height : $_height;

		$preview_width = $width;

		// Find the max dimensions to use for the preview spacing image
		$maxwidth = $maxheight = 0;

		foreach ( $O->images as $img ) {
			$scale = $p_fit ? array_search($p_fit, ImageAsset::$defaults['scaling']) : false;
			$scaled = $img->scaled($width, $height, $scale);
			$maxwidth = max($maxwidth, $scaled['width']);
			$maxheight = max($maxheight, $scaled['height']);
		}

		if ( 0 == $maxwidth ) $maxwidth = $width;
		if ( 0 == $maxheight ) $maxheight = $height;

		$p_link = Shopp::str_true($p_link);

		$product_class = 'product_' . (int) $O->id;

		// Setup preview images
		$previews = '';

		if ( 'transparent' == strtolower($p_bg) ) $fill = -1;
		else $fill = $p_bg ? hexdec(ltrim($p_bg, '#')) : false;

		$lowest_quality = min(ImageSetting::$qualities);

		$scale = $p_fit ? array_search($p_fit, ImageAsset::$defaults['scaling']) : false;
		$sharpen = $p_sharpen ? max($p_sharpen, ImageAsset::$defaults['sharpen']) : false;
		$quality = $p_quality ? max($p_quality, $lowest_quality) : false;

		foreach ( $O->images as $Image ) {
			$firstPreview = false;
			if ( empty($previews) ) { // Adds "filler" image to reserve the dimensions in the DOM
				$firstPreview = $previews .=
					'<li class="fill">' .
					'<img src="' .  Shopp::clearpng() . '" alt="" style="width: ' . (int) $maxwidth . 'px; height: ' . (int) $maxheight . 'px;" />' .
					'</li>';
			}

			$scaled = $Image->scaled($width, $height, $scale);

			$titleattr = ! empty($Image->title) ? ' title="' . esc_attr($Image->title) . '"' : '';
			$alt = esc_attr( ! empty($Image->alt) ? $Image->alt : $Image->filename );
			$src = $Image->url($width, $height, $scale, $sharpen, $quality, $fill);

			$img = '<img src="' . $src . '"' . $titleattr . ' alt="' . $alt . '" width="' . (int) $scaled['width'] . '" height="' . (int) $scaled['height'] . '" />';

			if ( $p_link ) {
				$hrefattr = $Image->url();
				$relattr = empty($rel) ? '' : ' rel="' . esc_attr($rel) . '"';
				$linkclasses = array('gallery', $product_class, $zoomfx);

				$img = '<a href="' . $hrefattr . '" class="' . join(' ', $linkclasses) . '"' . $relattr . $titleattr . '>' . $img . '</a>';
			}

			$previews .= '<li id="preview-' . $Image->id . '"' . ( empty($firstPreview) ? '' : '  class="active"' ) . '>' . $img. '</li>';
		}
		$previews = '<ul class="previews">' . $previews . '</ul>';

		$thumbs = '';
		$twidth = $preview_width + $margins;

		// Add thumbnails (if needed)
		if ( count($O->images) > 1 ) {

			$default_size = 64;
			$_thumbwidth = shopp_setting('gallery_thumbnail_width');
			$_thumbheight = shopp_setting('gallery_thumbnail_height');
			if ( ! $_thumbwidth ) $_thumbwidth = $default_size;
			if ( ! $_thumbheight ) $_thumbheight = $default_size;

			if ( $thumbsize > 0 ) $thumbwidth = $thumbheight = $thumbsize;

			$width = $thumbwidth > 0 ? $thumbwidth : $_thumbwidth;
			$height = $thumbheight > 0 ? $thumbheight : $_thumbheight;

			$thumbs = '';
			foreach ( $O->images as $Image ) {

				$scale = $thumbfit ? array_search($thumbfit, ImageAsset::$defaults['scaling']) : false;
				$sharpen = $thumbsharpen ? min($thumbsharpen, ImageAsset::$defaults['sharpen']) : false;
				$quality = $thumbquality ? min($thumbquality, ImageAsset::$defaults['quality']) : false;

				if ( 'transparent' == strtolower($thumbbg) ) $fill = -1;
				else $fill = $thumbbg ? hexdec(ltrim($thumbbg, '#')) : false;

				$scaled = $Image->scaled($width, $height, $scale);

				$titleattr = empty($Image->title) ? '' : ' title="' . esc_attr($Image->title) . '"';
				$alt = esc_attr( empty($Image->alt) ? $Image->name : $Image->alt );
				$src = $Image->url($width, $height, $scale, $sharpen, $quality, $fill);

				$thumbclasses = array('preview-' . $Image->id);
				if ( empty($thumbs) ) $thumbclasses[] = 'first';
				$thumbclasses = esc_attr(join(' ', $thumbclasses));

				$thumbs .= '<li id="thumbnail-' . $Image->id . '" class="' . $thumbclasses . '">' .
							'<img src="' . $src . '"' . $titleattr . ' alt="' . $alt . '" width="' . (int) $scaled['width'] . '" height="' . (int) $scaled['height'] . '" />' .
							'</li> ';

			}
			$thumbs = '<ul class="thumbnails">' . $thumbs . '</ul>';
		} // end count($O->images)

		if ( $rowthumbs > 0 ) $twidth = ($width + $margins + 2) * (int) $rowthumbs;

		$result = '<div id="gallery-' . $O->id . '" class="gallery">' . $previews . $thumbs . '</div>';
		$script = "\t" . 'ShoppGallery("#gallery-' . $O->id . '","' . $preview . '"' . ( $twidth ? ",$twidth" : "" ) . ');';
		add_storefrontjs($script);

		return $result;
	}

	public static function has_addons ( $result, $options, $O ) {
		reset($O->prices);
		return ( Shopp::str_true($O->addons) && ! empty($O->options['a']) );
	}

	public static function has_categories ( $result, $options, $O ) {

		if ( empty($O->categories) )
			$O->load_data(array('categories'));

		reset($O->categories);

		if ( count($O->categories) > 0 ) return true;
		else return false;

	}

	public static function has_images ( $result, $options, $O ) {

		if ( empty($O->images) )
			$O->load_data(array('images'));

		reset($O->images);
		return ( ! empty($O->images) );

	}

	public static function has_savings ( $result, $options, $O ) {
		return ( Shopp::str_true($O->sale) && $O->min['saved'] > 0 );
	}

	public static function has_specs ( $result, $options, $O ) {

		if ( empty($O->specs) )
			$O->load_data(array('specs'));

		reset($O->specs);

		if ( count($O->specs) > 0 ) return true;
		else return false;

	}

	public static function has_tags ( $result, $options, $O ) {

		if ( empty($O->tags) )
			$O->load_data(array('tags'));

		reset($O->tags);

		if ( count($O->tags) > 0 ) return true;
		else return false;

	}

	public static function has_variations ( $result, $options, $O ) {

		if ( ! Shopp::str_true($O->variants) ) return false;

		// Only load again if needed
		$load = array();
		if ( empty($O->options) ) $load[] = 'meta';
		if ( empty($O->prices) ) $load[] = 'prices';
		if ( ! empty($load) ) $O->load_data($load);

		reset($O->prices);
		return ( ! empty($O->options['v']) || ! empty($O->options) );

	}

	public static function id ( $result, $options, $O ) {
		return $O->id;
	}

	/**
	 * Renders a product image
	 *
	 * @see the image() method from theme/catalog.php
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return string
	 **/
	public static function image ( $result, $options, $O ) {
		$loadset = array('images', 'coverimages');
		if ( empty($options['load']) || ! in_array($options['load'], $loadset) )
			$options['load'] = $loading[0];

		// Load images if no images are loaded or we're loading all images after the coverimage was loaded
		if ( empty($O->images) || 'images' == $options['load'] )
			$O->load_data( array($options['load']) );

		return ShoppStorefrontThemeAPI::image( $result, $options, $O );
	}

	public static function images ( $result, $options, $O ) {
		if (!$O->images) return false;
		if (!isset($O->_images_loop)) {
			reset($O->images);
			$O->_images_loop = true;
		} else next($O->images);

		if (current($O->images) !== false) return true;
		else {
			unset($O->_images_loop);
			return false;
		}
	}

	public static function in_cart ( $result, $options, $O ) {
		$Order = ShoppOrder();
		$cartitems = $Order->Cart->contents;
		if (empty($cartitems)) return false;
		foreach ((array)$cartitems as $Item)
			if ($Item->product == $O->id) return true;
		return false;
	}

	public static function in_category ( $result, $options, $O ) {
		if (empty($O->categories)) $O->load_data(array('categories'));
		if (isset($options['id'])) $field = "id";
		if (isset($options['name'])) $field = "name";
		if (isset($options['slug'])) $field = "slug";
		foreach ($O->categories as $category)
			if ($category->{$field} == $options[$field]) return true;
		return false;
	}

	public static function input ( $result, $options, $O ) {
		$defaults = array(
			'type' => 'text',
			'name' => false,
			'value' => ''
		);
		$options = array_merge($defaults, $options);

		// Ensure we have a title attribute (catalog.js depends on this)
		$options['title'] = ($options['name'] !== false) ? $options['name'] : Shopp::__('product input field');

		extract($options, EXTR_SKIP);

		$select_attrs = array('title', 'required', 'class', 'disabled', 'required', 'size', 'tabindex', 'accesskey');
		$submit_attrs = array('title', 'class', 'value', 'disabled', 'tabindex', 'accesskey');

		if ( empty($type) || ( ! in_array($type,array('menu', 'textarea') ) && ! Shopp::valid_input($options['type'])) )
			$type = $defaults['type'];

		if ( empty($name) ) return '';
		$slug = sanitize_title_with_dashes($name);
		$id = "data-$slug-{$O->id}";

		if ( 'menu' == $type ) {
			$result = '<select name="products[' . (int)$O->id . '][data][' . esc_attr($name) . ']" id="' . esc_attr($id) . '"' . inputattrs($options, $select_attrs) . '>';
			if ( isset($options['options']) )
				$menuoptions = preg_split('/,(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/', $options['options']);
			if ( is_array($menuoptions) ) {
				foreach( $menuoptions as $option ) {
					$selected = "";
					$option = trim($option, '"');
					if ( isset($options['default']) && $options['default'] == $option )
						$selected = ' selected="selected"';
					$result .= '<option value="' . esc_attr($option) . '"' . $selected . '>' . esc_html($option) . '</option>';
				}
			}
			$result .= '</select>';
		} elseif ( 'textarea' == $type ) {

			if ( isset($options['cols']) ) $cols = ' cols="' . (int)$options['cols'] . '"';
			if ( isset($options['rows']) ) $rows = ' rows="' . (int)$options['rows'] . '"';

			$result = '<textarea name="products[' . (int)$O->id . '][data][' . esc_attr($name) . ']" id="'.$id.'"'.$cols.$rows.inputattrs($options).'>'.esc_html($value).'</textarea>';

		} else {

			if ( in_array($type, array('checkbox', 'radio')) && false !== strpos($name, '[]') ) {
				$nametext = substr($name, 0, -2);
				$options['title'] = $nametext;
				$name = '[' . esc_attr($nametext) . '][]';
			} else $name = '[' . esc_attr($name) . ']';

			$result = '<input type="' . esc_attr($type) . '" name="products[' . (int)$O->id . '][data]' . $name . '" id="' . esc_attr($id) . '"' . inputattrs($options) . ' />';

		}

		return $result;
	}

	public static function is_featured ( $result, $options, $O ) {
		return Shopp::str_true($O->featured);
	}

	public static function name ( $result, $options, $O ) {
		return apply_filters('shopp_product_name',$O->name);
	}

	public static function on_sale ( $result, $options, $O ) {
		if (empty($O->prices)) $O->load_data(array('prices','summary'));
		if (empty($O->prices)) return false;
		return Shopp::str_true($O->sale);
	}

	public static function out_of_stock ( $result, $options, $O ) {

		if ( ! shopp_setting_enabled('inventory') ) return false;
		if ( ! $O->outofstock ) return false;

		if ( isset($options['label']) ) { // If label option is set at all, show the label instead
			$classes = array('outofstock');
			if ( isset($options['class']) )
				$classes = array_merge($classes, explode(' ', $options['class']));

			$label = shopp_setting('outofstock_text'); // @deprecated Removing label setting
			if ( empty($label) ) $label = Shopp::__('Out of stock');
			if ( ! Shopp::str_true($options['label']) ) $label = $options['label'];
			return '<span class="' . esc_attr(join(' ', $classes)). '">' . esc_html($label) . '</span>';

		} else return true;

	}

	public static function price ( $result, $options, $O ) {
		$defaults = array(
			'taxes' => null,
			'starting' => '',
			'separator' => ' &mdash; ',
			'property' => 'price',
			'money' => 'on',
			'number' => false,
			'high' => false,
			'low' => false,
			'disabled' => __('Currently unavailable', 'Shopp')
		);
		$options = array_merge($defaults, $options);
		extract($options);

		if ( ! Shopp::str_true($O->sale) ) $property = 'price';

		$levels = array('min', 'max');
		foreach ( $levels as $level )
			$$level = isset($O->{$level}[ $property ]) ? $O->{$level}[ $property ] : false;

		list($min, $max) = self::_taxes($O, $property, $taxes);

		if ( 0 == $min + $max ) { // Pricing disabled?
			// @todo Refactor this so the summary system can reflect disabled products
			if ( empty($O->prices) ) $O->load_data( array('prices') ); // Load all price data to check disabled status
			if ( 1 === count($O->prices) && 'N/A' === $O->prices[0]->type ) return $disabled;
		}

		if ( $min == $max || ! empty($starting) || Shopp::str_true($low) ) $prices = array($min);
		elseif ( Shopp::str_true($high) ) $prices = array($max);
		else $prices = array($min, $max);

		$prices = array_map('roundprice', $prices);
		if ( Shopp::str_true($number) ) return join($separator, $prices);
		if ( Shopp::str_true($money) )  $prices = array_map('money', $prices);
		if ( ! empty($starting) && $min != $max ) $prices = "$starting {$prices[0]}";

		if ( is_array($prices) ) return join($separator, $prices);
		else return $prices;
	}

	public static function saleprice ( $result, $options, $O ) {
		$options['property'] = 'saleprice';
		return self::price( $result, $options, $O );
	}

	public static function quantity ( $result, $options, $O ) {
		if ( ! shopp_setting_enabled('shopping_cart') ) return '';
		if ( shopp_setting_enabled('inventory') && $O->outofstock ) return '';

		$inputs = array('text','menu');
		$defaults = array(
			'value' => 1,
			'input' => 'text', // accepts text,menu
			'labelpos' => 'before',
			'label' => '',
			'options' => '1-15,20,25,30,40,50,75,100',
			'size' => 3
		);
		$options = array_merge($defaults,$options);
		$_options = $options;
		extract($_options);
		$select_attrs = array('title','required','class','disabled','required','size','tabindex','accesskey');

		unset($_options['label']); // Interferes with the text input value when passed to inputattrs()
		if( !empty($label) ) $labeling = '<label for="quantity-'.$O->id.'">'.$label.'</label>';


		if ( ! isset($O->_prices_loop) ) reset($O->prices);
		$variation = current($O->prices);

		if ( ! shopp_setting_enabled('download_quantity') && ! empty($O->prices) ) {
			$downloadonly = true;
			foreach ( $O->prices as $variant ) {
				if ( 'Download' != $variant->type && 'N/A' != $variant->type )
					$downloadonly = false;
			}
			if ( $downloadonly ) return '';
		}


		$_ = array();

		if ("before" == $labelpos) $_[] = $labeling;
		if ("menu" == $input) {
			if (Shopp::str_true($O->inventory) && isset($O->max['stock']) && $O->max['stock'] == 0) return "";

			if (strpos($options,",") !== false) $options = explode(",",$options);
			else $options = array($options);

			$qtys = array();
			foreach ((array)$options as $v) {
				if (strpos($v,"-") !== false) {
					$v = explode("-",$v);
					if ($v[0] >= $v[1]) $qtys[] = $v[0];
					else for ($i = $v[0]; $i < $v[1]+1; $i++) $qtys[] = $i;
				} else $qtys[] = $v;
			}
			$_[] = '<select name="products['.$O->id.'][quantity]" id="quantity-'.$O->id.'"' . inputattrs($options, $select_attrs) . '>';
			foreach ($qtys as $qty) {
				$amount = $qty;
				if ( $variation && 'Donation' == $variation->type && Shopp::str_true($variation->donation['var']) ) {
					if ($variation->donation['min'] == "on" && $amount < $variation->price) continue;
					$amount = money($amount);
					$value = $variation->price;
				} else {
					if (Shopp::str_true($O->inventory) && $amount > $O->max['stock']) continue;
				}
				$selected = ($qty == $value ? ' selected="selected"' : '');
				$_[] = '<option'.$selected.' value="'.$qty.'">'.$amount.'</option>';
			}
			$_[] = '</select>';
		} elseif (Shopp::valid_input($input)) {
			if (  $variation && 'Donation' == $variation->type && Shopp::str_true($variation->donation['var']) ) {
				if ($variation->donation['min']) $_options['value'] = $variation->price;
				$_options['class'] .= " currency";
			}
			$_[] = '<input type="'.$input.'" name="products['.$O->id.'][quantity]" id="quantity-'.$O->id.'"'.inputattrs($_options).' />';
		}

		if ("after" == $labelpos) $_[] = $labeling;
		return join("\n",$_);
	}

	public static function relevance ( $result, $options, $O ) {
		return (string)$O->score;
	}

	public static function savings ( $result, $options, $O ) {

		$defaults = array(
			'taxes' => null,
			'show' => '',
			'separator' => ' &mdash; '
		);
		$options = array_merge($defaults, $options);
		extract($options);

		$min = 0; $max = 0;
		$levels = array('min', 'max');

		foreach ( $levels as $level )
			$$level = isset($O->{$level}['savings']) ? $O->{$level}['savings'] : false;

		if ( in_array(strtolower($show), array('%', 'percent') ) ) {

			$savings = ( round($min) == round($max) ) ? array($min) : array($min, $max);
			sort($savings);

			foreach ( $savings as &$amount )
				$amount = percentage($amount, array('precision' => 0));

			return join($separator, $savings);

		} else {

			$inclusivetax = self::_inclusive_taxes($O);
			$taxes = isset($taxes) ? Shopp::str_true($taxes) : null;

			if ( isset($taxes) && ( $inclusivetax ^ $taxes ) ) {
				list($regmin, $regmax) = self::_taxes($O, 'price', $taxes);
				$min *= $regmin / 100;
				$max *= $regmax / 100;
			} else foreach ( $levels as $level )
					$$level = isset($O->{$level}['saved']) ? $O->{$level}['saved'] : false;

			$saved = ( $min == $max ) ? array($min) : array($min, $max);
			foreach ( $saved as &$amount )
				$amount = money($amount);

			return join($separator, $saved);

		}

	}

	public static function schema ( $result, $options, $O ) {
		$template = locate_shopp_template( array('product-' . $O->slug . '-schema.php', 'product-schema.php') );
		if ( ! $template ) $template = SHOPP_ADMIN_PATH . '/products/schema.php';
		ob_start();
		include($template);
		$result = ob_get_clean();
		return $result;
	}

	public static function slug ( $result, $options, $O ) {
		return $O->slug;
	}

	public static function spec ( $result, $options, $O ) {
		$showname = false;
		$showcontent = false;
		$defaults = array(
			'separator' => ': ',
			'delimiter' => ', ',
			'name' => false,
			'index' => false,
			'content' => false,
		);
		if (isset($options['name'])) $showname = true;
		if (isset($options['content'])) $showcontent = true;
		$options = array_merge($defaults,$options);
		extract($options);

		$string = '';

		if ( !empty($name) ) {
			if ( ! isset($O->specnames[$name]) ) return apply_filters('shopp_product_spec',false);
			$spec = $O->specnames[$name];
			if (is_array($spec)) {
				if ($index) {
					foreach ($spec as $id => $item)
						if (($id+1) == $index) $content = $item->value;
				} else {
					$values = array();
					foreach ($spec as $item) $values[] = $item->value;
					$content = join($delimiter,$values);
				}
			} else $content = $spec->value;

			return apply_filters('shopp_product_spec',$content);
		}

		// Spec loop handling
		$spec = current($O->specnames);

		if (is_array($spec)) {
			$values = array();
			foreach ($spec as $id => $entry) {
				$specname = $entry->name;
				$values[] = $entry->value;
			}
			$specvalue = join($delimiter,$values);
		} else {
			$specname = $spec->name;
			$specvalue = $spec->value;
		}

		if ($showname && $showcontent)
			$string = $spec->name.$separator.apply_filters('shopp_product_spec',$specvalue);
		elseif ($showname) $string = $specname;
		elseif ($showcontent) $string = apply_filters('shopp_product_spec',$specvalue);
		else $string = $specname.$separator.apply_filters('shopp_product_spec',$specvalue);
		return $string;
	}

	public static function sku ( $result, $options, $O ) {
		if ( empty($O->prices) ) $O->load_data(array('prices'));

		if ( 1 == count($O->prices) && $O->prices[0]->sku )
			return $O->prices[0]->sku;

		$skus = array();
		foreach ($O->prices as $price)
			if ( 'N/A' != $price->type && $price->sku ) $skus[$price->sku] = $price->sku;

		if ( ! empty($skus) ) return join(',', $skus);
		return '';
	}

	public static function specs ( $result, $options, $O ) {
		if (!isset($O->_specs_loop)) {
			reset($O->specnames);
			$O->_specs_loop = true;
		} else next($O->specnames);

		if (current($O->specnames) !== false) return true;
		else {
			unset($O->_specs_loop);
			return false;
		}
	}

	public static function stock ( $result, $options, $O ) {
		return (int)$O->stock;
	}

	public static function summary ( $result, $options, $O ) {
		$summary = $O->summary;

		$overflow = isset($options['overflow']) ? esc_html($options['overflow']) : '&hellip;';

		if ( ! empty($options['clip']) && strlen($O->summary) > (int)$options['clip'] )
			$summary = substr($summary, 0, strpos(wordwrap($summary, (int)$options['clip'], "\b"), "\b")) . $overflow;

		return apply_filters('shopp_product_summary', $summary);
	}

	public static function tag ( $result, $options, $O ) {
		$tag = current($O->tags);
		if (isset($options['show'])) {
			if ($options['show'] == "id") return $tag->id;
		}
		return $tag->name;
	}

	public static function tagged ( $result, $options, $O ) {
		if (empty($O->tags)) $O->load_data(array('tags'));
		if (isset($options['id'])) $field = "id";
		if (isset($options['name'])) $field = "name";
		foreach ($O->tags as $tag)
			if ($tag->{$field} == $options[$field]) return true;
		return false;
	}

	public static function tags ( $result, $options, $O ) {
		if (!isset($O->_tags_loop)) {
			reset($O->tags);
			$O->_tags_loop = true;
		} else next($O->tags);

		if (current($O->tags) !== false) return true;
		else {
			unset($O->_tags_loop);
			return false;
		}
	}

	// Note this returns the "effective" tax rate (not including compound taxes)
	public static function taxrate ( $result, $options, $O ) {
		return Shopp::taxrate($O);
	}

	public static function type ( $result, $options, $O ) {
		if (empty($O->prices)) $O->load_data(array('prices'));

		if (1 == count($O->prices))
			return $O->prices[0]->type;

		$types = array();
		foreach ($O->prices as $price)
			if ('N/A' != $price->type) $types[$price->type] = $price->type;

		return join(',',$types);
	}

	public static function url ( $result, $options, $O ) {
		global $wp_rewrite;
		return Shopp::url( $wp_rewrite->using_permalinks() ? $O->slug : array(ShoppProduct::$posttype => $O->slug), false );
	 }

	public static function variation ( $result, $options, $O ) {
		$defaults = array(
			'separator' => ' ',
			'units' => 'on',
			'promos' => 'on',
			'discounts' => 'on',
			'taxes' => null,
			'money' => 'on',
			'number' => 'off'
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		$weightunit = Shopp::str_true($units) ? shopp_setting('weight_unit') : '';
		$variation = current($O->prices);
		$taxes = isset($taxes) ? Shopp::str_true($taxes) : null;

		$_ = array();
		if ( array_key_exists('id', $options) ) 	$_[] = $variation->id;
		if ( array_key_exists('label', $options) )	$_[] = $variation->label;
		if ( array_key_exists('type', $options) )	$_[] = $variation->type;
		if ( array_key_exists('sku', $options) )	$_[] = $variation->sku;
		if ( array_key_exists('stock', $options) ) 	$_[] = $variation->stock;

		if ( array_key_exists('price', $options) ) {
			$price = Shopp::roundprice(self::_taxed((float)$variation->price, $O, $variation->tax, $taxes));
			if ( Shopp::str_true($money) ) $_[] = money($price);
			else $_[] = $price;
		}

		if ( array_key_exists('saleprice', $options) ) {
			$saleprice = Shopp::str_true($discounts) ? $variation->promoprice : $variation->saleprice;
			$saleprice = Shopp::roundprice( self::_taxed((float)$saleprice, $O, $variation->tax, $taxes) );
			if ( Shopp::str_true($money) ) $_[] = money($saleprice);
			else $_[] = $saleprice;
		}

		if ( array_key_exists('weight', $options) )
			$_[] = round($variation->weight, 3) . ($weightunit ? " $weightunit" : false);

		if ( array_key_exists('shipfee', $options) ) {
			$shipfee = Shopp::roundprice($variation->shipfee);
			if ( Shopp::str_true($money) ) $_[] = money($shipfee);
			else $_[] = $shipfee;
		}

		if ( array_key_exists('sale', $options) )
			return Shopp::str_true($variation->sale);

		if ( array_key_exists('shipping', $options) )
			return Shopp::str_true($variation->shipping);

		if ( array_key_exists('tax', $options) )
			return Shopp::str_true($variation->tax);

		if ( array_key_exists('inventory', $options) )
			return Shopp::str_true($variation->inventory);

		return join($separator,$_);
	}

	public static function variations ( $result, $options, $O ) {
		$string = "";

		if (!isset($options['mode'])) {
			if (!isset($O->_prices_loop)) {
				reset($O->prices);
				$O->_prices_loop = true;
			} else next($O->prices);
			$price = current($O->prices);

			if ($price && ($price->type == 'N/A' || $price->context != 'variation'))
				$price = next($O->prices);

			if ($price !== false) return true;
			else {
				unset($O->_prices_loop);
				return false;
			}
		}

		if ( shopp_setting_enabled('inventory') && $O->outofstock ) return false; // Completely out of stock, hide menus
		if (!isset($options['taxes'])) $options['taxes'] = null;

		$defaults = array(
			'defaults' => '',
			'disabled' => 'show',
			'pricetags' => 'show',
			'before_menu' => '',
			'after_menu' => '',
			'format' => '%l (%p)',
			'label' => 'on',
			'mode' => 'multiple',
			'taxes' => null,
			'required' => __('You must select the options for this item before you can add it to your shopping cart.','Shopp')
			);
		$options = array_merge($defaults,$options);
		extract($options);

		$taxes = isset($taxes) ? Shopp::str_true($taxes) : null;
		$collection_class = ShoppCollection() && isset(ShoppCollection()->slug) ? 'category-' . ShoppCollection()->slug : '';

		if ( 'single' == $mode ) {
			if ( ! empty($before_menu) ) $string .= $before_menu . "\n";
			if ( Shopp::str_true($label) ) $string .= '<label for="product-options' . (int)$O->id . '">' . Shopp::esc_html__('Options') . ': </label> ' . "\n";

			$string .= '<select name="products[' . (int)$O->id . '][price]" id="product-options' . (int)$O->id . '" class="' . esc_attr($collection_class) . ' product' . (int)$O->id . ' options">';
			if ( ! empty($defaults) ) $string .= '<option value="">' . esc_html($options['defaults']) . '</option>' . "\n";

			foreach ( $O->prices as $pricing ) {
				if ( 'variation' != $pricing->context ) continue;

				$currently = Shopp::str_true($pricing->sale) ? $pricing->promoprice : $pricing->price;
				$disable = ( $pricetag->type == 'N/A' || ( Shopp::str_true($pricing->inventory) && $pricing->stock == 0 ) );

				$currently = self::_taxed((float)$currently, $O, $pricing->tax, $taxes);

				$discount = 0 == $pricing->price ? 0 : 100 - round($pricing->promoprice * 100 / $pricing->price);
				$_ = new StdClass();
				$_->p = 'Donation' != $pricing->type && ! $disable ? money($currently) : false;
				$_->l = $pricing->label;
				$_->i = Shopp::str_true($pricing->inventory);
				$_->s = $_->i ? (int)$pricing->stock : false;
				$_->u = $pricing->sku;
				$_->tax = Shopp::str_true($pricing->tax);
				$_->t = $pricing->type;
				$_->r = $pricing->promoprice != $pricing->price ? money($pricing->price) : false;
				$_->d = $discount > 0 ? $discount : false;

				if ( $disable && 'show' == $disabled )
					$string .= '<option value="' . $pricing->id . '"' . ( $disable ? ' disabled="disabled"' : '' ) . '>' . esc_html(self::_variant_formatlabel($format, $_)) . '</option>' . "\n";
			}
			$string .= '</select>';
			if ( ! empty($options['after_menu']) ) $string .= $options['after_menu']."\n";

		} else {
			if ( ! isset($O->options) ) return;

			$menuoptions = $O->options;
			if ( ! empty($O->options['v']) ) $menuoptions = $O->options['v'];

			$baseop = shopp_setting('base_operations');
			$precision = $baseop['currency']['format']['precision'];

			$pricekeys = array();
			foreach ($O->pricekey as $key => $pricing) {
				$discount = 100-round($pricing->promoprice * 100 / $pricing->price);
				$_ = new StdClass();
				$_->p = 'Donation' != $pricing->type ? (float)apply_filters('shopp_product_variant_price', (Shopp::str_true($pricing->sale) ? $pricing->promoprice : $pricing->price) ) : false;
				$_->i = Shopp::str_true($pricing->inventory);
				$_->s = $_->i ? (int)$pricing->stock : false;
				$_->u = $pricing->sku;
				$_->tax = Shopp::str_true($pricing->tax);
				$_->t = $pricing->type;
				$_->r = $pricing->promoprice != $pricing->price ? money($pricing->price) : false;
				$_->d = $discount > 0 ? $discount : false;
				$pricekeys[ $key ] = $_;
			}

			// Output a JSON object for JS manipulation
			if ( 'json' == $options['mode'] ) return json_encode($pricekeys);

			$jsoptions = array('prices'=> $pricekeys,'format' => $format);
			if ( 'hide' == $options['disabled'] ) $jsoptions['disabled'] = false;
			if ( 'hide' == $options['pricetags'] ) $jsoptions['pricetags'] = false;
			if ( ! empty($taxrate) ) $jsoptions['taxrate'] = Shopp::taxrate($O);
			$select_collection = ( ! empty($collection_class) ) ? '.' . $collection_class : '';

			ob_start();
?><?php if (!empty($options['defaults'])): ?>
$s.opdef = true;
<?php endif; ?>
<?php if (!empty($options['required'])): ?>
$s.opreq = "<?php echo $options['required']; ?>";
<?php endif; ?>
new ProductOptionsMenus(<?php printf("'select%s.product%d.options'",$select_collection,$O->id); ?>,<?php echo json_encode($jsoptions); ?>);
<?php

			$script = ob_get_contents();
			ob_end_clean();

			add_storefrontjs($script);

			foreach ( $menuoptions as $id => $menu ) {
				if ( ! empty($before_menu) ) $string .= $before_menu . "\n";
				if ( Shopp::str_true($label) ) $string .= '<label for="options-' . esc_attr($menu['id']) . '">' . esc_html($menu['name']) . '</label> '."\n";
				$string .= '<select name="products[' . (int)$O->id . '][options][]" class="' . esc_attr($collection_class) . ' product' . (int)$O->id . ' options" id="options-' . esc_attr($menu['id']) . '">';
				if ( ! empty($defaults) ) $string .= '<option value="">' . esc_html($options['defaults']) . '</option>' . "\n";
				foreach ( $menu['options'] as $key => $option )
					$string .= '<option value="' . esc_attr($option['id']) . '">' . esc_html($option['name']) . '</option>'."\n";

				$string .= '</select>';
				if ( ! empty($after_menu) ) $string .= $after_menu . "\n";
			}
		}

		return $string;
	}

	public static function weight ( $result, $options, $O ) {
		if(empty($O->prices)) $O->load_data(array('prices'));
		$defaults = array(
			'unit' => shopp_setting('weight_unit'),
			'min' => $O->min['weight'],
			'max' => $O->max['weight'],
			'units' => true,
			'convert' => false
		);
		$options = array_merge($defaults, $options);
		extract($options);

		if(!isset($O->min['weight'])) return false;

		if ($convert !== false) {
			$min = convert_unit($min,$convert);
			$max = convert_unit($max,$convert);
			if (is_null($units)) $units = true;
			$unit = $convert;
		}

		$range = false;
		if ($min != $max) {
			$range = array($min,$max);
			sort($range);
		}

		$string = ($min == $max)?round($min,3):round($range[0],3)." - ".round($range[1],3);
		$string .= Shopp::str_true($units) ? " $unit" : "";
		return $string;
	}

	public static function _variant_formatlabel ( string $format, $var ) {
		$data = (array)$var;

		$label = $format;
		foreach ( $data as $token => $value )
			$label = str_replace("%$token", (string)$value, $label);

		return trim($label);
	}

	/**
	 * Helper function to determine if inclusive taxes apply to a given product
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppProduct $O The Shopp product to compare
	 * @return boolean True if inclusive taxes apply, false otherwise
	 **/
	private static function _inclusive_taxes ( $O ) {
		return ( shopp_setting_enabled('tax_inclusive') && ! Shopp::str_true($O->excludetax) );
	}

	/**
	 * Helper that applies or excludes taxes as needed from minumum and maximum price levels
	 * based on inclusive tax settings and the tax option given
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppProduct $O The product to get properties from
	 * @param string $property The price property to use ('price' or 'saleprice')
	 * @param boolean $taxoption The Theme API tax option given the the tag
	 * @return array The minimum and maximum prices with or without taxes
	 **/
	private static function _taxes ( ShoppProduct $O, $property, $taxoption = null ) {
		$min = 0; $max = 0;
		$levels = array('min', 'max');
		foreach ( $levels as $level )
			$$level = isset($O->{$level}[ $property ]) ? $O->{$level}[ $property ] : false;

		$taxrates = Shopp::taxrates($O);

		foreach ( $levels as $level )
			$$level = self::_taxed($$level, $O, isset($O->{$level}[ $property . '_tax' ]) ? $O->{$level}[ $property . '_tax' ] : true, $taxoption, $taxrates);


		return array($min, $max);
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
	private static function _taxed ( $amount, ShoppProduct $O, $istaxed, $taxoption = null, array $taxrates = array() ) {
		if ( ! $istaxed ) return $amount;

		if ( empty($taxrates) ) $taxrates = Shopp::taxrates($O);

		$inclusivetax = self::_inclusive_taxes($O);
		if ( isset($taxoption) ) $taxoption = Shopp::str_true( $taxoption );

		if ( $inclusivetax ) {
			$adjustment = ShoppTax::adjustment($taxrates);
			if ( 1 != $adjustment )
				return (float) ($amount / $adjustment);
		}

		// Handle inclusive/exclusive tax presentation options (product editor setting or api option)
		// If the 'taxes' option is specified and the item either has inclusive taxes that apply,
		// or the 'taxes' option is forced on (but not both) then handle taxes by either adding or excluding taxes
		// This is an exclusive or known as XOR, the lesser known brother of Thor that gets left out of the family get togethers
		if ( isset($taxoption) && ( $inclusivetax ^ $taxoption ) ) {

			if ( $taxoption )
				return ShoppTax::calculate($taxrates, (float)$amount);
			else return ShoppTax::exclusive($taxrates, (float)$amount);

		}

		return $amount;
	}

}
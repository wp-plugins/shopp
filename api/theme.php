<?php
/**
 * Shopp Theme API
 *
 * @copyright Ingenesis Limited, February 2011-2014
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp\API\Theme API
 * @version   1.0
 * @since     1.2
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Defines the shopp() 'tag' handler for complete template customization
 *
 * Appropriately routes tag calls to the tag handler for the requested object.
 *
 * @api
 * @since 1.0
 *
 * @param mixed        $context  The object label or Object to get the tag property from
 * @param string       $property The property of the object to get/output
 * @param string|array $options  Custom options for the property result in query form
 *                               (option1=value&option2=value&...) or alternatively as an associative array
 * @return void|bool|string Boolean values or string result or nothing if the string is output
 */
function shopp ( $context, $property = false, $options = false ) {

	$Object = false;
	$result = false;

	$parameters = array('first', 'second', 'third'); // Parameter prototype
	$num = func_num_args();							 // Determine number of arguments provided
	$context = $tag = false;						 // object API to use and tag name
	$options = array();								 // options to pass to API call

	if ( $num < 1 ) { // Not enough arguments to do anything, bail
		shopp_add_error( Shopp::__('shopp() theme tag syntax error: no object property specified.'), SHOPP_PHP_ERR );
		return;
	}

	// Grab the arguments (up to 3)
	$fargs = func_get_args();
	$args = array_combine( array_slice($parameters, 0, $num), $fargs);
	extract($args);

	if ( is_object($first) ) { // Handle Object instances as first argument
		$Object = $first;
		$context = isset($Object->api) ? $Object->api : strtolower(get_class($Object));
		$tag = strtolower($second);
	} elseif ( false !== strpos($first, '.') ) { // Handle object.tag first argument
		list($context, $tag) = explode('.', strtolower($first));
		if ( $num > 1 ) $options = shopp_parse_options($second);
	} elseif ( '' == $context . $tag ) { // Normal tag handler
		list($context, $tag) = array_map('strtolower', array($first, $second));
	}

	if ( $num > 2 ) $options = shopp_parse_options($third);

	// strip hypens from tag names
	$tag = str_replace ( '-', '', $tag );

	// strip get prefix from requested tag
	$get = false;
	if ( 'get' == substr($tag, 0, 3) ) {
		$tag = substr($tag, 3);
		$get = true;
	}

	$Object = apply_filters('shopp_themeapi_object', $Object, $context, $tag);
	$Object = apply_filters('shopp_tag_domain', $Object, $context); // @deprecated

	if ( 'hascontext' == $tag ) return ($Object);

	if ( ! $Object ) shopp_add_error(Shopp::__("The shopp('%s') tag cannot be used in this context because the object responsible for handling it doesn't exist.", $context), SHOPP_PHP_ERR);

	$themeapi = apply_filters('shopp_themeapi_context_name', $context);
	$result   = apply_filters('shopp_themeapi_' . strtolower($themeapi . '_' . $tag), $result, $options, $Object); // tag specific tag filter
	$result   = apply_filters('shopp_tag_' . strtolower($context . '_' . $tag), $result, $options, $Object); // @deprecated use shopp_themeapi_{api}_{tag}

	$result   = apply_filters('shopp_themeapi_' . strtolower($themeapi), $result, $options, $tag, $Object); // global object tag filter
	$result   = apply_filters('shopp_themeapi', $result, $options, $tag, $Object);
	$result   = apply_filters('shopp_ml_t', $result, $options, $tag, $Object); // @deprecated use shopp_themeapi

	// Force boolean result
	if ( isset($options['is']) ) {
		if ( Shopp::str_true($options['is']) ) {
			if ( $result ) return true;
		} else {
			if ( false == $result ) return true;
		}
		return false;
	}

	// Always return a boolean if the result is boolean
	if ( is_bool($result) ) return $result;

	if ( $get ||
		( isset($options['return']) && Shopp::str_true($options['return']) ) ||
		( isset($options['echo']) && !Shopp::str_true($options['echo']) )	)
		return $result;

	// Output the result
	if ( is_scalar($result) ) echo $result;
	else return $result;

}
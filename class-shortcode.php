<?php

/**
 * Shortcode Class
 * @package  Post Link Shortcodes
 */

class PostLinkShortcode
{
	protected $tag;
	protected $url;
	protected $data = array();
	protected $attrs = array();
	protected $request;
	protected $archive;
	protected $type;
	protected $obj;

	/* html element name */
	protected $element;
	/* html attribute which should receive the url */
	protected $url_attribute;
	/* shortcode attributes reserved for output control */
	protected $reserved_keys = [ 'post_id', 'slug', 'inner', 'text' ];
	protected $_url;


	function __construct( $atts, $content, $tag )
	{
		if ( ! is_array( $atts ) )
			$atts = array();

		$this->data['orig'] = array(
			'atts'    => $atts,
			'content' => $content,
			'tag'     => $tag,
		);

		$this->setup_props( $tag );

		$this->setup_element();

		$this->setup_data( $atts );

		$this->setup_attributes( $atts );

		$this->setup_shorthand( $atts );
	}

	/**
	 * Extract data bits from shortcode tag name
	 *
	 * @param $tag  Shortcode name
	 */
	protected function setup_props( $tag )
	{
		$this->tag     = $tag;
		$pieces        = explode( '_', $tag );
		$this->request = array_pop( $pieces ); // (url|link|src|img)
		$this->archive = ( count( $pieces ) > 1 && 'archive' == array_pop( $pieces ) ); // bool
		$this->type    = implode( '_', $pieces ); // post type
	}

	/**
	 * Setup element-related properties for this request
	 */
	protected function setup_element()
	{
		switch ( $this->request )
		{
			case 'link' :
				$this->element = 'a';
				$this->url_attribute = 'href';
				break;
			case 'img' :
				$this->element = 'img';
				$this->url_attribute = 'src';
				break;
		}

		if ( $this->url_attribute )
			array_push($this->reserved_keys, $this->url_attribute);
	}

	/**
	 * Separates attributes and control data from shortcode atts
	 *
	 * @param $atts
	 */
	protected function setup_data( array $atts )
	{
		foreach ( $this->reserved_keys as $key )
		{
			if ( ! array_key_exists($key, $this->data) )
				$this->data[ $key ] = null;
		}

		$non_attrs  = array_diff_assoc( $atts, $this->attrs );
		$this->data = array_merge( $this->data, $non_attrs );
	}

	/**
	 * @param $attributes
	 *
	 * @return array
	 */
	protected function setup_attributes( array $attributes )
	{
		// remove reserved non-attributes
		foreach ( $this->reserved_keys as $key ) {
			unset( $attributes[ $key ] );
		}

		// compensate for shortcodes not liking attributes with hyphens
		foreach ( $attributes as $name => $value ) {
			$attribute_name                 = str_replace( '_', '-', $name );
			$this->attrs[ $attribute_name ] = $value;
		}
	}

	/**
	 * Translates shorthand post_id/slug into their attribute equivalents
	 *
	 * @param $atts
	 */
	protected function setup_shorthand( $atts )
	{
		if ( ! $this->archive && isset( $atts[ 0 ] ) )
		{
			$value              = $this->do_att_shortcode( $atts[ 0 ] );
			$key                = is_numeric( $value ) ? 'post_id' : 'slug';
			$this->data[ $key ] = $value;
			unset( $this->attrs[ 0 ] );
			unset( $this->data[ 0 ] );
		}
	}

	/**
	 * Determines and sets what should be used for the element's inner content
	 */
	protected function setup_inner()
	{
		// If someone took the time to use an enclosed shortcode, that takes precedence
		if ( '' !== $this->data['orig']['content'] )
			$this->data['inner'] = $this->data['orig']['content'];

		// Allow inner text to be set with a text="" attribute instead
		elseif ( ! empty( $this->data['orig']['atts']['text'] ) )
			$this->data['inner'] = $this->do_att_shortcode( $this->data['orig']['atts']['text'] );

		// Defaults to post title for single, or post type name for archive
		else
			$this->data['inner'] = '';
	}

	/**
	 * Determines and sets the URL
	 */
	protected function setup_url()
	{
		if ( $this->archive ) {
			return $this->url = get_post_type_archive_link( $this->type );
		}

		if ( ! $obj = $this->get_object() ) return;

		if (
			('attachment' == $this->type && in_array($this->request, array('src','img')))
			|| ('attachment' == $this->type && 'link' == $this->request && 'src' == $this->data['href'] )
		) {
			return $this->url = wp_get_attachment_url( $obj->ID );
		}

		return $this->url = ( $obj instanceof WP_Post ) ? get_permalink( $obj ) : false;
	}

	/**
	 * @return object|null
	 */
	public function get_object()
	{
		if ( isset( $this->obj ) ) return $this->obj;

		if ( $this->archive ) {
			return $this->obj = get_post_type_object( $this->type );
		}

		if ( $this->data['post_id'] ) {
			return $this->obj = get_post( $this->data['post_id'] );
		}

		if ( $this->data['slug'] )
		{
			$slug_query = array(
				'name'           => $this->data[ 'slug' ],
				'post_type'      => $this->type,
				'posts_per_page' => 1
			);

			$slug_results = (array) get_posts( $slug_query );

			return $this->obj = reset( $slug_results );
		}

		return null;
	}
	 * Get the target url
	 * @return (string|bool) The permalink URL, or false on failure (if the target doesn't exist).
	 */
	function get_url()
	{
		if ( ! isset( $this->url ) ) {
			$this->setup_url();
		}

		/**
		 * The URL
		 *
		 * @filter 'pls/url'
		 * @param (mixed) the URL if found, or (bool) false on failure
		 * @param (array) current shortcode object variables
		 */
		$url = apply_filters( 'pls/url', $this->url, $this->get_filter_data() );

		return esc_url( $url );
	}

	function get_filter_data()
	{
		return get_object_vars( $this );
	}

	/**
	 * Get Link (anchor) markup
	 * By this point, a URL has successfully been found
	 * @return (string)
	 */
	function get_link()
	{
		$html_attributes = $this->format_html_attributes( $this->get_attrs() );

		/**
		 * Inner link text/html
		 *
		 * do_shortcode is applied by default
		 * @see  __construct()
		 *
		 * @filter 'pls/link_text'
		 * @param (string)	inner 	current inner html of link
		 * @param (array)	array() current shortcode object variables
		 */
		$inner = apply_filters( 'pls/link_text', $this->get_inner(), $this->get_filter_data() );

		/**
		 * The final link markup
		 *
		 * @filter 'pls/link'
		 * @param (string) markup
		 * @param (array) current shortcode object variables
		 */
		return apply_filters( 'pls/link', "<a $html_attributes>$inner</a>", $this->get_filter_data() );
	}

	public function get_img()
	{
		$html_attributes = $this->format_html_attributes( $this->get_attrs() );

		/**
		 * The final image markup
		 *
		 * @filter 'pls/img'
		 * @param (string) markup
		 * @param (array) current shortcode object variables
		 */
		return apply_filters( 'pls/img', "<$this->element $html_attributes>", $this->get_filter_data() );
	}

	/**
	 * Determine inner html for the element
	 */
	function get_inner()
	{
		if ( ! isset( $this->data['inner'] ) )
			$this->setup_inner();

		// static - [sc]$content[/sc] takes precedence over [sc text=""]
		if ( strlen( $this->data['inner'] ) )
			return $this->data['inner'];

		// dynamic
		if ( $this->archive )
		{
			/**
			 * Archive link text
			 * @filter 'pls/archive_text'
			 * @param (string) post type name
			 * @param (object) post type object
			 * @param (array) current shortcode object variables
			 */
			return apply_filters( 'pls/archive_text', $this->obj->labels->name, $this->obj, $this->get_filter_data() );
		}

		/**
		 * Single post link text
		 * @filter 'pls/single_text'
		 * @param (string) post title
		 * @param (object) post object
		 * @param (array) current shortcode object variables
		 */
		return apply_filters( 'pls/single_text', $this->obj->post_title, $this->obj, $this->get_filter_data() );
	}

	/**
	 * Get sanitized, qualified attributes
	 *
	 * @return array
	 */
	function get_attrs()
	{
		// base attributes
		// a shortcode-defined href is overridden by the url
		$attrs = array_merge( $this->attrs, array('href' => $this->url) );

		/**
		 * Optionally restrict html attributes to only those defined here
		 *
		 * This is a whitelist of allowed attribute names. All others will be removed.
		 *
		 * To use: return a 1 dimensional array of allowed html attributes
		 * Ex: return array('href','id','class')
		 *
		 * @filter 'pls/allowed_link_attributes'
		 * @param (array)	array()	empty array to be filled with html attribute names to whitelist
		 * @param (array)	$attrs	current set of html attribute => value
		 * @param (array)	array() current shortcode object variables
		 *
		 * *** MAKE SURE TO INCLUDE 'href' IF YOU USE THIS! ***
		 */
		$allowed = apply_filters( 'pls/allowed_link_attributes', array(), $attrs, $this->get_filter_data() );
		$allowed = apply_filters( "pls/$this->request/attributes/allowed", $allowed, $attrs, $this->get_filter_data() );

		if ( $allowed && is_array( $allowed ) )
		{
			foreach ( array_keys( $attrs ) as $k )
				if ( ! in_array( $k, $allowed ) )
					unset( $attrs[ $k ] );
		}

		/**
		 * Optionally forbid specific attributes
		 *
		 * This is a blacklist of attribute names.
		 *
		 * To use: return a 1 dimensional array of html attributes to exclude
		 * Ex: return array('style','height','width')
		 *
		 * @filter 'pls/exclude_link_attributes'
		 * @param (array)	array()	empty array to be filled with html attribute names to blacklist
		 * @param (array)	$attrs	current set of html attribute => value
		 * @param (array)	array() current shortcode object variables
		 *
		 * May be used together with 'pls/allowed_link_attributes' filter as well
		 */
		$exclude = apply_filters( 'pls/exclude_link_attributes', array(), $attrs, $this->get_filter_data() );
		$exclude = apply_filters( "pls/$this->request/attributes/disallowed", $exclude, $attrs, $this->get_filter_data() );

		if ( $exclude && is_array( $exclude ) )
		{
			foreach ( $exclude as $e )
				unset( $attrs[ $e ] );
		}

		// sanitize attribute values
		return array_map( 'esc_attr', $attrs );
	}

	/**
	 * Allow shortcodes to be used inside shortcode attribute values by using {{}} instead of []
	 *
	 * @param $content
	 *
	 * @return
	 */
	function do_att_shortcode( $content )
	{
		if ( false !== strpos($content, '{{') && false !== strpos($content, '}}') )
		{
			$convert = str_replace( array('{{','}}'), array('[',']'), $content );
			$content = do_shortcode( $convert );
		}
		return $content;
	}

	function __get( $name )
	{
		return property_exists($this, $name)
			? $this->{$name}
			: null;
	}

	/**
	 * @param array $attributes
	 *
	 * @return string
	 */
	protected function format_html_attributes( array $attributes )
	{
		// build html attribute string
		$attr_pairs = array();
		foreach ( $attributes as $name => $value )
		{
			$value = $this->do_att_shortcode( $value );

			if ( is_numeric( $name ) && strlen( $value ) ) {
				$attr_pairs[] = $value;
			} elseif ( ! is_numeric( $name ) && strlen( $value ) ) {
				$attr_pairs[] = sprintf( '%s="%s" ', $name, $value );
			}
		}
		$html_attributes = trim( join( ' ', $attr_pairs ) );

		return $html_attributes;
	}

}

/**
 * Class PLS_SC
 * PostLinkShortcode alias
 * @deprecated 0.4.0
 */
class PLS_SC extends PostLinkShortcode {}
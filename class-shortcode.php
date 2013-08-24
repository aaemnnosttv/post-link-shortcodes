<?php

/**
 * Shortcode Class
 * @package  Post Link Shortcodes
 */

class PLS_SC
{
	private $url;
	private $data;
	private $attrs;

	function __construct( $atts, $content, $tag )
	{
		$this->setup_props( $tag );
		
		$this->setup_data( $atts, $content );
		
		// backup
		$this->data['orig'] = array(
			'atts'    => $atts,
			'content' => $content,
			'tag'     => $tag,
		);
	}

	/**
	 * Extract data bits from shortcode tag name
	 * @param  [type] $tag [description]
	 * @return [type]      [description]
	 */
	function setup_props( $tag )
	{
		$pieces = explode('_', $tag);
		
		$this->tag     = $tag;
		$this->request = array_pop( $pieces ); // (url|link)
		$this->archive = ( count( $pieces ) > 1 && 'archive' == array_pop( $pieces ) ); // bool
		$this->type    = join( $pieces ); // post type
	}

	/**
	 * Separates attributes and data from shortcode atts
	 * @param  [type] $atts [description]
	 * @return [type]       [description]
	 */
	function setup_data( $atts, $content )
	{
		if ( ! is_array( $atts ) )
			$atts = array();

		// prepare a separate array to for attributes
		$this->attrs = $atts;
		// remove reserved non-attributes
		foreach ( array( 0,'post_id','slug','text' ) as $key )
			unset( $this->attrs[ $key ] );
		
		// everything else
		$data = array_diff_assoc( $atts, $this->attrs );

		// allow "shorthand" for post_id / slug
		if ( !$this->archive && isset( $atts[0] ) ) {
			$key = is_numeric( $atts[0] ) ? 'post_id' : 'slug';
			$data[ $key ] = $atts[0];
			unset( $data[0] );
		}
		 
		// if someone took the time to use an encapsulated shortcode, that denotes precedence
		// allow alternate inner text to be set with a text="" att
		// defaults to post title for single, or post type name for archive
		if ( '' !== $content )
			$data['inner'] = $content;
		else
			$data['inner'] = isset( $atts['text'] )
								? $this->do_att_shortcode( $atts['text'] )
								: '';

		$this->data = $data;
	}

	/**
	 * Get the target url
	 * @return (string|bool) The permalink URL, or false on failure (if the page doesn't exist).
	 */
	function get_url()
	{
		if ( is_null( $this->url ) )
		{
			if ( $this->archive )
			{
				$obj = get_post_type_object( $this->type );
				$url = get_post_type_archive_link( $this->type );
			}
			else
			{
				$d = array(
					'post_id' => '',
					'slug'    => '',
				);
				extract( shortcode_atts( $d, $this->data ) );

				if ( !$post_id && $slug ) {
					$found = get_posts( array(
						'name'           => $slug,
						'post_type'      => $this->type,
						'posts_per_page' => 1
					));
				}

				$obj = $found ? $found[0] : get_post( $post_id );
				$url = get_permalink( $obj );
			}
			// store results
			$this->obj = $obj;
			$this->_url = $url;
		}

		/**
		 * @filter 'pls/url'
		 * The URL 
		 */
		return $this->url = apply_filters( 'pls/url', $url, $this->get_filter_data() );
	}

	public function get_filter_data()
	{
		$vars = get_object_vars( $this );
		unset( $vars['data'] );
		$vars = array_merge( $vars, $this->data );

		return $vars; 
	}

	public function get_link()
	{
		// get filtered & sanitized attrs
		$attrs = $this->get_attrs();

		// begin html attribute construction
		$attr = '';
		foreach ( $attrs as $name => $value )
		{
			$value = $this->do_att_shortcode( $value );
			$attr .= sprintf('%s="%s" ', $name, $value);
		}
		$attr = trim( $attr );

		/**
		 * @filter 'pls/link_text'
		 * Inner html of generated link
		 * do_shortcode is applied by default
		 */
		$inner = apply_filters( 'pls/link_text', $this->get_inner(), $this->get_filter_data() );

		/**
		 * @filter 'pls/link'
		 * The LINK markup
		 */
		return apply_filters( 'pls/link', "<a $attr>$inner</a>", $this->get_filter_data() );
	}

	/**
	 * Determine inner html for the link
	 */
	public function get_inner() 
	{
		// static - [sc text=""] takes precedence [sc]$content[/sc]
		if ( !empty( $this->data['inner'] ) )
			return $this->data['inner'];

		// dynamic
		if ( $this->archive )
			return apply_filters( 'pls/post_type_name',		$this->obj->labels->name, $this->get_filter_data() );
		else
			return apply_filters( 'pls/post_title_text',	$this->obj->post_title, $this->get_filter_data() );
	}

	public function get_attrs()
	{
		// base attributes
		// a shortcode-defined href is overriden by permalink
		$attrs = array_merge( $this->attrs, array('href' => $this->url) );

		/**
		 * @filter 'pls/allowed_link_attributes'
		 * Optionally filter out any html attributes to only those defined here
		 * To use: return a 1 dimensional array of allowed html attributes
		 * All others will be removed
		 * *** MAKE SURE TO INCLUDE 'href' IF YOU USE THIS! ***
		 * Ex: array('href','id','class')
		 */
		$allowed = apply_filters( 'pls/allowed_link_attributes', array(), $attrs, $this->get_filter_data() );

		if ( $allowed && is_array( $allowed ) )
		{
			foreach ( array_keys( $attrs ) as $k )
				if ( !in_array( $k, $allowed ) )
					unset( $attrs[ $k ] );
		}

		/**
		 * @filter 'pls/exclude_link_attributes'
		 * Optionally exclude specific attributes
		 * To use: return a 1 dimensional array of html attributes to exclude
		 * Ex: array('style','height','width')
		 * May be used together with allowed filter as well
		 */
		$exclude = apply_filters( 'pls/exclude_link_attributes', array(), $attrs, $this->get_filter_data() );

		if ( $exclude && is_array( $exclude ) )
		{
			foreach ( $exclude as $e )
				if ( array_key_exists( $e, $attrs ) )
					unset( $attrs[ $e ] );
		}

		// sanitize attributes
		return array_map( 'esc_attr', $attrs );
	}

	/**
	 * Allow shortcodes to be used inside shortcode attribute values by using {{}} instead of []
	 * @param  [type] $content [description]
	 * @return [type]          [description]
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
} // PLS_SC
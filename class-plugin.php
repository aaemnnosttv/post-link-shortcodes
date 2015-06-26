<?php

/**
 * Plugin Class
 * @package  Post Link Shortcodes
 */


class PostLinkShortcodes
{
	/* @deprecated 0.4.0 */
	protected static $instance;

	/* Captured post types */
	protected $types;

	protected $shortcodes = array(
		'all'        => array(),
		'registered' => array(),
	);

	protected $aliases = array();

	public function __construct() {}

	public function setup_hooks()
	{
		add_action( 'wp_loaded'     , array($this, 'capture_types') );
		add_action( 'admin_notices' , array($this, 'report_conflicts') );

		/**
		 * Default filters
		 */
		add_filter( 'pls/url', 'pls_attachment_src', 10, 2 );
		add_filter( 'pls/link_text', 'do_shortcode' );
		// clone the_title filters
		add_filter( 'pls/single_text', 'wptexturize' );
		add_filter( 'pls/single_text', 'convert_chars' );
		add_filter( 'pls/single_text', 'trim' );
	}

	/**
	 * Collect all post types
	 */
	public function capture_types()
	{
		$this->types = get_post_types( array('show_ui' => true), 'objects' );
		$this->register_dynamic_shortcodes();
		// Yes m'lord?
		do_action( 'pls/ready' );
	}

	/**
	 * Registers shortcodes for each captured post type
	 *
	 * Shortcodes by the same name that are already registered will not be overridden.
	 */
	function register_dynamic_shortcodes()
	{
		foreach ( $this->types as $type )
		{
			$this->register_shortcodes_for_type( $type );
		}
	}

	/**
	 * Registers shortcodes for the given post type
	 *
	 * At a minimum, the type will get these:
	 * [{type}_url]
	 * [{type}_link]
	 *
	 * If the post type was registered with `has_archive`,
	 * these two will also be registered:
	 * [{type}_archive_url]
	 * [{type}_archive_link]
	 *
	 * @param $type  post type object
	 */
	protected function register_shortcodes_for_type( $type )
	{
		foreach ( array( 'url', 'link' ) as $request )
		{
			$this->register_shortcode( "{$type->name}_$request" );

			if ( $type->has_archive )
				$this->register_shortcode( "{$type->name}_archive_$request" );
		}
	}

	/**
	 * @param       $tag
	 */
	protected function register_shortcode( $tag )
	{
		global $shortcode_tags;

		$this->shortcodes[ 'all' ][ ] = $tag;

		if ( ! shortcode_exists( $tag, $shortcode_tags ) )
		{
			add_shortcode( $tag, array($this, 'handler') );
			$this->shortcodes[ 'registered' ][ ] = $tag;
		}
		else
		{
			$this->conflicts[ $tag ] = $shortcode_tags[ $tag ];
		}
	}

	/**
	 * MASTER SHORTCODE HANDLER
	 * @param  array 	$atts    	shortcode atts
	 * @param  string 	$content 	enclosed shortcode content
	 * @param  string 	$tag     	shortcode tag
	 * @return string 				url / html
	 */
	public function handler( $atts, $content, $tag )
	{
		$sc = new PLS_SC( $atts, $content, $tag );

		if ( ! $url = $sc->get_url() )
		{
			/**
			 * @filter 'pls/output/not_found'
			 * @since  0.3.1
			 * @param string returned output for a target that is not found
			 * @param (array) current shortcode object variables
			 */
			return apply_filters( 'pls/output/not_found', '', $sc->get_filter_data() );
		}

		if ( 'url' == $sc->request )
			$output = $url;
		else
			$output = $sc->get_link();

		/**
		 * Final output
		 *
		 * @filter 'pls/output'
		 * @param (string) output
		 * @param (array) current shortcode object variables
		 */
		return apply_filters( 'pls/output', $output, $sc->get_filter_data() );
	}

	/**
	 * Alias shortcode callback handler
	 *
	 * @param $atts
	 * @param $content
	 * @param $tag
	 *
	 * @return string [type]          [description]
	 */
	function alias_handler( $atts, $content, $tag )
	{
		if ( ! isset( $this->aliases[ $tag ] ) )
			return;

		$alias_of = $this->aliases[ $tag ]['alias_of'];
		$defaults = $this->aliases[ $tag ]['defaults'];

		// make sure the alias target exists
		if ( ! in_array( $alias_of, $this->shortcodes['all'] ) )
			return;

		// will be '' if no atts in sc string
		if ( ! is_array( $atts ) )
			$atts = array();

		/**
		 * Default handling
		 */
		if ( $content )
		{
			$atts['text'] = $content;
			$content = '';
		}
		// now inner text will be modified the same way

		if ( is_array( $defaults ) && $defaults )
		{
			foreach ( $defaults as $dkey => $default )
			{
				$key = trim( $dkey, '+' );

				if ( isset( $atts[ $key ] ) && $flag = $this->get_flag( $dkey ) )
				{
					$atts[ $key ] = ( 'prefix' == $flag )
						? "{$default}{$atts[$key]}"
						: "{$atts[$key]}{$default}";
				}
				elseif ( isset( $atts[ $key ] ) )
					continue; // override default
				else
					$atts[ $key ] = $default;
			}
		}

		return $this->handler( $atts, $content, $alias_of );
	}

	/**
	 * Register an alias of a PLS shortcode to another tag
	 * Optionally allow defaults &/or pre/suffixed values!
	 *
	 * @param      $tag
	 * @param      $alias_of
	 * @param bool $defaults
	 */
	function alias( $tag, $alias_of, $defaults = false )
	{
		$this->aliases[ $tag ] = array(
			'alias_of' => $alias_of,
			'defaults' => $defaults,
		);

		add_shortcode( $tag, array($this, 'alias_handler') );
	}

	function get_flag( $a )
	{
		$len = strlen( $a );

		// check for a difference on either side
		if ( strlen( trim( $a, '+' ) ) === $len )
			return false;

		if ( strlen( ltrim( $a, '+' ) ) !== $len )
			return 'prefix';
		else
			return 'suffix';
	}

	/**
	 * Provides feedback about shortcodes which couldn't be registered
	 * without overriding existing ones.
	 */
	protected function report_conflicts()
	{
		if ( count( $this->conflicts ) ) {
			do_action( 'pls/conflicts', $this->conflicts );
		}
	}

	/**
	 * @deprecated 0.4.0
	 */
	public static function get_instance()
	{
		return PostLinkShortcodes();
	}

} // PostLinkShortcodes

/**
 * @return PostLinkShortcodes
 */
function PostLinkShortcodes()
{
	static $plugin;
	if ( ! $plugin ) {
		$plugin = new PostLinkShortcodes();
		$plugin->setup_hooks();
	}

	return $plugin;
}

/**
 * Filter callback for pls/url
 *
 * Returns attachment src for attachments
 *
 * @param $url
 * @param $data
 *
 * @return mixed
 */
function pls_attachment_src( $url, $data )
{
	if ( $attachment_src = wp_get_attachment_url( get_post_field('ID', $data['obj']) ) ) {
		$url = $attachment_src;
	}

	return $url;
}

/**
 * Register a NEW shortcode as an alias of a PLS shortcode
 *
 * Optionally define default values for attributes &/or prefix/suffix them
 * to the attributes passed by the shortcode!
 *
 * Note: function arguments differ from add_shortcode! (with the exception of the first)
 *
 * @param  string	$tag 		name of shortcode to add
 * @param  string	$alias_of	tag of shortcode to "connect" to
 * @param  mixed 	$defaults 	array of default attributes => values
 *
 * Prefix / Suffix
 * these are always applied as they are additive
 *
 * Prefix a value:
 * +class => 'someclass ' with a shortcode that passes class="myclass"
 * will produce an html class attribute class="someclass myclass"
 *
 * Suffix a value:
 * class+ => ' someclass' with a shortcode that passes class="myclass"
 * will produce an html class attribute class="myclass someclass"
 *
 * Defaults (no prefix/suffix):
 * defined values that are added if there is no existing value for the attribute
 * passed shortcode values will override defined defaults completely
 */
function pls_add_shortcode_alias( $tag, $alias_of, $defaults = false )
{
	PostLinkShortcodes()->alias( $tag, $alias_of, $defaults );
}
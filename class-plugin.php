<?php

/**
 * Plugin Class
 * @package  Post Link Shortcodes
 */


class PostLinkShortcodes
{
	/* @deprecated 0.4.0 */
	protected static $instance;

	var $types;
	private $shortcodes = array(
		'all'        => array(),
		'registered' => array(),
	);
	private $aliases = array();


	public function __construct() {}

	public function setup_hooks()
	{
		add_action( 'init', array(&$this, 'capture_types'), 500 );

		/**
		 * Default filters
		 */
		add_filter( 'pls/link_text',	'do_shortcode'  );
		// clone the_title filters
		add_filter( 'pls/single_text',	'wptexturize'   );
		add_filter( 'pls/single_text',	'convert_chars' );
		add_filter( 'pls/single_text',	'trim'          );
	}

	function capture_types()
	{
		$this->types = get_post_types( array('show_ui' => true) );
		$this->register_dynamic_shortcodes();
		// Yes m'lord?
		do_action( 'pls/ready' );
	}

	/**
	 * Registers 4 shortcodes for each registered post type
	 *
	 * [posttype_url]
	 * [posttype_link]
	 * [posttype_archive_url]
	 * [posttype_archive_link]
	 *
	 * Shortcodes by the same name that are already registered will not be overridden.
	 */
	function register_dynamic_shortcodes()
	{
		global $shortcode_tags;
		$conflicts = array();

		foreach ( $this->types as $type )
		{
			foreach ( array('url','link') as $request )
			{
				foreach ( array("{$type}_$request","{$type}_archive_$request") as $tag )
				{
					$this->shortcodes['all'][] = $tag;

					if ( !self::shortcode_exists( $tag, $shortcode_tags ) )
					{
						add_shortcode( $tag, array(&$this, 'handler') );
						$this->shortcodes['registered'][] = $tag;
					}
					else
						$conflicts[ $tag ] = $shortcode_tags[ $tag ];
				}
			}
		}

		if ( count( $conflicts ) )
			do_action( 'pls/conflicts', $conflicts );
	}

	/**
	 * MASTER SHORTCODE HANDLER
	 * @param  array 	$atts    	shortcode atts
	 * @param  string 	$content 	enclosed shortcode content
	 * @param  string 	$tag     	shortcode tag
	 * @return string 				url / html
	 */
	public static function handler( $atts, $content, $tag )
	{
		$sc = new PLS_SC( $atts, $content, $tag );

		if ( false === $url = $sc->get_url() )
		{
			/**
			 * @filter 'pls/output/not_found'
			 * @since  0.3.1
			 * @param string returned output for a target that is not found
			 * @param (array) current shortcode object variables
			 */
			return apply_filters( 'pls/output/not_found', '', $sc->get_filter_data() ); // not found
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
	 * @param  [type] $atts    [description]
	 * @param  [type] $content [description]
	 * @param  [type] $tag     [description]
	 * @return [type]          [description]
	 */
	function alias_handler( $atts, $content, $tag )
	{
		if ( !isset( $this->aliases[ $tag ] ) )
			return;

		extract( $this->aliases[ $tag ] );
		// $alias_of
		// $defaults

		// make sure the alias target exists
		if ( !in_array( $alias_of, $this->shortcodes['all'] ) )
			return;

		// will be '' if no atts in sc string
		if ( !is_array( $atts ) )
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
	 * @param  [type] $tag      [description]
	 * @param  [type] $alias_of [description]
	 * @param  array  $atts     [description]
	 * @param  string $content  [description]
	 * @return [type]           [description]
	 */
	function alias( $tag, $alias_of, $defaults = false )
	{
		$this->aliases[ $tag ] = array(
			'alias_of' => $alias_of,
			'defaults' => $defaults,
		);

		add_shortcode( $tag, array(&$this, 'alias_handler') );
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
	 * [shortcode_exists description]
	 * @param  [type] $tag [description]
	 * @return [type]      [description]
	 */
	static function shortcode_exists( $tag )
	{
		global $shortcode_tags;
		return array_key_exists( $tag, $shortcode_tags );
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
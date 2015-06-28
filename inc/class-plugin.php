<?php

/**
 * Plugin Class
 * @package  Post Link Shortcodes
 */


class PostLinkShortcodes
{
	/* @deprecated 0.4.0 */
	protected static $instance;

	/**
	 * Captured post types
	 * @var array
	 */
	protected $types = array();

	/**
	 * @var array
	 */
	protected $shortcodes = array(
		'all'        => array(),
		'registered' => array(),
	);

	/**
	 * Registered shortcode aliases
	 * @var array
	 */
	protected $aliases = array();

	public function __construct() {}

	/**
	 * Register core actions & filters
	 */
	public function setup_hooks()
	{
		add_action( 'wp_loaded'     , array($this, 'init') );
		add_action( 'admin_notices' , array($this, 'report_conflicts') );

		/**
		 * Default filters
		 */
		add_filter( 'pls/link_text', 'do_shortcode' );
		// clone the_title filters
		add_filter( 'pls/single_text', 'wptexturize' );
		add_filter( 'pls/single_text', 'convert_chars' );
		add_filter( 'pls/single_text', 'trim' );
	}

	/**
	 * Kick the tires, 'n light the fires
	 */
	public function init()
	{
		$this->capture_types();
		$this->register_shortcodes();

		// Yes m'lord?
		do_action( 'pls/ready' );
	}

	/**
	 * Collect all post types
	 */
	public function capture_types()
	{
		$types = get_post_types( array('show_ui' => true), 'objects' );

		/**
		 * @filter 'pls/types'
		 * @since 0.4.0
		 * @param array $types  post type objects
		 */
		$this->types = apply_filters( 'pls/types', $types );
	}

	/**
	 * Register all our shortcodes
	 */
	public function register_shortcodes()
	{
		$this->register_dynamic_shortcodes();

		if ( isset( $this->types['attachment'] ) )
		{
			$this->register_shortcode('attachment_src');
			$this->register_shortcode('attachment_img');
		}
	}

	/**
	 * Registers shortcodes for each captured post type
	 *
	 * Shortcodes by the same name that are already registered will not be overridden.
	 */
	function register_dynamic_shortcodes()
	{
		array_map( array($this, 'register_shortcodes_for_type'), $this->types );
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
		$sc = new PostLinkShortcode( $atts, $content, $tag );

		if ( ! $sc->get_object() )
		{
			/**
			 * @filter 'pls/output/not_found'
			 * @since  0.3.1
			 * @param string returned output for a target that is not found
			 * @param (array) current shortcode object variables
			 */
			return apply_filters( 'pls/output/not_found', '', $sc->get_filter_data() );
		}

		switch ( $sc->request )
		{
			case 'url' :
			case 'src' :
				$output = $sc->get_url();
				break;

			case 'link' :
				$output = $sc->get_link();
				break;

			case 'img' :
				$output = $sc->get_img();
				break;

			default:
				/**
				 * @filter 'pls/request/{request}'
				 * @since 0.4.0
				 * @param string output
				 * @param object PostLinkShortcode
				 */
				$output = apply_filters( "pls/request/$sc->request", '', $sc );
		}

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

}

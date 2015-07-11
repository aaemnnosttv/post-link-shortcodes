<?php

/**
 * Shortcode Class
 * @package  Post Link Shortcodes
 */

class PostLinkShortcode
{
    /**
     * The shortcode tag
     * @var string
     */
    protected $tag;

    /**
     * Target object
     * @var WP_Post|stdClass
     */
    protected $obj;

    /* @var string */
    protected $request;

    /* @var boolean */
    protected $archive;

    /* @var string post type */
    protected $type;

    /* html element name */
    protected $element;

    /* @var string */
    protected $url;

    /* @var string */
    protected $src;

    /* @var string  enclosed shortcode content */
    protected $content;

    /* @var string  element inner content */
    protected $inner;

    /* @var array */
    protected $data = array();

    /* @var array */
    protected $attrs = array();

    /* @var string  html attribute which should receive the url */
    protected $url_attribute;

    /* @var array  shortcode attributes reserved for output control */
    protected $reserved_keys = array( 'post_id', 'slug', 'inner', 'text' );

    /* @deprecated 0.4.0 */
    protected $_url;


	/**
     * Create a new PostLinkShortcode
     *
     * @param $atts
     * @param $content
     * @param $tag
     */
    public function __construct( $atts, $content, $tag )
    {
        if ( ! is_array( $atts ) )
            $atts = array();

        $this->data['orig'] = array(
            'atts'    => $atts,
            'content' => $content,
            'tag'     => $tag,
        );

        $this->content = $content;

        $this->setup_props( $tag );

        $this->setup_element();

        $this->setup_attributes( $atts );

        $this->setup_data( $atts );

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
                array_push( $this->reserved_keys, 'size' );
                break;

            case 'src' :
                array_push( $this->reserved_keys, 'size' );
                break;
        }

        if ( $this->url_attribute ) {
            array_push( $this->reserved_keys, $this->url_attribute );
        }
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
        if ( $this->archive ) return; // archives only require {type}

        // the data has been passed explicitly - bail!
        if ( $this->data['post_id'] || $this->data['slug'] ) return;

        // shorthand value must be the first keyless shortcode attribute
        if ( isset( $atts[ 0 ] ) )
        {
            $value              = $this->do_att_shortcode( $atts[ 0 ] );
            $key                = is_numeric( $value ) ? 'post_id' : 'slug';
            $this->data[ $key ] = $value;
            unset( $this->attrs[ 0 ] );
            unset( $this->data[ 0 ] );
        }
    }

    /**
     * @return object|null
     */
    protected function setup_object()
    {
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

    /**
     * Determines and sets what should be used for the element's inner content
     */
    protected function setup_inner()
    {
        // If someone took the time to use an enclosed shortcode, that takes precedence
        if ( strlen( $this->content ) )
            return $this->inner = $this->content;

        // Allow inner content to be set with an inner="" attribute
        if ( strlen( $this->data['inner'] ) )
            return $this->inner = $this->do_att_shortcode( $this->data['inner'] );

        // Allow inner text to be set with a text="" attribute
        if ( strlen( $this->data['text'] ) )
            return $this->inner = $this->do_att_shortcode( $this->data['text'] );

        // dynamic
        if ( $this->archive )
        {
            $inner = $this->obj->labels->name;

            /**
             * Archive link text
             * @filter 'pls/archive_text'
             * @deprecated 0.4.0
             *
             * @param (string) post type name
             * @param (object) post type object
             * @param (array) current shortcode object variables
             */
            if ( has_filter('pls/archive_text') )
            {
                _deprecated_argument('add_filter', '0.4.0',
                    "The filter tag 'pls/archive_text' is deprecated.'
                    Use 'pls/inner' instead."
                );
                $inner = apply_filters( 'pls/archive_text', $inner, $this->obj, $this->get_filter_data() );
            }

            return $this->inner = $inner;
        }

        $inner = $this->obj->post_title;

        /**
         * Single post link text
         * @filter 'pls/single_text'
         * @deprecated 0.4.0
         *
         * @param (string) post title
         * @param (object) post object
         * @param (array) current shortcode object variables
         */
        if ( has_filter('pls/single_text') )
        {
            _deprecated_argument('add_filter', '0.4.0',
                "The filter tag 'pls/single_text' is deprecated.'
                Use 'pls/inner' instead."
            );
            $inner = apply_filters( 'pls/single_text', $inner, $this->obj, $this->get_filter_data() );
        }

        return $this->inner = $inner;
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

        return $this->url = ( $obj instanceof WP_Post ) ? get_permalink( $obj ) : false;
    }

    /**
     * @return bool|string
     */
    protected function setup_src()
    {
        if ( ! $this->get_object() ) return;

        return $this->src = $this->get_attachment_src();
    }

    /**
     * Get the shortcode's target object
     */
    public function get_object()
    {
        if ( ! isset( $this->obj ) ) {
            $this->setup_object();
        }

        /**
         * @filter 'pls/object'
         * @param WP_Post|stdClass
         * @param array current shortcode object variables
         */
        return apply_filters( 'pls/object', $this->obj, $this->get_filter_data() );
    }

    /**
     * Get the proper URI based on the request
     * @return string
     */
    public function get_uri()
    {
        return $this->is_src()
            ? $this->get_src()
            : $this->get_url();
    }

    /**
     * Get the target url (permalink)
     * @return string
     */
    public function get_url()
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

    /**
     * Get the target src (attachment img, file, etc)
     * @return string
     */
    public function get_src()
    {
        if ( ! isset( $this->src ) ) {
            $this->setup_src();
        }

        /**
         * The SRC
         *
         * @filter 'pls/src'
         * @param (mixed) the SRC if found, or (bool) false on failure
         * @param (array) current shortcode object variables
         */
        $src = apply_filters( 'pls/src', $this->src, $this->get_filter_data() );

        return esc_url( $src );
    }

    /**
     * @return bool|string
     */
    public function get_attachment_src()
    {
        $object = $this->get_object();

        if ( has_post_thumbnail( $object->ID ) ) {
            $object = get_post( get_post_thumbnail_id( $object->ID ) );
        }

        if ( ! get_attached_file( $object->ID ) ) return;

        if ( wp_attachment_is_image( $object->ID ) )
        {
            $size = ! empty( $this->data['size'] ) ? $this->data['size'] : 'full';
            $attachment = (array) wp_get_attachment_image_src( $object->ID, $size );
            return reset( $attachment );
        }

        return wp_get_attachment_url( $object->ID );
    }

    /**
     * Get Link (anchor) markup
     * By this point, a URL has successfully been found
     * @return mixed|void (string)
     */
    public function get_link()
    {
        $html_attributes = $this->format_html_attributes( $this->get_attrs() );

        $inner = $this->get_inner();

        /**
         * Inner link text/html
         *
         * do_shortcode is applied by default
         * @see  PostLinkShortcode::setup_hooks
         *
         * @filter 'pls/link_text'
         * @deprecated 0.4.0
         * @param (string)	inner 	current inner html of link
         * @param (array)	array() current shortcode object variables
         */
        if ( has_filter('pls/link_text') )
        {
            _deprecated_argument('add_filter', '0.4.0',
                "The filter tag 'pls/link_text' is deprecated.'
                Use 'pls/inner' instead."
            );
            $inner = apply_filters( 'pls/link_text', $inner, $this->get_filter_data() );
        }

        /**
         * The final link markup
         *
         * @filter 'pls/link'
         * @param (string) markup
         * @param (array) current shortcode object variables
         */
        return apply_filters( 'pls/link', "<$this->element $html_attributes>$inner</$this->element>", $this->get_filter_data() );
    }

    /**
     * @return mixed|void
     */
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
    public function get_inner()
    {
        if ( ! isset( $this->inner ) ) {
            $this->setup_inner();
        }

        /**
         * @filter 'pls/inner'
         * @param inner html element content
         * @param shortcode data
         */
        return apply_filters( 'pls/inner', $this->inner, $this->get_filter_data() );
    }

    /**
     * Get sanitized, qualified attributes
     * @return array
     */
    public function get_attrs()
    {
        $attrs = $this->attrs;

        /**
         * Optionally restrict html attributes to only those defined here
         *
         * This is a whitelist of allowed attribute names. All others will be removed.
         *
         * To use: return a 1 dimensional array of allowed html attributes
         * Ex: return array('href','id','class')
         *
         * @filter 'pls/{request}/attributes/allowed'
         * @param array array()	empty array to be filled with html attribute names to whitelist
         * @param array $attrs	current set of html attribute => value
         * @param array array() current shortcode object variables
         */
        $allowed = array();

        if ( 'link' == $this->request ) {
            /**
             * @filter 'pls/allowed_link_attributes'
             * @deprecated 0.4.0
             */
            if ( has_filter('pls/allowed_link_attributes') )
            {
                _deprecated_argument('add_filter', '0.4.0',
                    "The filter tag 'pls/allowed_link_attributes' is deprecated.'
                    Use 'pls/link/attributes/allowed' instead."
                );
                $allowed = apply_filters( 'pls/allowed_link_attributes', $allowed, $attrs, $this->get_filter_data() );
            }
        }
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
         * @filter 'pls/{request}/attributes/disallowed'
         * @param (array)	array()	empty array to be filled with html attribute names to blacklist
         * @param (array)	$attrs	current set of html attribute => value
         * @param (array)	array() current shortcode object variables
         *
         * May be used together with 'pls/{request}/attributes/allowed' filter as well
         */
        $disallowed = $this->reserved_keys;

        if ( 'link' == $this->request ) {
            /**
             * @filter 'pls/exclude_link_attributes'
             * @deprecated 0.4.0
             */
            if ( has_filter('pls/exclude_link_attributes') )
            {
                _deprecated_argument('add_filter', '0.4.0',
                    "The filter tag 'pls/exclude_link_attributes' is deprecated.'
                    Use 'pls/link/attributes/disallowed' instead."
                );
                $disallowed = apply_filters( 'pls/allowed_link_attributes', $disallowed, $attrs, $this->get_filter_data() );
            }
        }
        $disallowed = apply_filters( "pls/$this->request/attributes/disallowed", $disallowed, $attrs, $this->get_filter_data() );

        if ( $disallowed && is_array( $disallowed ) )
        {
            foreach ( $disallowed as $e )
                unset( $attrs[ $e ] );
        }

        unset( $attrs[ $this->url_attribute ] );
        $attrs = array_merge( array( $this->url_attribute => $this->get_uri() ), $attrs );

        // sanitize attribute values
        return array_map( 'esc_attr', $attrs );
    }

    /**
     * Get current data relevant for filter callbacks
     * @return array
     */
    public function get_filter_data()
    {
        return get_object_vars( $this );
    }

    /**
     * Allow shortcodes to be used inside shortcode attribute values by using {{}} instead of []
     *
     * @param $content
     *
     * @return string
     */
    public function do_att_shortcode( $content )
    {
        if ( false !== strpos($content, '{{') && false !== strpos($content, '}}') )
        {
            $convert = str_replace( array('{{','}}'), array('[',']'), $content );
            $content = do_shortcode( $convert );
        }
        return $content;
    }

    /**
     * Provide read-only access to protected properties
     *
     * @param $name
     *
     * @return null
     */
    public function __get( $name )
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
                $attr_pairs[ ] = $value;
            } elseif ( ! is_numeric( $name ) && strlen( $value ) ) {
                $attr_pairs[ ] = sprintf( '%s="%s" ', $name, $value );
            }
        }
        $html_attributes = trim( join( ' ', $attr_pairs ) );

        return $html_attributes;
    }

    /**
     * Whether or not the url should return an src, rather than permalink
     * @return bool
     */
    public function is_src()
    {
        if ( in_array( $this->request, array( 'src','img' ) ) ) return true;

        // href=src syntax
        if ( ! empty( $this->data['href'] ) && 'src' == $this->data['href'] ) return true;

        return false;
    }

}

/**
 * Class PLS_SC
 * PostLinkShortcode alias
 * @deprecated 0.4.0
 */
class PLS_SC extends PostLinkShortcode {}
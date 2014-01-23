=== Plugin Name ===
Stable tag: 0.3
Contributors: aaemnnosttv
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LRA4JZYALHX82
Tags: post link, post url, custom post type link, custom post type url, shortcodes
Requires at least: 2.9
Tested up to: 3.6.1
License: GPLv2 or later

A plugin for dynamically adding a collection of shortcodes for building links to a given post/archive (of any type) or simply returning the URL!

== Description ==

Aptly titled *Post Link Shortcodes*, the plugin dynamically creates shortcodes for each registered post type.  These shortcodes can be used to create an html anchor to the post, post type archive or just return the URL for either.

Each post type will have 4 shortcodes created for it where `posttype` is the name of the post type. Eg: `post` or `page`

`[posttype_url]
[posttype_link]
[posttype_archive_url]
[posttype_archive_link]`


= Attributes & Usage =

Depending on what kind of returned information is desired, the right shortcode will need to be used and some attributes may be required.


= URL =

**Using Post ID**
`[post_url post_id=1]`
`[page_url post_id=2]`
**Using Post "slug"** (`post_name` - does not require pretty permalinks)
`[post_url slug="hello-world"]`
`[page_url slug="sample-page"]`
**"Shorthand" (slug|ID) - no attribute name!**
`[post_url hello-world]` or `[post_url 1]` 
`[page_url sample-page]` or `[page_url 2]`

= Archive =

*By default, built-in WordPress post types do not have archive pages, therefore this feature is geared more for custom post types.
For this example, I will reference a hypothetical custom post type 'book' which when registered was setup with `'has_archive' => true`.*

`[book_archive_url]`
That's it!

= Link / Anchor =

The link shortcodes are fully controlable. The attributes are the same as the URL shortcodes for establishing the target (href).

**Link Text**

Link text (anchor inner html) is set by either:

**Dynamic**

* `[posttype_link]` defaults to target post title
* `[posttype_archive_link]` defaults to posttype name

**Static**

* `[posttype_link]this is link text[/posttype_link]` OR
* `[posttype_link text="this is link text"]`

Link text supports shortcodes.

**The rest of the attributes are created by YOU!
Any other attribute="value" you use, will be added to the html element.**

( *Allowed attributes can be restricted using filters* )

**Example:** `[posttype_link id=mylink class="blue special" target=_blank]`
will produce: `<a href="url-to-post" id="mylink" class="blue special" target="_blank">Post Title</a>`

Any attribute you set will be added to the element with the exception of those specifically used by PLS - ie: 'slug', 'post_id', or 'text'. 'href' is also ignored as a shortcode attribute as it is set dynamically!

**Note: for html attributes with hyphens, like `data-target` for example, you need to use an underscore instead *when defining it in shortcode* (see below) - otherwise this can and probably will break things.**
This is the only time you'll need to make a substituion/compensation and it only applies to the attribute name, not the value. 

**Politician CPT**
`[candidate_link charles-mchutchence class=good-guy style="color: gray;" data_can_water_ski=1]`

Now we're getting silly, but you get the idea.
`<a href="queried-url" class="great-guy" style="color: gray;" data-can-water-ski="1">Charles McHutchence</a>`

== Shortcode Support ==

PLS supports the use of shortcodes inside enclosed shortcodes: `[post_link hello-world][some_other_shortcode][/post_link]`
**as well as inside all shortcode attribute values.**  To use in this way, simply use double curly braces around the shortcode instead of square brackets like so:
`[post_link hello-world text="{{some_other_shortcode}}"]`

Or suppose you had a shortcode `[the_id]` to return the current post ID.  You could use PLS to create a self-referencing link like so:
`[post_link {{the_id}} text="This post!"]`

The sky is the limit!

== Filters ==

PLS has several filters to control the output.

* `pls/url` - a returned value of (bool) `false` will kill further output/processing - equivalent to no target found.
* `pls/not_found` - markup to return in the event the target is not found. Default: <code>''</code> (empty string)
* `pls/single_text` - inner text for anchor for a single post
* `pls/archive_text` - inner text for anchor for a post archive page
* `pls/link_text` - inner text for anchor
* `pls/link` - link markup
* `pls/output` - final returned output

Each filter callback accepts at least 2 parameters, some 3.  See the source for more information about each filter.  It's well documented! 

**Link Attribute Control:**

By default, all html attributes are allowed in link shortcodes.  If you wish to restrict those that can be used, there are 2 filters for doing so.

* `pls/allowed_link_attributes` - indexed array of all allowed attribute names.  All others are stripped out.  I would recommend including 'href' here if you use this filter as it is not protected.

* `pls/exclude_link_attributes` - indexed array of attribute names NOT to allow.

The two filters may be used together.

== More ==

[Post Link Shortcodes on GitHub!](https://github.com/aaemnnosttv/Post-Link-Shortcodes)



== Installation ==

1. Upload the `post-link-shortcodes` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.


== Changelog ==

= 0.3.1 =
* Fix a bug where a shortcode would return data for the current page/post when searching for a target post by a slug that does not or no longer exists.

= 0.3 =
* Aliases introduced

= 0.2 =
* Initial release!
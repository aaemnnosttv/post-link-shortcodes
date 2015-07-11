=== Post Link Shortcodes ===
Stable tag: 0.3.1
Contributors: aaemnnosttv
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LRA4JZYALHX82
Tags: shortcode, custom post type, post link, post url, custom post type link, custom post type url, shortcodes
Requires at least: 3.6
Tested up to: 4.2.2
License: GPLv2 or later

A collection of shortcodes for building links, images, or URLs to a given post/archive of any type.

== Description ==

Aptly titled *Post Link Shortcodes*, the plugin dynamically creates shortcodes for each registered post type.  These shortcodes can be used to create an html anchor to the post, post type archive or just return the URL for either.

Each post type will have 2 shortcodes created for it where `{type}` is the name of the post type. Eg: `post` or `page`

`[{type}_url]
[{type}_link]`

If the post type was registered with `has_archive`, two more shortcodes will also be created:

`[{type}_archive_url]
[{type}_archive_link]`

**NEW in 0.4.0:**
If the post type supports `thumbnail` (featured images) two more shortcodes will also be created:
`[{type}_src]
[{type}_img]`

**NEW in 0.4.0:**
Attachment shortcodes
`[attachment_src]
[attachment_img]`


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

If used, shorthand values must be the first _keyless_ shortcode attribute if any others are used.

= Archive =

*By default, built-in WordPress post types do not have archive pages, therefore this feature is geared more for custom post types.
For this example, I will reference a hypothetical custom post type 'book' which when registered was setup with `'has_archive' => true`.*

`[book_archive_url]`
That's it!

= HTML Elements = 
Prior to v0.4.0, Post Link Shortcodes were limited to creating HTML anchors - "links" - to their target resource.

With 0.4.0, that support has been extended to images as well.

= HTML Element Attributes =

Post Link Shortcodes aims to provide maximum flexibility when it comes to adding attributes to the html element you are generating.  You can essentially use whatever you want.

The html attributes mirror the attributes used by the shortcode, with the exception of the reserved control attributes: `post_id`, `slug`, `inner`, `text`, and - where applicable - `size`.

To reiterate, any other attribute="value" added to the shortcode, will be added to the html element.  It really couldn't be easier!

( *Allowed attributes can be restricted using filters* )

= Images =

Images can be rendered in two ways, either by using the `attachment_img` shortcode or by the dynamic `{type}_img` shortcode, which is added for all registered post types which support `thumbnail` (featured images).


= Link / Anchor =

The link shortcodes are fully controlable. 

**Link Text**

Link text (inner html) is set by either:

**Dynamic**

* `[{type}_link]` defaults to target post title
* `[{type}_archive_link]` defaults to {type} name

**Static** (in order of dominance)
* `[{type}_link]this is link text[/{type}_link]`
* `[{type}_link inner="this is link text"]`
* `[{type}_link text="this is link text"]`

Link text supports shortcodes.

== Linking to an SRC ==

Post Link Shortcodes refers to an `src` as the URL to an image, or attachment of any other type.
By default, a `{type}_link` shortcode will use the target's permalink as the URL.  For targets with a featured image, or attachments where you want to create a link to the target's src, simply use the `href=src` attribute pair.  That's it!

E.g. `[attachment_link 5234 href=src text="Download the PDF manual" class="pdf" download]`

This would generate an html anchor with the href pointing to the file URL for the attachment with ID 5234, which might look something like this:
`<a href="http://example.com/wp-content/uploads/2015/06/user-manual.pdf" class="pdf" download>Download the PDF manual</a>`

Note the keyless/boolean `download` attribute.  This is an HTML5 attribute which modern browsers understand that the target url is to be downloaded, rather than navigated to.  Try it!  Great for file downloads like the above example.



**Example:** `[{type}_link id=mylink class="blue special" target=_blank]`
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

* `pls/object` - the target object
* `pls/url` - the target url/src
* `pls/single_text` - inner text for anchor for a single post
* `pls/archive_text` - inner text for anchor for a post archive page
* `pls/link_text` - inner text for anchor
* `pls/inner` - html element's inner html
* `pls/link` - link markup
* `pls/img` - image markup
* `pls/output` - final returned output
* `pls/output/not_found` - markup to return in the event the target is not found. Default: `''` (empty string)

Each filter callback accepts at least 2 parameters, some 3.  See the source for more information about each filter.  It's well documented!

**Link Attribute Control:**

By default, all html attributes are allowed in link shortcodes.  If you wish to restrict those that can be used, there are 2 filters for doing so.

* `pls/{request}/attributes/allowed` - indexed array of all allowed attribute names.  All others are stripped out.  I would recommend including 'href' here if you use this filter as it is not protected.

* `pls/{request}/attributes/disallowed` - indexed array of attribute names NOT to allow.

The two filters may be used together.

== More ==

[Post Link Shortcodes on GitHub!](https://github.com/aaemnnosttv/Post-Link-Shortcodes)



== Installation ==

1. Upload the `post-link-shortcodes` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.


== Changelog ==

= 0.4.0 =
* Requires PHP 5.4 or above
* NEW: attachment_src and attachment_img shortcodes
* NEW: added filters
* Various improvements and optimizations under the hood
* Added support for boolean html attributes 

= 0.3.1 =
* Fix a bug where a shortcode would return data for the current page/post when searching for a target post by a slug that does not or no longer exists.

= 0.3 =
* Aliases introduced

= 0.2 =
* Initial release!
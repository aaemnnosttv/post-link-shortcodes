=== Post Link Shortcodes ===
Stable tag: 0.4.1
Contributors: aaemnnosttv
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LRA4JZYALHX82
Tags: shortcode, custom post type, post link, post url, custom post type link, custom post type url, shortcodes
Requires at least: 3.6
Tested up to: 5.2.4
License: GPLv2 or later

A collection of shortcodes for building links, images, or URLs to a given post/archive of any type.

== Description ==

This plugin dynamically adds a collection of helpful shortcodes for each registered post type.  These shortcodes can be used to return URLs, or generate HTML elements like anchors and images to the related post!

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

`[post_src hello-world]
[post_img hello-world size=medium]`

**For more information and examples: [Read the Documentation](https://github.com/aaemnnosttv/post-link-shortcodes/wiki)**

== Screenshots ==

1. Basic usage. Source shortcode, with html-escaped output in blue.
1. Post thumbnail usage.  Works for any post type which supports post thumbnails as well as attachments. Source shortcode, with html-escaped output in blue.
1. Attachment examples. Source shortcode, with html-escaped output in blue.

== More ==

[Post Link Shortcodes on GitHub!](https://github.com/aaemnnosttv/Post-Link-Shortcodes)

== Installation ==

1. Upload the `post-link-shortcodes` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Upgrade Notice ==

= 0.4.1 =
Fixed unregistered `{type}_archive_url` shortcode


== Changelog ==

= 0.4.1 =
* FIXED: Unregistered `{type}_archive_url` shortcode

= 0.4.0 =
* NEW: `attachment_src` and `attachment_img` shortcodes
* NEW: `{type}_src` and `{type}_img` shortcodes for types that support post thumbnails
* NEW: added filters
* Various improvements and optimizations under the hood
* Added support for boolean html attributes 

= 0.3.1 =
* Fix a bug where a shortcode would return data for the current page/post when searching for a target post by a slug that does not or no longer exists.

= 0.3 =
* Aliases introduced

= 0.2 =
* Initial release!

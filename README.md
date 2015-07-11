# Post Link Shortcodes #
**Stable tag:** 0.3.1  
**Contributors:** aaemnnosttv  
**Donate link:** https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LRA4JZYALHX82  
**Tags:** shortcode, custom post type, post link, post url, custom post type link, custom post type url, shortcodes  
**Requires at least:** 3.6  
**Tested up to:** 4.2.2  
**License:** GPLv2  

A collection of shortcodes for building links, images, or URLs to a given post/archive of any type.

## Description ##

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

**[Read the Documentation](https://github.com/aaemnnosttv/post-link-shortcodes/wiki)**

## More ##

[Post Link Shortcodes on GitHub!](https://github.com/aaemnnosttv/Post-Link-Shortcodes)



## Installation ##

1. Upload the `post-link-shortcodes` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.


## Changelog ##

### 0.4.0 ###
* NEW: attachment_src and attachment_img shortcodes
* NEW: added filters
* Various improvements and optimizations under the hood
* Added support for boolean html attributes 

### 0.3.1 ###
* Fix a bug where a shortcode would return data for the current page/post when searching for a target post by a slug that does not or no longer exists.

### 0.3 ###
* Aliases introduced

### 0.2 ###
* Initial release!
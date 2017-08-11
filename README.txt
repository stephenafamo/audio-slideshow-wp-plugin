=== Plugin Name ===
Contributors: stephenafamo
Donate link: http://stephenafamo.com
Tags: html, php, custom pages, custom templates, custom posts
Requires at least: 3.0.1
Tested up to: 4.8
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Use your HTML or PHP files for any page or post.

== Description ==

This plugin allows you to use any HTML or PHP file as the template for any page or post. 

Simply upload the file and select it. 
You can upload custom js and css files into the media library and link to them from the HTML file.

Options:

* Overwrite All: You overwrite the entire theme and use your custom file
* Overwrite Content: Keeps the header, footer, sidebar, e.t.c. Simply overwrites the body of the page or post
* Above Content: Your custom content is simply added to the top of the page content
* Below Content: You custom content is placed just beneath the page content.

= Adding support for custom post types =

By default the pulugin works with pages and posts, however, go to the settings to enable it on any other registered post type.

use the `hppp_post_types` filter to add more post types.

Like this:

		public function post_type_modify ($post_types) {
            $post_types[] = 'custom_post_type';
            return $post_types;
        }

	    add_filter( 'hppp_post_types', 'post_type_modify' );


== Installation ==

1. Upload `html-php-pages-and-posts` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= What about external JS and CSS files? =

You can upload it all into the media library. Simply reference them properly in the html file. 
e.g. `<link rel='stylesheet' href='http://example.com/wp-content/2017/01/my_custom_stylesheet.css' type='text/css' />`

= Will template tags work in my custom templates? =

Yes.
All wordpress functions, and any installed plugin function will work if called properly

= What post types will this work with? =

By default, it can only be used by on pages and posts, but you can add any other post type by hooking into the `hppp_post_types` filter.

== Changelog ==

= 2.0.0 =
* Add settings page to enable support for any registered post type
* Allow users to set default template for any registered post type

= 1.1.0 =
* Add filter hook to modify supported post_types

= 1.0.0 =
* First release
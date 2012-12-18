=== Plugin Name ===
Contributors: mwaterous, chipbennett
Donate Link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9847234
Tags: short, link, bitly, url, shortener, social, media, twitter
Requires at least: 2.9.2
Tested up to: 3.0.3
Stable tag: 0.2.6

WP Bit.ly uses the Bit.ly API to generate short links for all of your posts and pages. Statistics are displayed for each link from the dashboard.

== Description ==

WP Bit.ly allows you to generate short links using the Bit.ly API for all of your blogs posts and pages.

The generated short links can than be used by you, your visitors and a variety of other services that employ them. Using shortcode or PHP template tags, the short links can than be displayed directly on your pages so that people can use them for bookmarks, email, twitter, or other social media sites to link back to your pages.

Future development will include the ability to use your own domain as the short link (http://yourdomain.com/bXhGjs).

Features of the current version also include the generation of a new meta box on your posts that show you statistics about your link. In addition to a regular statistics plugin you can use this plugin to see who's clicking on your links!

== Installation ==

Installation of WP Bit.ly is as easy as:

1. Upload the entire `/wp-bitly/` folder to your `/wp-content/plugins/` directory.
1. Navigate to the 'Plugins' page of your dashboard, and activate WP Bit.ly
1. After activation proceed to the WP Bit.ly options page and configure your Bit.ly username and API key.
1. A new metabox will appear on the options page to generate short links for all your existing posts and pages (including custom post types if required).
1. New posts will automatically generate shortlinks as you create them!

== Frequently Asked Questions ==

= After installation, do I need to update all my posts for short links to be created? =

No, WP Bit.ly can do this for you automatically through the options page. Select the type of post you would like to generate short links for, click 'Generate', and WP Bit.ly will take care of the rest!

= What if I have #### posts? Will the bit.ly API limit me? =

At present we have seen the generator perform within the bit.ly rate limits for sites with under 1000 posts - for sites over this, you can still run the generation as bit.ly will not ban you, simply stop you. Any posts that are missed will automatically generate their own short links when they are accessed on the front end (either by visitor, crawler, or other).

= Will the automatic generation slow my site down? =

No, especially since it only occurs once and every access after will use the locally stored shortlink.

= What happens if I change a posts permalink? =

WP Bit.ly validates all short links whenever you update a post, so if you change the permalink or location of the post, your old short link will be replaced with a new one.

= Does WP Bit.ly conform to the HTML/HTTP shortlink specification? =

With help from the new WordPress 3.0 Shortlink API, WP Bit.ly conforms with the specification by not only inserting the appropriate meta element in each of your pages, but also the HTTP headers.

= How do I include the short links using WordPress shortcode? =

The WP Bit.ly shortcode for including short links directly in your posts is quite simply [wpbitly]. This shortcode will also accept the same arguments that you can pass to the_shortlink(), including 'text', 'title', 'before' and 'after'.

= How do I include the short links using PHP? =

If you are using WordPress 3.0 (remember to upgrade!) all you have to do is include a call to the_shortlink() anywhere in your theme or plugin. If you are using WordPress 2.9.2 or earlier, there is a function called wpbitly_print() located in deprecated.php that you can use.

== Changelog ==

= 0.2.6 =

* Added support for automatic generation of shortlinks when posts are viewed.

= 0.2.5 =
* Added support for WordPress 3.0 shortlink API
* Added support for custom post types.
* Various revisions and minor bug fixes throughout the entire plugin.


= 0.1.5 =
* Short link header data wasn't being properly inserted.

= 0.1.4 =
* Fixed a bug in the short link generation for existing posts and pages

= 0.1.0 =
* Initial release of WP Bit.ly

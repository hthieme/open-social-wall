=== Open Social Wall ===
Contributors:
Tags: mastodon, pixelfed, fediverse, social feed, masonry
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 0.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Aggregate public Mastodon and Pixelfed feeds into a responsive masonry-style social wall.

== Description ==

Open Social Wall combines public Mastodon RSS and Pixelfed Atom feeds into one chronological, responsive social wall.

Features include:

* Mastodon RSS support
* Pixelfed Atom support
* Responsive masonry-style layout
* Full post text without automatic truncation
* Image and multi-image support when supplied by the source feed
* Filter buttons for All, Mastodon, and Pixelfed
* Client-side Load more pagination
* Original-post links
* WordPress transient caching
* Configurable wall width, cache duration, initial post count, and batch size
* No bundled analytics or tracking

Instagram is not included in version 0.1.0. Scraping is intentionally not used.

== Installation ==

1. Upload the `open-social-wall` folder to `/wp-content/plugins/`, or install the plugin ZIP through the WordPress admin area.
2. Activate Open Social Wall.
3. Go to Settings > Open Social Wall.
4. Add a Mastodon RSS feed and/or Pixelfed Atom feed.
5. Add `[open_social_wall]` to a page or post.

== Frequently Asked Questions ==

= Does it require an API key? =

No. Version 0.1.0 uses public RSS/Atom feeds.

= Does it support Instagram? =

Not yet. A future official API connector is planned for eligible professional accounts. The project does not scrape Instagram.

= Why can I not load older posts? =

RSS and Atom sources often publish only a limited number of recent items. The plugin cannot retrieve history that is not present in the source feed.

= Do cards link back to the original post? =

Yes. Clicking a card opens the corresponding Mastodon or Pixelfed post in a new tab.

== Changelog ==

= 0.1.0 =
* Initial public release.
* Mastodon RSS and Pixelfed Atom aggregation.
* Responsive masonry layout.
* Filters and client-side Load more pagination.
* WordPress transient caching.

== Upgrade Notice ==

= 0.1.0 =
Initial public release.

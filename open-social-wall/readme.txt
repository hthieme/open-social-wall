=== Open Social Wall ===
Contributors:
Tags: mastodon, pixelfed, fediverse, rss, atom, social feed, masonry
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 0.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Aggregate Mastodon, Pixelfed and other public RSS/Atom feeds into one responsive social wall.

== Description ==

Open Social Wall combines public Mastodon RSS, Pixelfed Atom and additional RSS/Atom feeds into one chronological, responsive social wall.

Features include:

* Mastodon RSS support
* Pixelfed Atom support
* Additional RSS/Atom feeds with custom display names
* Responsive masonry-style layout
* Centered source filter bar
* Full post text without automatic truncation
* Image and multi-image support when supplied by the source feed
* Client-side Load more pagination
* Original-item links
* WordPress transient caching
* Configurable wall width, cache duration, initial post count, and batch size
* No bundled analytics or tracking

Generic RSS and Atom feeds vary in how they expose media. Text, dates and links are generally portable; image support depends on the source feed.

Instagram is not included in version 0.1.0. Scraping is intentionally not used.

== Installation ==

1. Upload the `open-social-wall` folder to `/wp-content/plugins/`, or install the plugin ZIP through the WordPress admin area.
2. Activate Open Social Wall.
3. Go to Settings > Open Social Wall.
4. Add a Mastodon RSS feed, Pixelfed Atom feed, or another public RSS/Atom feed.
5. Add `[open_social_wall]` to a page or post.

== Frequently Asked Questions ==

= Does it require an API key? =

No. Version 0.1.0 uses public RSS/Atom feeds.

= Can I add feeds other than Mastodon or Pixelfed? =

Yes. Use Additional feeds in Settings > Open Social Wall. Each additional source can have its own display name, which is shown in the source filter bar.

= Does it support Instagram? =

Not yet. A future official API connector is planned for eligible professional accounts. The project does not scrape Instagram.

= Why can I not load older posts? =

RSS and Atom sources often publish only a limited number of recent items. The plugin cannot retrieve history that is not present in the source feed.

= Do cards link back to the original item? =

Yes. Clicking a card opens the corresponding original item in a new tab.

== Changelog ==

= 0.1.0 =
* Initial public release.
* Mastodon RSS and Pixelfed Atom aggregation.
* Additional RSS/Atom sources with custom display names.
* Responsive masonry layout.
* Centered source filters and client-side Load more pagination.
* WordPress transient caching.

== Upgrade Notice ==

= 0.1.0 =
Initial public release.

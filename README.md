# Open Social Wall

[![PHP lint](https://github.com/hthieme/open-social-wall/actions/workflows/php-lint.yml/badge.svg)](https://github.com/hthieme/open-social-wall/actions/workflows/php-lint.yml)

Open Social Wall is a lightweight WordPress plugin that aggregates public **Mastodon RSS** and **Pixelfed Atom** feeds into one chronological, responsive masonry-style social wall.

It is designed for personal sites, portfolios, blogs, community pages, and other WordPress installations that want to display federated social posts without embedding a third-party social-wall service.

## Features

- Aggregates Mastodon and Pixelfed into one chronological stream
- Responsive masonry-style layout
- Full post text; no automatic truncation
- Image support, including multi-image posts when exposed by the feed
- Deterministic wide cards for selected visual posts
- Network filters: All, Mastodon, Pixelfed
- Client-side **Load more** pagination without page reloads
- Opens each card in the original network post
- WordPress transient caching
- Configurable initial post count, batch size, feed pool, cache duration, and maximum wall width
- No bundled analytics or tracking
- No third-party JavaScript dependencies

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer
- Public Mastodon RSS and/or Pixelfed Atom feed URLs

## Installation

### From a ZIP file

1. Download or build the `open-social-wall` plugin ZIP.
2. In WordPress, go to **Plugins → Add New Plugin → Upload Plugin**.
3. Upload the ZIP and activate **Open Social Wall**.
4. Go to **Settings → Open Social Wall**.
5. Enter at least one feed URL.
6. Add the shortcode to a page or post:

```text
[open_social_wall]
```

### From this repository

Copy the `open-social-wall` directory into:

```text
wp-content/plugins/
```

Then activate the plugin in the WordPress admin area.

## Configuration

Open **Settings → Open Social Wall**.

### Mastodon

For many Mastodon installations, a public profile feed follows this pattern:

```text
Profile: https://mastodon.example/@username
RSS:     https://mastodon.example/@username.rss
```

Availability can depend on the Mastodon server and account visibility.

### Pixelfed

A common Pixelfed Atom feed pattern is:

```text
Profile: https://pixelfed.example/username
Atom:    https://pixelfed.example/users/username.atom
```

Availability can depend on the Pixelfed instance and account visibility.

### Display settings

The settings page lets you configure:

- **Posts shown initially** — default: 20
- **Posts per “Load more”** — default: 12
- **Maximum feed pool** — default: 60
- **Cache duration** — default: 30 minutes
- **Maximum wall width** — default: 1680 px

RSS and Atom feeds often expose only a limited history. The maximum feed pool cannot retrieve posts that the source feed itself does not publish.

## Shortcode

Basic usage:

```text
[open_social_wall]
```

Override the initial number of visible posts:

```text
[open_social_wall initial="12"]
```

Hide the network filter buttons:

```text
[open_social_wall filters="no"]
```

Combine options:

```text
[open_social_wall initial="16" filters="no"]
```

## How links behave

Clicking a post card opens the original Mastodon or Pixelfed post in a new browser tab. Links inside post text also open safely in a new tab.

## Privacy

The plugin fetches configured public RSS/Atom feeds server-side through WordPress and caches normalized post data using WordPress transients. It does not include analytics or tracking code.

Images are currently loaded from the media URLs provided by the source feed. Site owners should consider their own privacy, caching, and content-policy requirements before deployment.

## Instagram

Instagram is **not included in version 0.1.0**. A future connector may use Meta's official API for eligible professional accounts. Scraping is intentionally not part of this project.

See [ROADMAP.md](ROADMAP.md).

## Development

The plugin intentionally uses plain PHP, CSS, and vanilla JavaScript. There is no build step.

Repository structure:

```text
open-social-wall/
├── assets/
│   ├── social-wall.css
│   └── social-wall.js
├── includes/
│   ├── class-osw-feed.php
│   └── class-osw-plugin.php
└── open-social-wall.php
```

Push a version tag such as `v0.1.0` to trigger the release workflow. It builds an installable plugin ZIP and creates a GitHub release automatically.

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) first.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

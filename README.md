# Open Social Wall

[![PHP lint](https://github.com/hthieme/open-social-wall/actions/workflows/php-lint.yml/badge.svg)](https://github.com/hthieme/open-social-wall/actions/workflows/php-lint.yml)

A lightweight WordPress plugin that combines public Mastodon and Pixelfed feeds into one responsive, chronological social wall.

Open Social Wall is designed for people who want to bring their Fediverse activity back to their own website without relying on a proprietary social-wall service.

It reads public RSS and Atom feeds server-side, normalizes the posts and displays them in a responsive masonry-style layout.

## Why Open Social Wall?

Social posts often live in separate networks and disappear into platform-specific timelines.

Open Social Wall brings them together on your own WordPress site.

The plugin focuses on three principles:

- **Open by default** — uses public RSS and Atom feeds
- **Lightweight** — plain PHP, CSS and vanilla JavaScript
- **Website first** — the social wall should look like part of your site, not like an embedded third-party widget

## Features

- Mastodon RSS feed support
- Pixelfed Atom feed support
- Chronological aggregation across networks
- Responsive masonry-style layout
- Full post text without automatic truncation
- Image and multi-image support when available in the source feed
- Deterministic wide cards for selected visual posts
- Network filters
- Client-side **Load more** without page reloads
- Original-post links for every card
- WordPress transient caching
- Configurable feed pool, cache duration and wall width
- No bundled analytics
- No third-party JavaScript dependencies

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer
- A public Mastodon RSS feed and/or Pixelfed Atom feed

## Installation

### Install from ZIP

1. Download the plugin ZIP.
2. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
3. Upload the ZIP and activate **Open Social Wall**.
4. Open **Settings → Open Social Wall**.
5. Add at least one feed URL.
6. Insert the shortcode:

```text
[open_social_wall]
```

### Install from source

Copy the `open-social-wall` directory into:

```text
wp-content/plugins/
```

Then activate the plugin in WordPress.

## Feed configuration

### Mastodon

Many Mastodon servers expose public profile RSS feeds using this pattern:

```text
Profile:
https://mastodon.example/@username

RSS:
https://mastodon.example/@username.rss
```

Feed availability depends on the Mastodon server and account visibility.

### Pixelfed

A common Pixelfed Atom feed pattern is:

```text
Profile:
https://pixelfed.example/username

Atom:
https://pixelfed.example/users/username.atom
```

Feed availability depends on the Pixelfed instance and account visibility.

## Display settings

Open **Settings → Open Social Wall**.

Available options include:

- Posts shown initially
- Posts loaded per click
- Maximum feed pool
- Cache duration
- Maximum wall width

Default values:

```text
Initial posts:       20
Load more batch:     12
Maximum feed pool:   60
Cache duration:      30 minutes
Maximum width:       1680 px
```

RSS and Atom feeds expose only a limited amount of history. The plugin cannot retrieve posts that are not available through the source feed.

## Shortcode

Basic usage:

```text
[open_social_wall]
```

Custom initial number of posts:

```text
[open_social_wall initial="12"]
```

Hide network filters:

```text
[open_social_wall filters="no"]
```

Combine options:

```text
[open_social_wall initial="16" filters="no"]
```

## Original post links

Every card links to the original post on Mastodon or Pixelfed.

Original posts open in a new browser tab.

## Privacy

Open Social Wall fetches configured public RSS and Atom feeds server-side through WordPress.

Normalized feed data is cached using WordPress transients.

The plugin:

- does not include analytics
- does not include tracking scripts
- does not require third-party JavaScript
- does not require API credentials for Mastodon or Pixelfed

Images are currently loaded from the media URLs published by the source feed.

Site owners should evaluate their own privacy and caching requirements.

## Roadmap

Planned ideas include:

- Gutenberg block
- optional Instagram connector through Meta's official API
- additional layout modes
- expanded automated testing
- WordPress Coding Standards checks

See [ROADMAP.md](ROADMAP.md).

## Development

The plugin intentionally has no build step.

Technology stack:

- PHP
- CSS
- vanilla JavaScript

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

Contributions are welcome.

Please read [CONTRIBUTING.md](CONTRIBUTING.md) before submitting changes.

## License

Open Social Wall is licensed under GPL-2.0-or-later.

See [LICENSE](LICENSE).

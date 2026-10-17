# Contributing

Thanks for considering a contribution to Open Social Wall.

## Before opening a pull request

1. Search existing issues and pull requests.
2. Keep changes focused and reasonably small.
3. Do not include credentials, access tokens, personal account URLs, or private data in code, fixtures, screenshots, commits, or issue reports.
4. Keep the plugin compatible with the minimum supported WordPress and PHP versions unless the change explicitly updates those requirements.
5. Prefer WordPress core APIs and vanilla JavaScript over new dependencies.
6. Preserve accessibility: keyboard interaction, visible focus states, semantic markup, and reduced-motion support matter.

## Coding style

- Follow WordPress PHP coding conventions where practical.
- Escape output at render time and sanitize saved settings.
- Prefix global PHP identifiers with `OSW_` / `osw_`.
- Do not minify or obfuscate source code in the repository.
- Avoid third-party tracking and telemetry.

## Testing

At minimum, test:

- activation on a clean WordPress installation
- settings save and sanitization
- Mastodon-only configuration
- Pixelfed-only configuration
- both feeds together
- empty configuration
- Load more behavior
- filters after additional cards are revealed
- card keyboard activation
- desktop, tablet, and mobile layouts

## Pull requests

Describe:

- what changed
- why it changed
- how it was tested
- whether the change affects privacy, external requests, compatibility, or stored options

By contributing, you agree that your contribution is licensed under GPL-2.0-or-later.

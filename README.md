# WPVDB Blocks

[![Checks](https://github.com/rbcorrales/wpvdb-blocks/actions/workflows/ci.yml/badge.svg)](https://github.com/rbcorrales/wpvdb-blocks/actions/workflows/ci.yml)
![WordPress](https://img.shields.io/badge/WordPress-6.9%2B-3858e9?logo=wordpress&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3%2B-777bb4?logo=php&logoColor=white)
[![License](https://img.shields.io/badge/License-GPLv2%2B-blue.svg)](LICENSE)

Editorial WordPress blocks powered by [`wpvdb-search`](https://github.com/rbcorrales/wpvdb-search).

## Requirements

[`wpvdb-search`](https://github.com/rbcorrales/wpvdb-search) installed and configured.

## Blocks

| Block | Purpose | Controls |
|---|---|---|
| `wpvdb-blocks/related-articles` | Shows related articles by comparing stored vectors for the current post against the embeddings table. It does not generate a new embedding during render. | Title, number of articles capped at 10, show excerpts. |

Each block is dynamic and registered from `block.json` metadata. The editor uses React and `useBlockProps()`. The frontend uses `get_block_wrapper_attributes()`, so WordPress provides the default wrapper class and block supports.
The plugin loads blocks through `WPVDB_Blocks\Block_Registry`. It uses the metadata collection APIs when they are available and falls back to per-block registration on older supported WordPress versions.

## Development

Install dependencies:

```bash
bun install
```

Build the block assets:

```bash
bun run build
```

Run the local checks:

```bash
bun run lint
```

The main branch maintenance workflow regenerates translation files and commits them when strings change. This plugin also has block editor JavaScript, so the i18n task rebuilds `languages/source-map.json` and refreshes the hashed JSON files WordPress uses for script translations. Release workflows regenerate translations before staging the zip and fail if generated files are out of date.

Run the same command locally only when you want to preview language file changes:

```bash
bun run i18n
```

## License

GPL-2.0-or-later.

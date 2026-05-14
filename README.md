# WPVDB Blocks

WPVDB Blocks provides editorial WordPress blocks powered by [`wpvdb-search`](https://github.com/rbcorrales/wpvdb-search).

## Requirements

- WordPress 6.9 or newer.
- WordPress with [`wpvdb-search`](https://github.com/rbcorrales/wpvdb-search) installed and configured.
- PHP 8.3 or newer.

## Block

The first block is `wpvdb-blocks/related-articles`.

The block compares stored vectors for the current post against the embeddings table. It does not generate a new embedding during render.
It is a dynamic block registered from `block.json` metadata. The editor uses React and `useBlockProps()`. The frontend uses `get_block_wrapper_attributes()`, so WordPress provides the default wrapper class and block supports.
The plugin loads blocks through `WPVDB_Blocks\Block_Registry`. It uses the metadata collection APIs when they are available and falls back to per block registration on older supported WordPress versions.

Block controls:

- Title.
- Number of articles, capped at 10.
- Show excerpts.

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

Regenerate translation files when strings change:

```bash
bun run pot
msgmerge --update --backup=none languages/wpvdb-blocks-es_ES.po languages/wpvdb-blocks.pot
msgfmt --check languages/wpvdb-blocks-es_ES.po -o languages/wpvdb-blocks-es_ES.mo
wp i18n make-json languages languages --no-purge
```

## License

GPL-2.0-or-later.

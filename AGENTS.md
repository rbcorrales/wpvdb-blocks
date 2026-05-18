# AGENTS.md

Agent guidance for this repository. Keep this file focused on non-obvious block, build, and release gotchas. General usage documentation belongs in `README.md`.

## Boundaries

- This repo is public. Do not add credentials, tokens, application passwords, site-specific hostnames, or private deployment details.
- Search behavior belongs in `https://github.com/rbcorrales/wpvdb-search`. Blocks should call the public search API and render the result.
- Do not add ingestion, queueing, provider settings, or embedding storage ownership here. Those belong in `https://github.com/Automattic/wpvdb`.
- Keep Playground fixture behavior in `https://github.com/rbcorrales/wpvdb-playground-demo`. Do not hardcode demo models or sample data in block code.
- The suite Playground Blueprint consumes this plugin's release zip. Keep the zip directly installable when changing `.distignore`, build outputs, or required artifacts.

## Block gotchas

- The related articles block must not generate embeddings during render. It compares stored vectors through `WPVDB_Search\Search::related_to_post()`.
- Related articles are expected to work in the suite Playground through `wpvdb-search` related lookup fallbacks and demo-side model routing. Keep any demo override outside this repo.
- Dynamic block rendering should escape as late as possible. If markup is assembled in a renderer, make the escaping boundary explicit and keep `render.php` responsible for output safety.
- The editor wrapper should use `useBlockProps()`. Frontend markup should use `get_block_wrapper_attributes()` so WordPress controls block classes and supports.
- Template variables in `render.php` are provided by WordPress block rendering. Keep their `@var` annotations in place so static tooling understands `$attributes`, `$content`, and `$block`.

## Build and i18n gotchas

- `build/` is generated and ignored. Release workflows build it into the zip, but it should not be committed.
- `src/*/block.json` is the source of block metadata. Keep version surfaces in sync with the shared bump workflow.
- Block editor translations need the built script paths. Run the build before generating JSON translation files.
- `languages/source-map.json` is tracked because `wp i18n make-json --use-map` needs a stable map from source handles to built assets. If a block or script handle changes, update the source map generator and regenerate i18n.
- Keep the source map helper local for now. Move it into `wpvdb-scripts` only if another block plugin needs the same behavior.
- `scripts/generate-i18n-source-map.mjs` also removes stale hashed JSON files for this text domain. Do not replace it with broad deletion outside `languages/wpvdb-blocks-*.json`.

## Development notes

- Build, lint, and test commands are defined in `package.json` and `composer.json`; prefer those scripts.
- `stubs/wpvdb-search.stub.php` is for static analysis only. Do not load it at runtime.
- If adding another block, check the source map generator, release the required artifacts, and add a block registration fallback in the same change.
- Unit tests cover block rendering and the i18n source map generator. Extend those tests when render output, search argument mapping, or source map behavior changes.

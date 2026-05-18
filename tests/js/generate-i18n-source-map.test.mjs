import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import {
	mkdirSync,
	mkdtempSync,
	readFileSync,
	rmSync,
	writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { after, test } from 'node:test';

const fixtures = [];
const scriptPath = resolve( 'scripts/generate-i18n-source-map.mjs' );

after( () => {
	for ( const fixture of fixtures ) {
		rmSync( fixture, { recursive: true, force: true } );
	}
} );

test( 'generates source maps for all block script fields', () => {
	const root = createFixture( {
		'src/example/block.json': JSON.stringify( {
			name: 'wpvdb-blocks/example',
			editorScript: 'file:./index.js',
			editorScriptModule: 'file:./editor-module.js',
			script: [ 'wp-element', 'file:./frontend.js' ],
			scriptModule: 'file:./frontend-module.js',
			viewScript: 'file:./view.js',
			viewScriptModule: 'file:./view-module.js',
		} ),
		'languages/wpvdb-blocks-old.json': '{}',
		'languages/other-domain-old.json': '{}',
	} );

	execFileSync( process.execPath, [ scriptPath ], { cwd: root } );

	const map = JSON.parse(
		readFileSync( join( root, 'languages/source-map.json' ), 'utf8' )
	);

	assert.deepEqual(
		map,
		{
			'src/example/editor-module.js': 'build/example/editor-module.js',
			'src/example/frontend-module.js':
				'build/example/frontend-module.js',
			'src/example/frontend.js': 'build/example/frontend.js',
			'src/example/index.js': 'build/example/index.js',
			'src/example/view-module.js': 'build/example/view-module.js',
			'src/example/view.js': 'build/example/view.js',
		},
		'Source map should include every block script field.'
	);
	assert.throws(
		() => readFileSync( join( root, 'languages/wpvdb-blocks-old.json' ) ),
		undefined,
		'Stale domain JSON files should be removed.'
	);
	assert.equal(
		readFileSync( join( root, 'languages/other-domain-old.json' ), 'utf8' ),
		'{}',
		'JSON files from other domains should be preserved.'
	);
} );

function createFixture( files ) {
	const root = mkdtempSync( join( tmpdir(), 'wpvdb-blocks-test-' ) );
	fixtures.push( root );

	for ( const [ relativePath, contents ] of Object.entries( files ) ) {
		const path = join( root, relativePath );
		mkdirSync( dirname( path ), { recursive: true } );
		writeFileSync( path, contents );
	}

	return root;
}

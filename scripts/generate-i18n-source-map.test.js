import { execFileSync } from 'node:child_process';
import {
	mkdirSync,
	mkdtempSync,
	readFileSync,
	rmSync,
	existsSync,
	writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';

const fixtures = [];
const scriptPath = resolve( 'scripts/generate-i18n-source-map.mjs' );

afterAll( () => {
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

	expect( map ).toEqual(
		expect.objectContaining( {
			'src/example/editor-module.js': 'build/example/editor-module.js',
			'src/example/frontend-module.js':
				'build/example/frontend-module.js',
			'src/example/frontend.js': 'build/example/frontend.js',
			'src/example/index.js': 'build/example/index.js',
			'src/example/view-module.js': 'build/example/view-module.js',
			'src/example/view.js': 'build/example/view.js',
		} )
	);
	expect(
		existsSync( join( root, 'languages/wpvdb-blocks-old.json' ) )
	).toBe( false );
	expect(
		readFileSync( join( root, 'languages/other-domain-old.json' ), 'utf8' )
	).toBe( '{}' );
} );

test( 'is deterministic across consecutive runs', () => {
	const root = createFixture( {
		'src/second/block.json': JSON.stringify( {
			name: 'wpvdb-blocks/second',
			editorScript: 'file:./index.js',
		} ),
		'src/first/block.json': JSON.stringify( {
			name: 'wpvdb-blocks/first',
			editorScript: 'file:./index.js',
		} ),
		'languages/wpvdb-blocks-stale.json': '{}',
	} );

	execFileSync( process.execPath, [ scriptPath ], { cwd: root } );
	const firstRun = readFileSync(
		join( root, 'languages/source-map.json' ),
		'utf8'
	);

	writeFileSync( join( root, 'languages/wpvdb-blocks-stale.json' ), '{}' );
	execFileSync( process.execPath, [ scriptPath ], { cwd: root } );
	const secondRun = readFileSync(
		join( root, 'languages/source-map.json' ),
		'utf8'
	);

	expect( secondRun ).toBe( firstRun );
	expect(
		existsSync( join( root, 'languages/wpvdb-blocks-stale.json' ) )
	).toBe( false );
} );

test( 'ignores non file script handles and non JavaScript assets', () => {
	const root = createFixture( {
		'src/example/block.json': JSON.stringify( {
			name: 'wpvdb-blocks/example',
			editorScript: [ 'wp-element', 'file:./index.js' ],
			script: 'file:./frontend.css',
			viewScript: 'https://example.test/view.js',
			viewScriptModule: 'file:./view-module.js',
		} ),
	} );

	execFileSync( process.execPath, [ scriptPath ], { cwd: root } );

	const map = JSON.parse(
		readFileSync( join( root, 'languages/source-map.json' ), 'utf8' )
	);

	expect( map ).toEqual( {
		'src/example/index.js': 'build/example/index.js',
		'src/example/view-module.js': 'build/example/view-module.js',
	} );
} );

test( 'creates languages directory and skips folders without block metadata', () => {
	const root = createFixture( {
		'src/example/block.json': JSON.stringify( {
			name: 'wpvdb-blocks/example',
			editorScript: 'file:./index.js',
		} ),
		'src/ignored/readme.txt': 'No block metadata here.',
	} );

	execFileSync( process.execPath, [ scriptPath ], { cwd: root } );

	const map = JSON.parse(
		readFileSync( join( root, 'languages/source-map.json' ), 'utf8' )
	);

	expect( map ).toEqual(
		expect.objectContaining( {
			'src/example/index.js': 'build/example/index.js',
		} )
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

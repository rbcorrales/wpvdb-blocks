import {
	existsSync,
	mkdirSync,
	readdirSync,
	readFileSync,
	unlinkSync,
	writeFileSync,
} from 'node:fs';
import { dirname, join, posix } from 'node:path';

const sourceRoot = 'src';
const buildRoot = 'build';
const languagesRoot = 'languages';
const outputFile = 'languages/source-map.json';
const jsonPrefix = 'wpvdb-blocks-';
const scriptFields = [
	'editorScript',
	'editorScriptModule',
	'script',
	'scriptModule',
	'viewScript',
	'viewScriptModule',
];

const map = {};

for ( const blockName of readdirSync( sourceRoot ).sort() ) {
	const blockDir = join( sourceRoot, blockName );
	const blockJsonPath = join( blockDir, 'block.json' );

	if ( ! existsSync( blockJsonPath ) ) {
		continue;
	}

	const blockJson = JSON.parse( readFileSync( blockJsonPath, 'utf8' ) );

	for ( const field of scriptFields ) {
		for ( const scriptPath of normalizeScripts( blockJson[ field ] ) ) {
			map[ sourceMapPath( sourceRoot, blockName, scriptPath ) ] =
				sourceMapPath( buildRoot, blockName, scriptPath );
		}
	}
}

mkdirSync( dirname( outputFile ), { recursive: true } );
writeFileSync( outputFile, `${ JSON.stringify( map, null, '\t' ) }\n` );
cleanJsonFiles();

function normalizeScripts( value ) {
	if ( ! value ) {
		return [];
	}

	const values = Array.isArray( value ) ? value : [ value ];

	return values
		.filter(
			( item ) =>
				typeof item === 'string' &&
				item.startsWith( 'file:./' ) &&
				item.endsWith( '.js' )
		)
		.map( ( item ) => item.replace( 'file:./', '' ) );
}

function sourceMapPath( ...parts ) {
	return posix.join(
		...parts.map( ( part ) => part.replaceAll( '\\', '/' ) )
	);
}

function cleanJsonFiles() {
	if ( ! existsSync( languagesRoot ) ) {
		return;
	}

	for ( const fileName of readdirSync( languagesRoot ) ) {
		if (
			fileName.startsWith( jsonPrefix ) &&
			fileName.endsWith( '.json' )
		) {
			unlinkSync( join( languagesRoot, fileName ) );
		}
	}
}

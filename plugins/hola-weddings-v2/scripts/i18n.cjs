#!/usr/bin/env node
/**
 * Translation pipeline for the hola-weddings text domain.
 *
 *   npm run i18n            pot + merge + compile
 *   npm run i18n -- pot     languages/hola-weddings.pot from PHP and the React bundles
 *   npm run i18n -- merge   add new strings from the POT to every languages/*.po
 *   npm run i18n -- compile .mo for PHP, and the booking form's JSON for each locale
 *
 * Why not `wp i18n update-po`: it deletes every entry the POT doesn't contain,
 * and the themes translate their own strings with this plugin's text domain, so
 * their translations live in these .po files without appearing in the plugin's
 * source. `merge` only adds and updates, it never removes.
 *
 * The booking form's translations are written to
 * languages/hola-weddings-{locale}-hw-booking-react.json, the handle-based name
 * wp_set_script_translations() looks for first (see Scripts.php). WP-CLI's
 * make-json names its output after each source file instead, so the pieces are
 * merged into that one file here.
 *
 * Requires `php` on PATH (override with PHP_BIN) and uses the plugin's wp-cli.phar.
 */

const fs            = require( 'fs' );
const os            = require( 'os' );
const path          = require( 'path' );
const { execFileSync } = require( 'child_process' );

const ROOT      = path.resolve( __dirname, '..' );
const LANG_DIR  = path.join( ROOT, 'languages' );
const DOMAIN    = 'hola-weddings';
const POT       = path.join( LANG_DIR, `${ DOMAIN }.pot` );
const JS_HANDLE = 'hw-booking-react';
const JS_SOURCE = /^assets\/(booking-form|shared)\//;
const PHP_BIN   = process.env.PHP_BIN || 'php';

function wp( ...args ) {
	execFileSync( PHP_BIN, [ '-d', 'memory_limit=1024M', path.join( ROOT, 'wp-cli.phar' ), 'i18n', ...args ], {
		cwd:   ROOT,
		stdio: [ 'ignore', 'inherit', 'inherit' ],
	} );
}

function poFiles() {
	return fs.readdirSync( LANG_DIR ).filter( ( f ) => new RegExp( `^${ DOMAIN }-[a-z]{2,3}(_[A-Z]{2})?\\.po$` ).test( f ) );
}

// ── Minimal PO parser / serializer ───────────────────────────────────────────

function unquote( line ) {
	const m = line.match( /"(.*)"\s*$/ );
	return m ? JSON.parse( `"${ m[ 1 ].replace( /\\'/g, "'" ) }"` ) : '';
}

function parse( file ) {
	const text    = fs.readFileSync( file, 'utf8' ).replace( /\r\n/g, '\n' );
	const entries = [];

	for ( const block of text.split( /\n{2,}/ ) ) {
		const e = { comments: [], msgctxt: null, msgid: null, msgid_plural: null, msgstr: [], obsolete: false };
		let cur = null;

		for ( let line of block.split( '\n' ) ) {
			if ( ! line ) continue;
			if ( line.startsWith( '#~' ) ) {
				e.obsolete = true;
				line = line.replace( /^#~\s?/, '' );
			}
			if ( line.startsWith( '#' ) ) { e.comments.push( line ); continue; }

			let m;
			if ( line.startsWith( 'msgctxt ' ) )            { e.msgctxt = unquote( line ); cur = [ 'msgctxt' ]; }
			else if ( line.startsWith( 'msgid_plural ' ) )  { e.msgid_plural = unquote( line ); cur = [ 'msgid_plural' ]; }
			else if ( line.startsWith( 'msgid ' ) )         { e.msgid = unquote( line ); cur = [ 'msgid' ]; }
			else if ( ( m = line.match( /^msgstr\[(\d+)\] / ) ) ) { e.msgstr[ +m[ 1 ] ] = unquote( line ); cur = [ 'msgstr', +m[ 1 ] ]; }
			else if ( line.startsWith( 'msgstr ' ) )        { e.msgstr[ 0 ] = unquote( line ); cur = [ 'msgstr', 0 ]; }
			else if ( line.trim().startsWith( '"' ) && cur ) {
				if ( cur[ 0 ] === 'msgstr' ) e.msgstr[ cur[ 1 ] ] += unquote( line );
				else e[ cur[ 0 ] ] += unquote( line );
			}
		}

		if ( e.msgid !== null ) entries.push( e );
	}

	return entries;
}

const keyOf = ( e ) => ( e.msgctxt !== null ? `${ e.msgctxt }\u0004` : '' ) + e.msgid;

function quote( s ) {
	const q = ( t ) => `"${ JSON.stringify( t ).slice( 1, -1 ) }"`;
	const nl = s.indexOf( '\n' );
	if ( nl === -1 || nl === s.length - 1 ) return q( s );
	return '""\n' + s.split( /(?<=\n)/ ).map( q ).join( '\n' );
}

function serialize( entries ) {
	return entries.map( ( e ) => {
		const out = [ ...e.comments ];
		if ( e.msgctxt !== null ) out.push( `msgctxt ${ quote( e.msgctxt ) }` );
		out.push( `msgid ${ quote( e.msgid ) }` );
		if ( e.msgid_plural !== null ) {
			out.push( `msgid_plural ${ quote( e.msgid_plural ) }` );
			for ( let i = 0; i < Math.max( 2, e.msgstr.length ); i++ ) out.push( `msgstr[${ i }] ${ quote( e.msgstr[ i ] ?? '' ) }` );
		} else {
			out.push( `msgstr ${ quote( e.msgstr[ 0 ] ?? '' ) }` );
		}
		return e.obsolete ? out.map( ( l ) => ( l.startsWith( '#' ) ? l : `#~ ${ l }` ) ).join( '\n' ) : out.join( '\n' );
	} ).join( '\n\n' ) + '\n';
}

// ── Steps ────────────────────────────────────────────────────────────────────

function pot() {
	wp( 'make-pot', '.', POT,
		`--domain=${ DOMAIN }`,
		'--exclude=node_modules,vendor,assets/dist,assets/vendor,assets/js,logs,languages,scripts',
		'--skip-audit',
	);
}

/**
 * Brings each .po in line with the POT: source comments and references come
 * from the POT, translations and translator state (fuzzy flags, previous-msgid
 * lines, translator comments) are kept, new strings are added untranslated, and
 * entries the POT lacks (theme strings) are kept as they are, after the others.
 * An obsolete (#~) entry whose string is back in the source is revived.
 */
function merge() {
	const potEntries = parse( POT ).filter( ( e ) => e.msgid !== '' );

	for ( const file of poFiles() ) {
		const full     = path.join( LANG_DIR, file );
		const existing = parse( full );
		const header   = existing.find( ( e ) => e.msgid === '' );
		const byKey    = new Map();
		for ( const e of existing ) {
			if ( e.msgid === '' ) continue;
			const k = keyOf( e );
			// An active entry wins over an obsolete one with the same string.
			if ( ! byKey.has( k ) || byKey.get( k ).obsolete ) byKey.set( k, e );
		}
		const inPot = new Set();
		let added   = 0;

		const merged = potEntries.map( ( p ) => {
			const k   = keyOf( p );
			const old = byKey.get( k );
			inPot.add( k );
			if ( ! old ) added++;

			const oldComments = old?.comments ?? [];
			const translator  = oldComments.filter( ( c ) => /^# /.test( c ) || c === '#' || c.startsWith( '#|' ) );
			const flags       = new Set( [ ...oldComments, ...p.comments ]
				.filter( ( c ) => c.startsWith( '#,' ) )
				.flatMap( ( c ) => c.slice( 2 ).split( ',' ).map( ( f ) => f.trim() ) )
				.filter( Boolean ) );
			const rest        = p.comments.filter( ( c ) => ! c.startsWith( '#,' ) );

			return {
				...p,
				comments: [ ...translator, ...rest, ...( flags.size ? [ `#, ${ [ ...flags ].join( ', ' ) }` ] : [] ) ],
				msgstr:   old ? old.msgstr : [],
				obsolete: false,
			};
		} );

		const kept = existing.filter( ( e ) => e.msgid !== '' && ! inPot.has( keyOf( e ) ) );

		fs.writeFileSync( full, serialize( [ header, ...merged, ...kept ].filter( Boolean ) ) );
		console.log( `${ file }: ${ added } added, ${ kept.length } kept that the POT doesn't have` );
	}
}

const isFuzzy = ( e ) => e.comments.some( ( c ) => /^#,.*\bfuzzy\b/.test( c ) );

/**
 * .mo files for PHP and the booking form's JSON, per locale.
 *
 * Fuzzy translations are left out of both: they are Poedit's guesses from
 * similar strings, often wrong (fr_CA has "Payment Information" guessed as the
 * guests heading), and WP-CLI's make-mo would compile them like any other.
 */
function compile() {
	const tmp = fs.mkdtempSync( path.join( os.tmpdir(), 'hw-i18n-' ) );

	try {
		for ( const file of poFiles() ) {
			const entries = parse( path.join( LANG_DIR, file ) ).map( ( e ) => ( isFuzzy( e ) ? { ...e, msgstr: [] } : e ) );
			fs.writeFileSync( path.join( tmp, file ), serialize( entries ) );
			wp( 'make-mo', path.join( tmp, file ), LANG_DIR );
			writeScriptJson( file.match( /-([^-]+)\.po$/ )[ 1 ], entries );
		}
	} finally {
		fs.rmSync( tmp, { recursive: true, force: true } );
	}
}

/**
 * Jed 1.x JSON, as WP-CLI's make-json writes it, with the translated strings
 * referenced from the booking form's sources. Written here rather than with
 * make-json because that names its output after each source file and, in the
 * WP-CLI bundled with the plugin, only reads .js sources, not .jsx.
 */
function writeScriptJson( locale, entries ) {
	const header   = entries.find( ( e ) => e.msgid === '' );
	const plural   = ( header?.msgstr[ 0 ] ?? '' ).match( /^Plural-Forms:\s*(.+)$/m )?.[ 1 ] ?? 'nplurals=2; plural=(n != 1);';
	const messages = { '': { domain: 'messages', lang: locale, 'plural-forms': plural } };

	for ( const e of entries ) {
		if ( ! e.msgid || e.obsolete || isFuzzy( e ) ) continue;
		if ( ! e.comments.some( ( c ) => c.startsWith( '#: ' ) && c.slice( 3 ).split( ' ' ).some( ( ref ) => JS_SOURCE.test( ref ) ) ) ) continue;
		const forms = e.msgid_plural !== null ? e.msgstr : [ e.msgstr[ 0 ] ?? '' ];
		if ( ! forms.length || forms.some( ( f ) => ! f ) ) continue;
		messages[ ( e.msgctxt !== null ? `${ e.msgctxt }\u0004` : '' ) + e.msgid ] = forms;
	}

	const dest  = path.join( LANG_DIR, `${ DOMAIN }-${ locale }-${ JS_HANDLE }.json` );
	const count = Object.keys( messages ).length - 1;

	if ( ! count ) {
		fs.rmSync( dest, { force: true } );
		return;
	}

	fs.writeFileSync( dest, JSON.stringify( {
		'translation-revision-date': new Date().toISOString(),
		generator:   'hola-weddings scripts/i18n.cjs',
		domain:      'messages',
		locale_data: { messages },
	} ) );
	console.log( `${ path.basename( dest ) }: ${ count } strings` );
}

// ── CLI ──────────────────────────────────────────────────────────────────────

const steps = { pot, merge, compile };
const asked = process.argv.slice( 2 );

for ( const name of asked.length ? asked : [ 'pot', 'merge', 'compile' ] ) {
	if ( ! steps[ name ] ) {
		console.error( `Unknown step "${ name }". Use: pot, merge, compile.` );
		process.exit( 1 );
	}
	steps[ name ]();
}

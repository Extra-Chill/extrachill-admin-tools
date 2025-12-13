( function () {
	'use strict';

	function $( id ) {
		return document.getElementById( id );
	}

	function setStatusHtml( html ) {
		const el = $( 'ec-link-page-font-migration-status' );
		if ( el ) {
			el.innerHTML = html;
		}
	}

	async function runBatch( offset, perPage ) {
		const cfg = window.ecLinkPageFontMigration || {};

		const res = await fetch( cfg.restUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce,
			},
			body: JSON.stringify( {
				offset,
				per_page: perPage,
			} ),
		} );

		if ( ! res.ok ) {
			const text = await res.text();
			throw new Error( text || 'Request failed' );
		}

		return res.json();
	}

	async function runMigration() {
		const button = $( 'ec-link-page-font-migration-run' );
		if ( ! button ) {
			return;
		}

		if ( ! window.confirm( 'Run link page font migration on this site?' ) ) {
			return;
		}

		button.disabled = true;
		setStatusHtml( '<p>Running…</p>' );

		let offset = 0;
		const perPage = 50;

		let totalScanned = 0;
		let totalUpdatedPosts = 0;
		let totalUpdatedFields = 0;

		try {
			while ( true ) {
				const result = await runBatch( offset, perPage );
				totalScanned += result.scanned || 0;
				totalUpdatedPosts += result.updated_posts || 0;
				totalUpdatedFields += result.updated_fields || 0;

				setStatusHtml(
					'<p><strong>Progress</strong></p>' +
					'<ul>' +
						'<li>Scanned: ' + totalScanned + '</li>' +
						'<li>Updated posts: ' + totalUpdatedPosts + '</li>' +
						'<li>Updated fields: ' + totalUpdatedFields + '</li>' +
					'</ul>'
				);

				offset = result.next_offset || offset + ( result.scanned || 0 );

				if ( result.done ) {
					break;
				}
			}

			setStatusHtml(
				'<div class="notice notice-success">' +
					'<p><strong>Done.</strong></p>' +
					'<ul>' +
						'<li>Total scanned: ' + totalScanned + '</li>' +
						'<li>Total updated posts: ' + totalUpdatedPosts + '</li>' +
						'<li>Total updated fields: ' + totalUpdatedFields + '</li>' +
					'</ul>' +
				'</div>'
			);
		} catch ( err ) {
			setStatusHtml(
				'<div class="notice notice-error"><p><strong>Error:</strong> ' +
					( err && err.message ? err.message : 'Unknown error' ) +
					'</p></div>'
			);
		} finally {
			button.disabled = false;
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		const button = $( 'ec-link-page-font-migration-run' );
		if ( ! button ) {
			return;
		}

		button.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			runMigration();
		} );
	} );
} )();

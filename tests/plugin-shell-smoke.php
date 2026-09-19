<?php
/**
 * Standalone smoke tests for the retirement shell.
 *
 * The plugin is a deprecated shell: no runtime behavior is asserted. These
 * checks protect the file invariants the platform depends on — the ABSPATH
 * guard, the plugin header, and agreement between the header Version and the
 * EXTRACHILL_ADMIN_TOOLS_VERSION constant that homeboy version_targets match.
 *
 * @package ExtraChillAdminTools
 */

// phpcs:disable -- Standalone test harness writes CLI output directly and never loads WordPress.

$plugin_file = dirname( __DIR__ ) . '/extrachill-admin-tools.php';
$source      = file_get_contents( $plugin_file );

function admin_tools_smoke_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
	echo "PASS: {$message}\n";
}

admin_tools_smoke_assert(
	false !== strpos( $source, "if ( ! defined( 'ABSPATH' ) )" ),
	'plugin file keeps the direct-access ABSPATH guard'
);

admin_tools_smoke_assert(
	1 === preg_match( '/^ \* Plugin Name: .+$/m', $source ),
	'plugin header declares Plugin Name'
);

admin_tools_smoke_assert(
	1 === preg_match( '/^ \* Version: ([0-9.]+)$/m', $source, $header_version ),
	'plugin header declares a Version'
);

admin_tools_smoke_assert(
	1 === preg_match( "/define\\( 'EXTRACHILL_ADMIN_TOOLS_VERSION', '([0-9.]+)' \\);/", $source, $constant_version ),
	'EXTRACHILL_ADMIN_TOOLS_VERSION constant is defined'
);

admin_tools_smoke_assert(
	$header_version[1] === $constant_version[1],
	"header Version ({$header_version[1]}) matches EXTRACHILL_ADMIN_TOOLS_VERSION ({$constant_version[1]})"
);

admin_tools_smoke_assert(
	false !== strpos( $source, 'Text Domain: extrachill-admin-tools' ),
	'plugin header keeps the canonical text domain'
);

echo "All plugin-shell smoke tests passed.\n";
exit( 0 );

<?php
/**
 * Plugin Name:         WP Composer Auto Updates
 * Description:         Enables maintenance and security updates for WordPress when VCS and DISALLOW_FILE_MODS are used.
 * Version:             0.1.1
 * Requires at least:   5.5
 * Author:              Benoît Chantre
 * Author URI:          https://benoitchantre.com
 * License:             GPL-3.0+
 * License URI:         http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package             WP_Composer_Auto_Updates
 */

namespace BenoitChantre\WP_Composer_Auto_Updates;

use function wp_get_environment_type;
use function add_filter;

/**
 * Keep automatic updates disabled for local and development environments.
 */
if ( in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
	return;
}

/**
 * Allows file changes for automatic updater when DISALLOW_FILE_MODS is enabled.
 *
 * @param bool   $file_mod_allowed Whether file modifications are allowed.
 * @param string $context          The usage context.
 * @return bool
 */
function allow_file_mod_for_automatic_updater( bool $file_mod_allowed, string $context ) : bool {
	if ( 'automatic_updater' === $context ) {
		return true;
	}

	return $file_mod_allowed;
}

/**
 * Pretends there's no VCS to allow automatic updates.
 */
add_filter( 'automatic_updates_is_vcs_checkout', '__return_false' );

/**
 * Allows file changes by the automatic updater when DISALLOW_FILE_MODS is enabled.
 * Disables auto updates except for minor core updates.
 */
if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
	add_filter( 'file_mod_allowed', __NAMESPACE__ . '\allow_file_mod_for_automatic_updater', 10, 2 );
	add_filter( 'allow_minor_auto_core_updates', '__return_true' );
	add_filter( 'allow_major_auto_core_updates', '__return_false' );
	add_filter( 'themes_auto_update_enabled', '__return_false' );
	add_filter( 'plugins_auto_update_enabled', '__return_false' );
}

<?php
/**
 * Plugin Name:         WP Composer Auto Updates
 * Description:         Enables maintenance and security updates for WordPress when VCS and DISALLOW_FILE_MODS are used.
 * Version:             1.0.0
 * Requires at least:   5.5
 * Author:              Benoît Chantre
 * Author URI:          https://benoitchantre.com
 * License:             GPL-3.0+
 * License URI:         http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package             WP_Composer_Auto_Updates
 */

namespace BenoitChantre\WP_Composer_Auto_Updates;

use function add_action;
use function add_filter;
use function add_query_arg;
use function delete_transient;
use function get_transient;
use function home_url;
use function is_wp_error;
use function sanitize_text_field;
use function set_transient;
use function status_header;
use function wp_generate_password;
use function wp_get_environment_type;
use function wp_remote_post;
use function wp_unslash;

/**
 * Value of the `action` query argument identifying an opcode cache reset request.
 */
const OPCACHE_RESET_ACTION = 'wp_composer_auto_updates_opcache_reset';

/**
 * Name of the transient holding the secret authenticating a reset request.
 */
const OPCACHE_RESET_SECRET_KEY = 'wp_composer_auto_updates_opcache_secret';

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
function allow_file_mod_for_automatic_updater( bool $file_mod_allowed, string $context ): bool {
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

/**
 * Clears the opcode cache of the web server once core has been updated.
 *
 * Core invalidates the opcode cache of every file it copies, but automatic
 * updates can run through WP-CLI from a real cron job. `opcache_invalidate()`
 * only affects the cache of the process calling it, so the invalidation happens
 * in the short-lived CLI process (where OPcache is usually disabled altogether)
 * and never reaches the PHP-FPM workers serving the site.
 *
 * As `opcache.validate_timestamps` is disabled in production, those workers
 * keep serving the previous version of core until they are restarted.
 *
 * The only way to reach their cache is to run `opcache_reset()` inside a web
 * request, so a request is sent back to the site, authenticated with a
 * single-use secret.
 */
add_action( 'automatic_updates_complete', __NAMESPACE__ . '\reset_opcache_after_core_update' );
add_action( 'muplugins_loaded', __NAMESPACE__ . '\handle_opcache_reset_request', 1 );

/**
 * Resets the opcode cache of the web server after a core update.
 *
 * @param array $results The results of all attempted updates.
 * @return void
 */
function reset_opcache_after_core_update( array $results ): void {
	if ( empty( $results['core'] ) ) {
		return;
	}

	$updated = false;

	foreach ( $results['core'] as $result ) {
		if ( ! empty( $result->result ) && ! is_wp_error( $result->result ) ) {
			$updated = true;
			break;
		}
	}

	if ( ! $updated ) {
		return;
	}

	$secret = wp_generate_password( 32, false );

	/*
	 * The secret is short-lived: it only needs to survive the request sent
	 * below, and it is deleted as soon as it has been used.
	 */
	set_transient( OPCACHE_RESET_SECRET_KEY, $secret, MINUTE_IN_SECONDS );

	wp_remote_post(
		add_query_arg(
			array(
				'action' => OPCACHE_RESET_ACTION,
				'secret' => $secret,
			),
			home_url( '/' )
		),
		array(
			'timeout' => 10,
		)
	);
}

/**
 * Handles the opcode cache reset request sent after a core update.
 *
 * Runs as early as possible so the reset is not performed by a process that has
 * already loaded a large part of the outdated core.
 *
 * @return void
 */
function handle_opcache_reset_request(): void {
	/*
	 * A nonce is not used here: the request is sent by WP-CLI, without a user,
	 * and a nonce generated for the logged out user could be recreated by
	 * anyone. The single-use secret below is checked instead.
	 */
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['action'], $_GET['secret'] ) || OPCACHE_RESET_ACTION !== $_GET['action'] ) {
		return;
	}

	$secret = get_transient( OPCACHE_RESET_SECRET_KEY );

	/*
	 * The secret is single-use: delete it before resetting the cache so a
	 * failed or repeated request cannot replay it.
	 */
	delete_transient( OPCACHE_RESET_SECRET_KEY );

	$provided = sanitize_text_field( wp_unslash( $_GET['secret'] ) );
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( ! $secret || ! hash_equals( $secret, $provided ) ) {
		status_header( 403 );
		die();
	}

	if ( function_exists( 'opcache_reset' ) && opcache_reset() ) {
		status_header( 202 );
	} else {
		status_header( 500 );
	}

	die();
}

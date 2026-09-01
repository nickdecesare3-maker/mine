<?php
/**
 * Uninstall handler.
 *
 * Runs only when the plugin is deleted from the Plugins screen (never on
 * plain deactivation). Intentionally leaves all Office Location posts and
 * meta in place -- deleting a site owner's content on uninstall would be a
 * destructive surprise. Only plugin-internal options/user meta are cleaned
 * up.
 *
 * @package OfficeLocationMap
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'olm_show_getting_started' );
delete_option( 'olm_display_settings' );

$users_with_flag = get_users(
	array(
		'meta_key' => 'olm_dismissed_getting_started', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'fields'   => 'ID',
	)
);

foreach ( $users_with_flag as $user_id ) {
	delete_user_meta( $user_id, 'olm_dismissed_getting_started' );
}

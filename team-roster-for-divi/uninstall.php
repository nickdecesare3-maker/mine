<?php
/**
 * Uninstall handler.
 *
 * Runs only when the plugin is deleted from the Plugins screen (never on
 * plain deactivation). Intentionally leaves all Team Member posts, meta
 * and Group terms in place -- deleting a site owner's content on uninstall
 * would be a destructive surprise. Only plugin-internal options/user meta
 * are cleaned up.
 *
 * @package TeamRosterForDivi
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'trd_show_getting_started' );

$users_with_flag = get_users(
	array(
		'meta_key' => 'trd_dismissed_getting_started', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'fields'   => 'ID',
	)
);

foreach ( $users_with_flag as $user_id ) {
	delete_user_meta( $user_id, 'trd_dismissed_getting_started' );
}

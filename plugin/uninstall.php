<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

global $wpdb;
delete_option( 'obrisowi_general_settings' );
delete_option( 'obrisowi_messengers' );
delete_option( 'obrisowi_db_version' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}obrisowi_stats" );

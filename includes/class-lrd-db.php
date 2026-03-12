<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LRD_DB {
    public static function install() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql = array();

        // Prayer Requests
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lrd_prayer (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
            guest_name  VARCHAR(100) DEFAULT '',
            guest_email VARCHAR(150) DEFAULT '',
            title       VARCHAR(255) NOT NULL,
            message     LONGTEXT NOT NULL,
            status      VARCHAR(20) NOT NULL DEFAULT 'publish',
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset;";

        // God Did It
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lrd_godit (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
            guest_name  VARCHAR(100) DEFAULT '',
            guest_email VARCHAR(150) DEFAULT '',
            title       VARCHAR(255) NOT NULL,
            message     LONGTEXT NOT NULL,
            status      VARCHAR(20) NOT NULL DEFAULT 'publish',
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset;";

        // Comments (shared for both types)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lrd_comments (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_type   VARCHAR(20) NOT NULL,
            post_id     BIGINT UNSIGNED NOT NULL,
            parent_id   BIGINT UNSIGNED NOT NULL DEFAULT 0,
            user_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
            guest_name  VARCHAR(100) DEFAULT '',
            guest_email VARCHAR(150) DEFAULT '',
            message     LONGTEXT NOT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_idx (post_type, post_id),
            KEY parent_id (parent_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ( $sql as $query ) {
            dbDelta( $query );
        }
    }
}

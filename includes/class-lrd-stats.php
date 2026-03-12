<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LRD_Stats {
    public function __construct() {
        add_shortcode( 'lrd_stats_bar', array( $this, 'render' ) );
        add_action( 'wp_login', array( $this, 'update_user_last_active' ), 10, 2 );
        add_action( 'wp', array( $this, 'track_user_activity' ) );
    }

    public function track_user_activity() {
        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            update_user_meta( $user_id, 'lrd_last_active', current_time( 'timestamp' ) );
        }
    }

    public function update_user_last_active( $user_login, $user ) {
        update_user_meta( $user->ID, 'lrd_last_active', current_time( 'timestamp' ) );
    }

    public function render() {
        $stats = $this->get_stats();
        ob_start();
        ?>
        <div class="lrd-stats-bar">
            <div class="lrd-stat-item">
                <span class="lrd-stat-num" id="lrd-stat-members"><?php echo number_format($stats['members']); ?></span>
                <span class="lrd-stat-label">Members</span>
            </div>
            <div class="lrd-stat-item">
                <span class="lrd-stat-num" id="lrd-stat-online"><?php echo number_format($stats['online']); ?></span>
                <span class="lrd-stat-label">Online</span>
            </div>
            <div class="lrd-stat-item">
                <span class="lrd-stat-num" id="lrd-stat-prayer"><?php echo number_format($stats['prayer']); ?></span>
                <span class="lrd-stat-label">Prayer Requests</span>
            </div>
            <div class="lrd-stat-item">
                <span class="lrd-stat-num" id="lrd-stat-events"><?php echo number_format($stats['events']); ?></span>
                <span class="lrd-stat-label">Upcoming Events</span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function get_stats() {
        $stats = get_transient( 'lrd_dashboard_stats' );
        if ( false === $stats ) {
            $stats = array(
                'members' => $this->count_members(),
                'online'  => $this->count_online(),
                'prayer'  => $this->count_prayer(),
                'events'  => $this->count_events(),
            );
            set_transient( 'lrd_dashboard_stats', $stats, 5 * MINUTE_IN_SECONDS );
        }
        return $stats;
    }

    private function count_members() {
        $user_query = new WP_User_Query( array(
            'role__in' => array( 'administrator', 'customer', 'subscriber' ),
            'count_total' => true,
        ) );
        return $user_query->get_total();
    }

    private function count_online() {
        global $wpdb;
        $threshold = current_time( 'timestamp' ) - ( 5 * MINUTE_IN_SECONDS );
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM $wpdb->usermeta WHERE meta_key = 'lrd_last_active' AND meta_value > %d",
            $threshold
        ) );
        return intval( $count );
    }

    private function count_prayer() {
        global $wpdb;
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lrd_prayer WHERE status = 'publish'" );
        return intval( $count );
    }

    private function count_events() {
        $now = current_time( 'timestamp' );
        $args = array(
            'post_type'      => 'ajde_events',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => 'evcal_srow',
                    'value'   => $now,
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                ),
            ),
            'fields' => 'ids',
        );
        $query = new WP_Query( $args );
        return $query->found_posts;
    }
}

new LRD_Stats();

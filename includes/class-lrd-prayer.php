<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LRD_Prayer {
    public function __construct() {
        add_shortcode( 'lrd_prayer_request', array( $this, 'render' ) );
    }

    public function render( $atts ) {
        $atts = shortcode_atts( array( 'per_page' => 5 ), $atts );
        ob_start();
        ?>
        <div class="lrd-widget lrd-prayer-widget" data-type="prayer" data-per-page="<?php echo intval($atts['per_page']); ?>">
            <div class="lrd-widget-header">
                <div class="lrd-title-wrap">
                    <h3 class="lrd-title">How Can We Pray for You?</h3>
                    <span class="lrd-info-icon" tabindex="0">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        <div class="lrd-info-popup">
                            <strong>What's Inside This Area</strong>
                            <p>Share your prayer request below and let the family lift you up.</p>
                            <p>You are not alone - we will pray with you.</p>
                        </div>
                    </span>
                </div>
                <button class="lrd-add-btn lrd-green" data-type="prayer">ADD +</button>
            </div>
            <div class="lrd-list" id="lrd-prayer-list">
                <?php echo self::get_items_html( 'prayer', 0, intval($atts['per_page']) ); ?>
            </div>
            <div class="lrd-pagination">
                <a href="#" class="lrd-explore-more" data-type="prayer" data-offset="<?php echo intval($atts['per_page']); ?>" data-per-page="<?php echo intval($atts['per_page']); ?>">Explore More &rsaquo;</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function get_items_html( $type, $offset = 0, $per_page = 5 ) {
        global $wpdb;
        $table = $type === 'prayer' ? $wpdb->prefix . 'lrd_prayer' : $wpdb->prefix . 'lrd_godit';
        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table WHERE status='publish' ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page, $offset
        ));
        if ( empty($items) ) {
            return '<p class="lrd-empty">No entries yet. Be the first!</p>';
        }
        $html = '';
        foreach ( $items as $item ) {
            $name   = $item->user_id ? get_userdata($item->user_id)->display_name : esc_html($item->guest_name);
            $avatar = $item->user_id ? get_avatar($item->user_id, 40) : '<div class="lrd-avatar-placeholder">' . strtoupper(substr($name,0,1)) . '</div>';
            $short  = wp_trim_words( $item->message, 12, '...' );
            $html  .= sprintf(
                '<div class="lrd-item" data-id="%d" data-type="%s">
                    <div class="lrd-item-avatar">%s</div>
                    <div class="lrd-item-body">
                        <div class="lrd-item-title">%s - %s</div>
                        <div class="lrd-item-preview">%s</div>
                    </div>
                </div>',
                $item->id,
                esc_attr($type),
                $avatar,
                esc_html($name),
                esc_html($item->title),
                esc_html($short)
            );
        }
        return $html;
    }
}

new LRD_Prayer();

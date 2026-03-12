<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LRD_Godit {
    public function __construct() {
        add_shortcode( 'lrd_god_did_it', array( $this, 'render' ) );
    }

    public function render( $atts ) {
        $atts = shortcode_atts( array( 'per_page' => 5 ), $atts );
        ob_start();
        ?>
        <div class="lrd-widget lrd-godit-widget" data-type="godit" data-per-page="<?php echo intval($atts['per_page']); ?>">
            <div class="lrd-widget-header">
                <div class="lrd-title-wrap">
                    <h3 class="lrd-title">God Did It!</h3>
                    <span class="lrd-info-icon" tabindex="0">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        <div class="lrd-info-popup">
                            <strong>What's Inside This Area</strong>
                            <p>Share your testimony and let the family celebrate with you.</p>
                            <p>God is good — let the world know what He has done!</p>
                        </div>
                    </span>
                </div>
                <button class="lrd-add-btn lrd-purple" data-type="godit">ADD +</button>
            </div>
            <div class="lrd-list" id="lrd-godit-list">
                <?php echo LRD_Prayer::get_items_html( 'godit', 0, intval($atts['per_page']) ); ?>
            </div>
            <div class="lrd-pagination">
                <a href="#" class="lrd-explore-more" data-type="godit" data-offset="<?php echo intval($atts['per_page']); ?>" data-per-page="<?php echo intval($atts['per_page']); ?>">Explore More &rsaquo;</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new LRD_Godit();

<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LRD_Events {
    public function __construct() {
        add_shortcode( 'lrd_events',      array( $this, 'render' ) );
        add_shortcode( 'lrd_events_list', array( $this, 'render_list' ) );
    }

    public function render_list( $atts ) {
        $atts = shortcode_atts( array(
            'count'    => 3,
            'view_more_url' => 'https://www.larryragland.com/upcoming-events/',
        ), $atts );

        $events = $this->get_events( intval($atts['count']) );

        if ( empty($events) ) {
            return '<p class="lrd-empty">No upcoming events found.</p>';
        }

        return $this->render_board( $events, $atts['view_more_url'] );
    }

    public function render( $atts ) {
        $atts = shortcode_atts( array(
            'count'    => 6,
            'view_more_url' => 'https://www.larryragland.com/upcoming-events/',
        ), $atts );

        $events = $this->get_events( intval($atts['count']) + 1 );

        if ( empty($events) ) {
            return '<p class="lrd-empty">No upcoming events found.</p>';
        }

        $next  = array_shift( $events );
        $rest  = array_slice( $events, 0, intval($atts['count']) );

        ob_start();
        ?>
        <div class="lrd-widget lrd-events-widget">

            <?php if ( $next ) : 
                $next_ts    = $this->get_event_timestamp( $next );
                $next_title = get_the_title( $next->ID );
                $next_date  = $this->format_event_date( $next );
                $next_time  = $this->format_event_time( $next );
                $next_loc   = get_post_meta( $next->ID, '_evcal_location_name', true ) ?: get_post_meta( $next->ID, 'evcal_location', true );
                $next_img   = get_the_post_thumbnail_url( $next->ID, 'medium' );
            ?>
            <div class="lrd-next-event" data-timestamp="<?php echo esc_attr($next_ts); ?>">
                <?php if ( $next_img ) : ?>
                    <div class="lrd-next-event-bg" style="background-image:url('<?php echo esc_url($next_img); ?>')"></div>
                <?php endif; ?>
                <div class="lrd-next-event-overlay">
                    <div class="lrd-next-event-date"><?php echo esc_html($next_date); ?> &nbsp; <?php echo esc_html($next_time); ?></div>
                    <div class="lrd-next-event-title"><?php echo esc_html($next_title); ?></div>
                    <?php if ( $next_loc ) : ?>
                        <div class="lrd-next-event-loc">📍 <?php echo esc_html($next_loc); ?></div>
                    <?php endif; ?>
                    <div class="lrd-countdown" data-timestamp="<?php echo esc_attr($next_ts); ?>">
                        <div class="lrd-countdown-unit"><span class="lrd-cd-days">--</span><small>Days</small></div>
                        <div class="lrd-countdown-unit"><span class="lrd-cd-hours">--</span><small>Hours</small></div>
                        <div class="lrd-countdown-unit"><span class="lrd-cd-mins">--</span><small>Minutes</small></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php echo $this->render_board( $rest, $atts['view_more_url'] ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_events( $count ) {
        $now = current_time('timestamp');
        $args = array(
            'post_type'      => 'ajde_events',
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'meta_key'       => 'evcal_srow',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => 'evcal_srow',
                    'value'   => $now,
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                ),
            ),
        );
        return get_posts( $args );
    }

    private function get_event_timestamp( $event ) {
        $ts = get_post_meta( $event->ID, 'evcal_srow', true );
        return $ts ? intval($ts) : 0;
    }

    private function format_event_date( $event ) {
        $ts = $this->get_event_timestamp( $event );
        return $ts ? date_i18n( 'm/d/Y', $ts ) : '';
    }
    
    private function format_event_date_list( $event ) {
        $ts = $this->get_event_timestamp( $event );
        if ( ! $ts ) return '';
        
        $month = date_i18n( 'F', $ts );
        $day = (int)date_i18n( 'j', $ts );
        
        // Sufijos ordinales (st, nd, rd, th)
        $suffix = 'th';
        if ( $day % 10 == 1 && $day != 11 ) $suffix = 'st';
        elseif ( $day % 10 == 2 && $day != 12 ) $suffix = 'nd';
        elseif ( $day % 10 == 3 && $day != 13 ) $suffix = 'rd';
        
        return $month . ' ' . $day . $suffix;
    }

    private function format_event_time( $event ) {
        $ts = $this->get_event_timestamp( $event );
        return $ts ? date_i18n( 'g A', $ts ) : '';
    }

    private function format_event_day( $event ) {
        $ts = $this->get_event_timestamp( $event );
        if ( ! $ts ) return '';
        // Check for recurring pattern stored in meta
        $repeat = get_post_meta( $event->ID, 'evcal_repeat', true );
        if ( $repeat ) return $repeat;
        return date_i18n( 'l', $ts );
    }

    private function get_event_subtitle_text( $event ) {
        $ts = $this->get_event_timestamp( $event );
        if ( ! $ts ) return '';

        $repeat = get_post_meta( $event->ID, 'evcal_repeat', true );
        if ( $repeat && ! in_array( strtolower($repeat), array('yes', 'no') ) ) {
            return $repeat;
        }

        $now = current_time('timestamp');
        $seven_days_later = $now + ( 7 * DAY_IN_SECONDS );

        if ( $ts >= $now && $ts <= $seven_days_later ) {
            return date_i18n( 'l', $ts );
        }

        return $this->format_event_date_list( $event );
    }

    private function render_board( $events, $view_more_url ) {
        ob_start();
        ?>
        <div class="lrd-events-board">
            <div class="lrd-events-board-header">
                <h3 class="lrd-title" style="display: flex;gap: .35rem;fill: #000;">The Church Board
                    <span class="lrd-info-icon" tabindex="0">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        <div class="lrd-info-popup">
                            <strong>What's Inside This Area</strong>
                            <p>Announcements, prayer requests, opportunities, events, livestreams, training, watch parties, and global meetups — the classic church board, now interactive and worldwide.</p>
                        </div>
                    </span>
                </h3>
                <p class="lrd-board-desc">Announcements, prayer requests, opportunities, events, livestreams, training, watch parties, and global meetups — the classic church board, now interactive and worldwide.</p>
            </div>

            <div class="lrd-events-list">
                <?php foreach ( $events as $event ) : ?>
                    <?php echo $this->render_event_row( $event ); ?>
                <?php endforeach; ?>
            </div>

            <div class="lrd-pagination">
                <a href="<?php echo esc_url($view_more_url); ?>" class="lrd-explore-more" target="_blank">View More &gt;</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_event_row( $event ) {
        $title     = get_the_title( $event->ID );
        $event_url = get_permalink( $event->ID );
        $time_str  = $this->format_event_time( $event );
        $date_str  = $this->get_event_subtitle_text( $event );
        $location  = get_post_meta( $event->ID, '_evcal_location_name', true ) ?: get_post_meta( $event->ID, 'evcal_location', true ) ?: get_post_meta( $event->ID, 'evcal_subtitle', true );
        $thumb     = get_the_post_thumbnail_url( $event->ID, 'thumbnail' );

        ob_start();
        ?>
        <div class="lrd-event-row">
            <div class="lrd-event-icon">
                <?php if ( $thumb ) : ?>
                    <a href="<?php echo esc_url($event_url); ?>" target="_blank" rel="noopener noreferrer">
                        <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>">
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url($event_url); ?>" target="_blank" rel="noopener noreferrer">
                        <div class="lrd-event-icon-placeholder"><?php echo esc_html(strtoupper(substr($title,0,1))); ?></div>
                    </a>
                <?php endif; ?>
            </div>
            <div class="lrd-event-info">
                <div class="lrd-event-name">
                    <a href="<?php echo esc_url($event_url); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo esc_html($title); ?>
                    </a>
                </div>
                <?php if ( $location ) : ?>
                    <div class="lrd-event-loc"><?php echo esc_html($location); ?></div>
                <?php endif; ?>
            </div>
            <div class="lrd-event-time">
                <div class="lrd-event-time-val"><?php echo esc_html($time_str); ?></div>
                <div class="lrd-event-date"><?php echo esc_html($date_str); ?></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new LRD_Events();

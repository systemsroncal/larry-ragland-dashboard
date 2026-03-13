<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LRD_Growth_Track {
    public function __construct() {
        add_shortcode( 'lrd_growth_track', array( $this, 'render_shortcode' ) );
    }

    public function render_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p class="lrd-login-required">Please log in to view the Growth Track.</p>';
        }

        $user_id = get_current_user_id();
        $watched_videos = get_user_meta( $user_id, 'lrd_watched_videos', true );
        if ( ! is_array( $watched_videos ) ) {
            $watched_videos = array();
        }

        $video_progress = get_user_meta( $user_id, 'lrd_video_progress', true );
        if ( ! is_array( $video_progress ) ) {
            $video_progress = array();
        }

        $videos = $this->get_videos();

        if ( empty( $videos ) ) {
            return '<p class="lrd-empty">No growth track videos available.</p>';
        }

        // Check if all videos are watched
        $watched_count = 0;
        foreach ( $videos as $v ) {
            if ( in_array( $v->ID, $watched_videos ) ) {
                $watched_count++;
            }
        }
        $total_videos = count($videos);
        $progress_percent = $total_videos > 0 ? ($watched_count / $total_videos) * 100 : 0;

        ob_start();
        ?>
        <div class="lrd-growth-track-container">
            <div class="lrd-gt-header">
                <p><strong>Start by watching the videos below</strong> — they introduce the vision and pillars of this global church.</p>
                <p>Each completed video moves your progress bar forward and unlocks the next step in your Growth Track journey. You’re not just watching — you’re joining a family that spans the world. 🌎</p>

                <div class="lrd-gt-progress-bar-container">
                    <div class="lrd-gt-progress-bar-fill" style="width: <?php echo esc_attr($progress_percent); ?>%;"></div>
                </div>

                <h2 class="lrd-gt-main-title">Growth Track</h2>
            </div>

            <div class="lrd-growth-track-grid">
                <?php
                $can_watch_next = true;
                foreach ( $videos as $index => $post ) :
                    $is_watched = in_array( $post->ID, $watched_videos );
                    $is_locked  = ! $is_watched && ! $can_watch_next;
                    $current_progress = isset($video_progress[$post->ID]) ? $video_progress[$post->ID] : 0;

                    $author_id = $post->post_author;
                    $author_avatar = get_avatar_url( $author_id, array( 'size' => 48 ) );
                    $author_fname = get_the_author_meta( 'first_name', $author_id );
                    $author_lname = get_the_author_meta( 'last_name', $author_id );
                    $thumb = get_the_post_thumbnail_url( $post->ID, 'medium' );
                    $excerpt = get_the_excerpt( $post->ID );

                    $classes = array( 'lrd-gt-item' );
                    if ( $is_watched ) $classes[] = 'is-watched';
                    if ( $is_locked ) $classes[] = 'is-locked';
                    ?>
                    <div class="<?php echo implode( ' ', $classes ); ?>"
                         data-id="<?php echo $post->ID; ?>"
                         data-progress="<?php echo esc_attr($current_progress); ?>"
                         <?php if (!$is_locked) echo 'data-video-url="' . esc_attr($this->get_video_url($post)) . '"'; ?>>

                        <div class="lrd-gt-thumb">
                            <?php if ( $thumb ) : ?>
                                <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $post->post_title ); ?>">
                            <?php else : ?>
                                <div class="lrd-gt-thumb-placeholder"></div>
                            <?php endif; ?>
                            <?php if ( $is_locked ) : ?>
                                <div class="lrd-gt-lock-overlay">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="lrd-gt-content">
                            <h4 class="lrd-gt-title"><?php echo esc_html( $post->post_title ); ?></h4>
                            <div class="lrd-gt-excerpt"><?php echo esc_html( $excerpt ); ?></div>

                            <div class="lrd-gt-footer">
                                <div class="lrd-gt-author">
                                    <img src="<?php echo esc_url( $author_avatar ); ?>" alt="">
                                    <span><?php echo esc_html( $author_fname . ' ' . $author_lname ); ?></span>
                                </div>
                                <div class="lrd-gt-play-action">
                                    <span>PLAY</span>
                                    <?php if ( $is_locked ) : ?>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                    <?php else : ?>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    if ( ! $is_watched ) {
                        $can_watch_next = false;
                    }
                endforeach;
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_videos() {
        $args = array(
            'post_type'      => 'growth_track_videos',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        );
        return get_posts( $args );
    }

    private function get_video_url( $post ) {
        $content = $post->post_content;
        preg_match( '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $content, $matches );
        if ( isset( $matches[1] ) ) {
            return 'https://www.youtube.com/embed/' . $matches[1] . '?enablejsapi=1&rel=0&controls=0&modestbranding=1&disablekb=1';
        }
        return '';
    }
}

new LRD_Growth_Track();

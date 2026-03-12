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

        $videos = $this->get_videos();

        if ( empty( $videos ) ) {
            return '<p class="lrd-empty">No growth track videos available.</p>';
        }

        // Check if all videos are watched
        $all_watched = true;
        foreach ( $videos as $v ) {
            if ( ! in_array( $v->ID, $watched_videos ) ) {
                $all_watched = false;
                break;
            }
        }

        ob_start();
        ?>
        <div class="lrd-growth-track-container">
            <?php if ( $all_watched ) : ?>
                <div class="lrd-last-lesson">
                    <h3>Last Lesson</h3>
                    <div class="lrd-video-wrapper">
                        <iframe width="560" height="315" src="https://www.youtube.com/embed/xOzbJKUyOOE" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                </div>
            <?php else : ?>
                <div class="lrd-growth-track-grid">
                    <?php
                    $can_watch_next = true;
                    foreach ( $videos as $index => $post ) :
                        $is_watched = in_array( $post->ID, $watched_videos );
                        $is_locked  = ! $is_watched && ! $can_watch_next;

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
                        <div class="<?php echo implode( ' ', $classes ); ?>" data-id="<?php echo $post->ID; ?>" <?php if (!$is_locked) echo 'data-video-url="' . esc_attr($this->get_video_url($post)) . '"'; ?>>
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
                                <?php if ( $is_watched ) : ?>
                                    <div class="lrd-gt-check-overlay">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="lrd-gt-content">
                                <h4 class="lrd-gt-title"><?php echo esc_html( $post->post_title ); ?></h4>
                                <div class="lrd-gt-excerpt"><?php echo esc_html( $excerpt ); ?></div>
                                <div class="lrd-gt-author">
                                    <img src="<?php echo esc_url( $author_avatar ); ?>" alt="">
                                    <span><?php echo esc_html( $author_fname . ' ' . $author_lname ); ?></span>
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
            <?php endif; ?>
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
        // Simple logic to extract YouTube URL from content
        // In a real scenario, this might be a custom field or specific shortcode
        $content = $post->post_content;
        preg_match( '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $content, $matches );
        if ( isset( $matches[1] ) ) {
            return 'https://www.youtube.com/embed/' . $matches[1] . '?enablejsapi=1&rel=0';
        }
        return '';
    }
}

new LRD_Growth_Track();

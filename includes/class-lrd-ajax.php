<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LRD_Ajax {
    public function __construct() {
        $actions = array(
            'lrd_load_items', 'lrd_add_item', 'lrd_get_item', 'lrd_add_comment', 'lrd_get_comments', 'lrd_get_stats'
        );
        foreach ( $actions as $action ) {
            add_action( 'wp_ajax_' . $action,        array( $this, str_replace('lrd_', '', $action) ) );
            add_action( 'wp_ajax_nopriv_' . $action, array( $this, str_replace('lrd_', '', $action) ) );
        }
    }

    private function verify() {
        check_ajax_referer( 'lrd_nonce', 'nonce' );
    }

    /* ---------- LOAD ITEMS (pagination) ---------- */
    public function load_items() {
        $this->verify();
        $type     = isset($_POST['type'])     ? sanitize_key($_POST['type'])     : 'prayer';
        $offset   = isset($_POST['offset'])   ? intval($_POST['offset'])         : 0;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page'])        : 5;

        wp_send_json_success( array(
            'html'   => LRD_Prayer::get_items_html( $type, $offset, $per_page ),
            'offset' => $offset + $per_page,
        ));
    }

    /* ---------- ADD ITEM ---------- */
    public function add_item() {
        $this->verify();
        global $wpdb;

        $type  = isset($_POST['type'])    ? sanitize_key($_POST['type'])        : 'prayer';
        $title = isset($_POST['title'])   ? sanitize_text_field($_POST['title']): '';
        $msg   = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';

        if ( ! $title || ! $msg ) {
            wp_send_json_error( 'Title and message are required.' );
        }

        $table = $type === 'prayer' ? $wpdb->prefix . 'lrd_prayer' : $wpdb->prefix . 'lrd_godit';

        $data = array(
            'title'   => $title,
            'message' => $msg,
            'status'  => 'publish',
        );

        if ( is_user_logged_in() ) {
            $data['user_id'] = get_current_user_id();
        } else {
            $name  = isset($_POST['guest_name'])  ? sanitize_text_field($_POST['guest_name'])  : '';
            $email = isset($_POST['guest_email']) ? sanitize_email($_POST['guest_email'])       : '';
            if ( ! $name ) wp_send_json_error( 'Name is required.' );
            $data['guest_name']  = $name;
            $data['guest_email'] = $email;
        }

        $wpdb->insert( $table, $data );
        wp_send_json_success( array( 'id' => $wpdb->insert_id ) );
    }

    /* ---------- GET SINGLE ITEM ---------- */
    public function get_item() {
        $this->verify();
        global $wpdb;

        $type = isset($_POST['type']) ? sanitize_key($_POST['type'])  : 'prayer';
        $id   = isset($_POST['id'])   ? intval($_POST['id'])          : 0;

        $table = $type === 'prayer' ? $wpdb->prefix . 'lrd_prayer' : $wpdb->prefix . 'lrd_godit';
        $item  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d AND status='publish'", $id ) );

        if ( ! $item ) wp_send_json_error( 'Not found.' );

        $name   = $item->user_id ? get_userdata($item->user_id)->display_name : $item->guest_name;
        $avatar = $item->user_id ? get_avatar_url($item->user_id, array('size'=>48)) : '';

        wp_send_json_success( array(
            'id'      => $item->id,
            'name'    => $name,
            'avatar'  => $avatar,
            'title'   => $item->title,
            'message' => nl2br( esc_html($item->message) ),
            'date'    => date_i18n( get_option('date_format'), strtotime($item->created_at) ),
        ));
    }

    /* ---------- ADD COMMENT ---------- */
    public function add_comment() {
        $this->verify();
        global $wpdb;

        $type      = isset($_POST['type'])      ? sanitize_key($_POST['type'])              : 'prayer';
        $post_id   = isset($_POST['post_id'])   ? intval($_POST['post_id'])                 : 0;
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id'])               : 0;
        $msg       = isset($_POST['message'])   ? sanitize_textarea_field($_POST['message']): '';

        if ( ! $post_id || ! $msg ) wp_send_json_error('Missing data.');

        $data = array(
            'post_type' => $type,
            'post_id'   => $post_id,
            'parent_id' => $parent_id,
            'message'   => $msg,
        );

        if ( is_user_logged_in() ) {
            $data['user_id'] = get_current_user_id();
        } else {
            $name  = isset($_POST['guest_name'])  ? sanitize_text_field($_POST['guest_name'])  : '';
            $email = isset($_POST['guest_email']) ? sanitize_email($_POST['guest_email'])       : '';
            if ( ! $name ) wp_send_json_error('Name is required.');
            $data['guest_name']  = $name;
            $data['guest_email'] = $email;
        }

        $wpdb->insert( $wpdb->prefix . 'lrd_comments', $data );
        $comment_id = $wpdb->insert_id;

        LRD_Notifications::notify_new_comment( $comment_id );

        // Return the rendered comment
        $comment        = (object)$data;
        $comment->id    = $comment_id;
        $comment->created_at = current_time('mysql');

        wp_send_json_success( array( 'html' => $this->render_comment($comment) ) );
    }

    /* ---------- GET COMMENTS ---------- */
    public function get_comments() {
        $this->verify();
        global $wpdb;

        $type    = isset($_POST['type'])    ? sanitize_key($_POST['type'])  : 'prayer';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id'])     : 0;

        $comments = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lrd_comments WHERE post_type=%s AND post_id=%d AND parent_id=0 ORDER BY created_at ASC",
            $type, $post_id
        ));

        $html = '';
        foreach ( $comments as $c ) {
            $html .= $this->render_comment($c);
            // Fetch replies
            $replies = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lrd_comments WHERE parent_id=%d ORDER BY created_at ASC", $c->id
            ));
            foreach ( $replies as $r ) {
                $html .= $this->render_comment($r, true);
            }
        }

        wp_send_json_success( array( 'html' => $html ?: '<p class="lrd-no-comments">Be the first to respond!</p>' ) );
    }

    public function get_stats() {
        $stats_obj = new LRD_Stats();
        $stats = $stats_obj->get_stats();
        wp_send_json_success( $stats );
    }

    private function render_comment( $c, $is_reply = false ) {
        $name   = $c->user_id ? get_userdata($c->user_id)->display_name : esc_html($c->guest_name);
        $avatar = $c->user_id ? get_avatar($c->user_id, 32) : '<div class="lrd-avatar-placeholder sm">' . strtoupper(substr($name,0,1)) . '</div>';
        $date   = isset($c->created_at) ? date_i18n( get_option('date_format'), strtotime($c->created_at) ) : '';
        $cls    = $is_reply ? 'lrd-comment lrd-reply' : 'lrd-comment';
        return sprintf(
            '<div class="%s" data-id="%d">
                <div class="lrd-comment-avatar">%s</div>
                <div class="lrd-comment-content">
                    <div class="lrd-comment-meta"><strong>%s</strong> <span>%s</span></div>
                    <div class="lrd-comment-text">%s</div>
                    %s
                </div>
            </div>',
            esc_attr($cls),
            $c->id,
            $avatar,
            esc_html($name),
            esc_html($date),
            nl2br( esc_html($c->message) ),
            $is_reply ? '' : sprintf('<a href="#" class="lrd-reply-link" data-id="%d">Reply</a>', $c->id)
        );
    }
}

new LRD_Ajax();

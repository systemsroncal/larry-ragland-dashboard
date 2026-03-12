<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LRD_Notifications {

    public static function notify_new_comment( $comment_id ) {
        global $wpdb;

        $comment = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lrd_comments WHERE id = %d", $comment_id
        ));

        if ( ! $comment ) return;

        $post_type = $comment->post_type;
        $post_id   = $comment->post_id;

        // Get the original post
        $table = $post_type === 'prayer' ? $wpdb->prefix . 'lrd_prayer' : $wpdb->prefix . 'lrd_godit';
        $post  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $post_id ) );

        if ( ! $post ) return;

        $post_type_label = $post_type === 'prayer' ? 'Prayer Request' : 'God Did It Testimony';
        $site_name       = get_bloginfo('name');

        // Notify post owner
        $owner_email = '';
        $owner_name  = '';
        if ( $post->user_id ) {
            $user        = get_userdata( $post->user_id );
            $owner_email = $user ? $user->user_email : '';
            $owner_name  = $user ? $user->display_name : '';
        } else {
            $owner_email = $post->guest_email;
            $owner_name  = $post->guest_name;
        }

        $commenter_name = $comment->user_id
            ? get_userdata( $comment->user_id )->display_name
            : $comment->guest_name;

        if ( $owner_email && ( ! $comment->user_id || $comment->user_id !== $post->user_id ) ) {
            $subject = sprintf( '[%s] Someone replied to your %s', $site_name, $post_type_label );
            $body    = sprintf(
                "Hello %s,\n\n%s left a reply on your %s \"%s\":\n\n\"%s\"\n\nView it here: %s\n\nGod bless,\n%s",
                $owner_name, $commenter_name, $post_type_label,
                $post->title, wp_strip_all_tags($comment->message),
                get_permalink( get_option('page_on_front') ),
                $site_name
            );
            wp_mail( $owner_email, $subject, $body );
        }

        // If this is a reply, notify the parent comment author
        if ( $comment->parent_id ) {
            $parent = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lrd_comments WHERE id = %d", $comment->parent_id
            ));
            if ( $parent ) {
                $parent_email = $parent->user_id
                    ? get_userdata($parent->user_id)->user_email
                    : $parent->guest_email;
                $parent_name  = $parent->user_id
                    ? get_userdata($parent->user_id)->display_name
                    : $parent->guest_name;

                if ( $parent_email && $parent_email !== $owner_email ) {
                    $subject = sprintf( '[%s] Someone replied to your comment', $site_name );
                    $body    = sprintf(
                        "Hello %s,\n\n%s replied to your comment on \"%s\":\n\n\"%s\"\n\nGod bless,\n%s",
                        $parent_name, $commenter_name, $post->title,
                        wp_strip_all_tags($comment->message), $site_name
                    );
                    wp_mail( $parent_email, $subject, $body );
                }
            }
        }
    }
}

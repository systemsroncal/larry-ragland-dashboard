/* Larry Ragland Dashboard — lrd.js */
(function($){
    'use strict';

    /* ============================================================
       OVERLAY / MODAL SYSTEM
       ============================================================ */
    var $overlay = null;

    function buildOverlay() {
        if ( $overlay ) return;
        $overlay = $('<div class="lrd-overlay"><div class="lrd-modal"><button class="lrd-modal-close" aria-label="Close">&times;</button><div class="lrd-modal-body"></div></div></div>');
        $('body').append($overlay);

        $overlay.on('click', function(e){
            if ( $(e.target).is('.lrd-overlay') ) closeModal();
        });
        $overlay.on('click', '.lrd-modal-close', closeModal);
    }

    function openModal( html ) {
        buildOverlay();
        $overlay.find('.lrd-modal-body').html(html);
        $overlay.addClass('active');
        $('body').css('overflow','hidden');
    }

    function closeModal() {
        if ( $overlay ) {
            $overlay.removeClass('active');
            $('body').css('overflow','');
        }
    }

    /* ============================================================
       VIEW ITEM DETAIL
       ============================================================ */
    $(document).on('click', '.lrd-item', function(){
        var $el   = $(this);
        var id    = $el.data('id');
        var type  = $el.data('type');

        openModal('<div class="lrd-spinner"></div>');

        $.post(LRD.ajax_url, {
            action: 'lrd_get_item',
            nonce:  LRD.nonce,
            id:     id,
            type:   type
        }, function(res){
            if ( ! res.success ) return;
            var d   = res.data;
            var av  = d.avatar ? '<img src="'+d.avatar+'" alt="">' : '<div class="lrd-avatar-placeholder">'+d.name.charAt(0).toUpperCase()+'</div>';
            var cls = type === 'godit' ? 'lrd-purple-btn' : '';

            var html =
                '<div class="lrd-detail-header">'
                    + '<div class="lrd-detail-avatar">' + av + '</div>'
                    + '<div><div class="lrd-detail-name">'+escHtml(d.name)+'</div><div class="lrd-detail-date">'+escHtml(d.date)+'</div></div>'
                + '</div>'
                + '<div class="lrd-detail-title">'+escHtml(d.title)+'</div>'
                + '<div class="lrd-detail-message">'+d.message+'</div>'
                + '<div class="lrd-comments-section">'
                    + '<h4>Responses</h4>'
                    + '<div class="lrd-comments-list" data-post-id="'+id+'" data-type="'+type+'"><div class="lrd-spinner"></div></div>'
                    + buildCommentForm( type, id, 0, cls )
                + '</div>';

            openModal(html);
            loadComments( id, type );
        });
    });

    function loadComments( postId, type ) {
        $.post(LRD.ajax_url, {
            action:  'lrd_get_comments',
            nonce:   LRD.nonce,
            post_id: postId,
            type:    type
        }, function(res){
            if ( res.success ) {
                $('.lrd-comments-list[data-post-id="'+postId+'"]').html(res.data.html);
            }
        });
    }

    function buildCommentForm( type, postId, parentId, extraCls ) {
        var guestFields = LRD.logged_in ? '' :
            '<div class="lrd-form-row"><label>Your Name *</label><input type="text" class="lrd-guest-name" placeholder="Name"></div>'
            + '<div class="lrd-form-row"><label>Email (for notifications)</label><input type="email" class="lrd-guest-email" placeholder="email@example.com"></div>';

        return '<div class="lrd-comment-form" data-type="'+type+'" data-post-id="'+postId+'" data-parent-id="'+parentId+'">'
            + guestFields
            + '<div class="lrd-form-row"><label>Your response</label><textarea class="lrd-comment-msg" placeholder="Write something..."></textarea></div>'
            + '<button class="lrd-submit-btn lrd-submit-comment '+extraCls+'">Post Response</button>'
            + '<div class="lrd-form-msg"></div>'
        + '</div>';
    }

    /* ---- Reply link ---- */
    $(document).on('click', '.lrd-reply-link', function(e){
        e.preventDefault();
        var commentId = $(this).data('id');
        var $section  = $(this).closest('.lrd-comments-section');
        var postId    = $section.find('.lrd-comments-list').data('post-id');
        var type      = $section.find('.lrd-comments-list').data('type');
        var cls       = type === 'godit' ? 'lrd-purple-btn' : '';

        // Remove existing reply form
        $section.find('.lrd-inline-reply').remove();
        var $replyWrap = $('<div class="lrd-inline-reply">').html(buildCommentForm(type, postId, commentId, cls));
        $(this).closest('.lrd-comment').after($replyWrap);
    });

    /* ---- Submit comment ---- */
    $(document).on('click', '.lrd-submit-comment', function(){
        var $btn    = $(this);
        var $form   = $btn.closest('.lrd-comment-form, .lrd-inline-reply .lrd-comment-form');
        var type    = $form.data('type');
        var postId  = $form.data('post-id');
        var parentId= $form.data('parent-id') || 0;
        var msg     = $form.find('.lrd-comment-msg').val().trim();
        var $msg    = $form.find('.lrd-form-msg');

        if ( ! msg ) { $msg.text('Please enter a response.'); return; }

        var data = {
            action:    'lrd_add_comment',
            nonce:     LRD.nonce,
            type:      type,
            post_id:   postId,
            parent_id: parentId,
            message:   msg
        };

        if ( ! LRD.logged_in ) {
            var name  = $form.find('.lrd-guest-name').val().trim();
            var email = $form.find('.lrd-guest-email').val().trim();
            if ( ! name ) { $msg.text('Name is required.'); return; }
            data.guest_name  = name;
            data.guest_email = email;
        }

        $btn.prop('disabled', true).append('<span class="lrd-spinner"></span>');

        $.post(LRD.ajax_url, data, function(res){
            $btn.prop('disabled', false).find('.lrd-spinner').remove();
            if ( res.success ) {
                $form.find('.lrd-comment-msg').val('');
                $msg.addClass('success').text('Posted!');
                var $list = $('.lrd-comments-list[data-post-id="'+postId+'"]');
                if ( parentId ) {
                    $form.closest('.lrd-inline-reply').before(res.data.html).remove();
                } else {
                    $list.append(res.data.html);
                }
                setTimeout(function(){ $msg.text('').removeClass('success'); }, 3000);
            } else {
                $msg.text(res.data || 'Error posting response.');
            }
        });
    });

    /* ============================================================
       ADD NEW ITEM POPUP
       ============================================================ */
    $(document).on('click', '.lrd-add-btn', function(){
        var type  = $(this).data('type');
        var label = type === 'prayer' ? 'Prayer Request' : 'God Did It Testimony';
        var cls   = type === 'godit'  ? 'lrd-purple-btn' : '';

        var guestFields = LRD.logged_in ? '' :
            '<div class="lrd-form-row"><label>Your Name *</label><input type="text" class="lrd-guest-name" placeholder="Name"></div>'
            + '<div class="lrd-form-row"><label>Email (for notifications)</label><input type="email" class="lrd-guest-email" placeholder="email@example.com"></div>';

        var html = '<h2>Add ' + label + '</h2>'
            + '<div class="lrd-add-form" data-type="'+type+'">'
            + guestFields
            + '<div class="lrd-form-row"><label>Title *</label><input type="text" class="lrd-add-title" placeholder="Brief title..."></div>'
            + '<div class="lrd-form-row"><label>Message *</label><textarea class="lrd-add-message" placeholder="Share your request..."></textarea></div>'
            + '<button class="lrd-submit-btn lrd-submit-add '+cls+'">Submit</button>'
            + '<div class="lrd-form-msg"></div>'
            + '</div>';

        openModal(html);
    });

    $(document).on('click', '.lrd-submit-add', function(){
        var $btn   = $(this);
        var $form  = $btn.closest('.lrd-add-form');
        var type   = $form.data('type');
        var title  = $form.find('.lrd-add-title').val().trim();
        var msg    = $form.find('.lrd-add-message').val().trim();
        var $msgEl = $form.find('.lrd-form-msg');

        if ( ! title || ! msg ) { $msgEl.text('Title and message are required.'); return; }

        var data = {
            action:  'lrd_add_item',
            nonce:   LRD.nonce,
            type:    type,
            title:   title,
            message: msg
        };

        if ( ! LRD.logged_in ) {
            var name  = $form.find('.lrd-guest-name').val().trim();
            var email = $form.find('.lrd-guest-email').val().trim();
            if ( ! name ) { $msgEl.text('Name is required.'); return; }
            data.guest_name  = name;
            data.guest_email = email;
        }

        $btn.prop('disabled', true).append('<span class="lrd-spinner"></span>');

        $.post(LRD.ajax_url, data, function(res){
            $btn.prop('disabled', false).find('.lrd-spinner').remove();
            if ( res.success ) {
                $msgEl.addClass('success').text('Submitted! Thank you.');
                $form.find('input, textarea').val('');
                // Prepend new item to the correct list
                refreshList( type );
                setTimeout(closeModal, 2000);
            } else {
                $msgEl.text(res.data || 'Error submitting. Please try again.');
            }
        });
    });

    function refreshList( type ) {
        var $widget = $('.lrd-widget[data-type="'+type+'"]');
        var perPage = $widget.data('per-page') || 5;

        $.post(LRD.ajax_url, {
            action:   'lrd_load_items',
            nonce:    LRD.nonce,
            type:     type,
            offset:   0,
            per_page: perPage
        }, function(res){
            if ( res.success ) {
                $widget.find('.lrd-list').html(res.data.html);
                $widget.find('.lrd-explore-more').data('offset', perPage);
            }
        });
    }

    /* ============================================================
       EXPLORE MORE (pagination)
       ============================================================ */
    $(document).on('click', '.lrd-explore-more[data-type]', function(e){
        e.preventDefault();
        var $el     = $(this);
        var type    = $el.data('type');
        var offset  = parseInt($el.data('offset'))   || 0;
        var perPage = parseInt($el.data('per-page')) || 5;

        $el.text('Loading...').css('pointer-events','none');

        $.post(LRD.ajax_url, {
            action:   'lrd_load_items',
            nonce:    LRD.nonce,
            type:     type,
            offset:   offset,
            per_page: perPage
        }, function(res){
            $el.text('Explore More \u203a').css('pointer-events','');
            if ( res.success ) {
                var $list = $el.closest('.lrd-widget').find('.lrd-list');
                $list.html(res.data.html);
                $el.data('offset', res.data.offset);
                window.scrollTo({ top: $list.offset().top - 60, behavior: 'smooth' });
            }
        });
    });

    /* ============================================================
       COUNTDOWN TIMER
       ============================================================ */
    function initCountdowns() {
        $('.lrd-countdown').each(function(){
            var $el = $(this);
            var ts  = parseInt($el.data('timestamp')) * 1000;

            function tick() {
                var now  = Date.now();
                var diff = ts - now;
                if ( diff <= 0 ) { $el.closest('.lrd-next-event').find('.lrd-cd-days,.lrd-cd-hours,.lrd-cd-mins').text('0'); return; }

                var days  = Math.floor( diff / 86400000 );
                var hours = Math.floor( (diff % 86400000) / 3600000 );
                var mins  = Math.floor( (diff % 3600000) / 60000 );

                $el.find('.lrd-cd-days').text(days);
                $el.find('.lrd-cd-hours').text(hours);
                $el.find('.lrd-cd-mins').text(mins);
            }

            tick();
            setInterval(tick, 30000);
        });
    }

    /* ============================================================
       HELPERS
       ============================================================ */
    function escHtml(str) {
        return $('<div>').text(str).html();
    }

    /* ============================================================
       INIT
       ============================================================ */
    $(function(){
        initCountdowns();
        initStatsAutoUpdate();
    });

    /* ============================================================
       STATS AUTO-UPDATE
       ============================================================ */
    function initStatsAutoUpdate() {
        if ( ! $('.lrd-stats-bar').length ) return;

        setInterval(function(){
            $.post(LRD.ajax_url, {
                action: 'lrd_get_stats',
                nonce:  LRD.nonce
            }, function(res){
                if ( res.success ) {
                    updateStatNum('#lrd-stat-members', res.data.members);
                    updateStatNum('#lrd-stat-online',  res.data.online);
                    updateStatNum('#lrd-stat-prayer',  res.data.prayer);
                    updateStatNum('#lrd-stat-events',  res.data.events);
                }
            });
        }, 300000); // 5 minutes
    }

    function updateStatNum(selector, newVal) {
        var $el = $(selector);
        var currentVal = parseInt($el.text().replace(/,/g, '')) || 0;
        if ( currentVal !== newVal ) {
            $el.fadeOut(200, function(){
                $el.text(newVal.toLocaleString()).fadeIn(200);
            });
        }
    }

})(jQuery);

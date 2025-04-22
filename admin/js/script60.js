jQuery(document).ready(function($) {
    const $modal = $('#admin-notes-modal');
    const $button = $('#admin-notes-button');
    const $textarea = $('#admin-notes-text');
    const $timestamp = $('#admin-notes-timestamp');
    const $gear = $('#admin-notes-gear');
    const $settings = $('#admin-notes-settings');
    const $back = $('#admin-notes-back');
    let saveTimer;

    // Restore positions
    if (AdminNotes.modal_position) {
        $modal.css({
            top: AdminNotes.modal_position.top,
            left: AdminNotes.modal_position.left,
            right: 'auto',
            bottom: 'auto',
            position: 'fixed'
        });
    }
    if (AdminNotes.button_position) {
        $button.css({
            top: AdminNotes.button_position.top,
            left: AdminNotes.button_position.left,
            right: 'auto',
            bottom: 'auto',
            position: 'fixed'
        });
    } else {
        // Default fallback if reset
        $button.removeAttr("style").css({
            bottom: '20px',
            right: '20px',
            position: 'fixed',
            top: 'auto',
            left: 'auto'
        });                
    }    

    // Restore modal visibility state
    const modalWasOpen = localStorage.getItem('adminNotesModalOpen') === 'true';
    if (modalWasOpen) {
        $modal.removeClass('hidden');
    }

    function saveNote() {
        const note = $textarea.val();

        $.post(AdminNotes.ajax_url, {
            action: 'save_admin_note',
            note: note,
            nonce: AdminNotes.nonce
        }, function(response) {
            if (response.success) {
                const $overlay = $('#admin-notes-overlay');
                $overlay.removeClass('hidden').fadeIn(200, function() {
                    setTimeout(() => {
                        $overlay.fadeOut(400, function() {
                            $overlay.addClass('hidden');
                        });
                    }, 1200);
                });

                const now = new Date();
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                const date = now.toLocaleDateString(undefined, options);
                const time = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });

                $timestamp.text('Last saved: ' + date + ' at ' + time);
            }
        });
    }

    $('#admin-notes-close').on('click', function() {
        $modal.addClass('hidden');
        localStorage.setItem('adminNotesModalOpen', 'false');
    });

    $('#admin-notes-save').on('click', saveNote);

    $textarea.on('input', function() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveNote, 5000);
    });

    function makeDraggable($el, targetName) {
        let isDragging = false;
        let offsetX, offsetY;
        let moved = false;

        $el.on('mousedown', function(e) {
            isDragging = true;
            moved = false;
            offsetX = e.clientX - $el.offset().left;
            offsetY = e.clientY - $el.offset().top;
            $el.css('transition', 'none');
            e.preventDefault();
        });

        $(document).on('mousemove', function(e) {
            if (isDragging) {
                moved = true;
                $el.css({
                    left: e.clientX - offsetX + 'px',
                    top: e.clientY - offsetY + 'px',
                    right: 'auto',
                    bottom: 'auto',
                    position: 'fixed'
                });
            }
        });

        $(document).on('mouseup', function(e) {
            if (isDragging) {
                isDragging = false;

                const pos = {
                    top: $el.css('top'),
                    left: $el.css('left'),
                    nonce: AdminNotes.nonce,
                    action: 'save_admin_notes_position',
                    target: targetName
                };
                $.post(AdminNotes.ajax_url, pos);

                if (targetName === 'button' && !moved) {
                    $modal.removeClass('hidden');
                    localStorage.setItem('adminNotesModalOpen', 'true');
                }
            }
        });
    }

    makeDraggable($modal, 'modal');
    makeDraggable($button, 'button');

    // Settings toggle
    $gear.on('click', function() {
        $modal.addClass('admin-notes-body-hidden');
        $settings.removeClass('hidden');
    });

    $back.on('click', function() {
        $modal.removeClass('admin-notes-body-hidden');
        $settings.addClass('hidden');
    });

    // Reveal button (modal only if user left it open)
    $button.removeClass('hidden');

    $('#admin-notes-reset').on('click', function() {
        $.ajax({
            type: 'POST',
            url: AdminNotes.ajax_url,
            data: {
                action: 'reset_admin_notes_positions',
                nonce: AdminNotes.nonce
            },
            success: function(response) {
                if (response.success) {
                    localStorage.setItem('adminNotesModalOpen', 'false');
                
                    // Reset modal visually
                    $modal.removeAttr("style").css({
                        bottom: '80px',
                        right: '5px',
                        position: 'fixed',
                        top: 'auto',
                        left: 'auto',
                        zIndex: 10000
                    });
                    
                    $button.removeAttr("style").css({
                        bottom: '20px',
                        right: '20px',
                        position: 'fixed',
                        top: 'auto',
                        left: 'auto',
                        zIndex: 9999
                    });
                    
                    // Hide modal & settings view
                    $modal.removeClass('admin-notes-body-hidden').addClass('hidden');
                    $settings.addClass('hidden');                    
                
                    // Reset button visually
                    $button.removeAttr("style").css({
                        bottom: '20px',
                        right: '20px',
                        position: 'fixed',
                        top: 'auto',
                        left: 'auto',
                        zIndex: 9999
                    });
                }                
            }
        });
    });            
});

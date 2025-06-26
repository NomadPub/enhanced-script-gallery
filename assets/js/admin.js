jQuery(document).ready(function ($) {
    'use strict';

    // === Modal Setup for Editing Scripts ===
    const $modalOverlay = $('<div>', {
        id: 'esg-modal-overlay',
        style: 'display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9998;'
    });

    const $modal = $('<div>', {
        id: 'esg-edit-modal',
        style: 'display:none; position:fixed; top:20%; left:50%; transform:translate(-50%, -50%); z-index:9999; background:#fff; padding:20px; max-width:500px; width:90%; border-radius:5px; box-shadow:0 0 10px rgba(0,0,0,0.3);'
    });

    $('body').append($modalOverlay).append($modal);

    // Template for edit modal HTML
    function getEditModalHTML(scriptData) {
        return `
            <h3>Edit Script</h3>
            <form id="esg-edit-form" data-file="${scriptData.file}">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="edit-name">Script Name</label></th>
                        <td><input type="text" id="edit-name" name="edit_name" value="${scriptData.name}" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="edit-description">Description</label></th>
                        <td>
                            <textarea id="edit-description" name="edit_description" rows="4" style="width:100%;">${scriptData.description || ''}</textarea>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Changes</button>
                    <button type="button" id="esg-close-modal" class="button">Cancel</button>
                </p>
            </form>
        `;
    }

    // === Open Edit Modal ===
    $(document).on('click', '.esg-edit-btn', function () {
        const scriptData = $(this).data();
        $modal.html(getEditModalHTML(scriptData));
        $modalOverlay.show();
        $modal.show();
    });

    // === Close Modal ===
    $(document).on('click', '#esg-close-modal, #esg-modal-overlay', function () {
        $modalOverlay.hide();
        $modal.hide();
    });

    // === Submit Edit Form via AJAX ===
    $(document).on('submit', '#esg-edit-form', function (e) {
        e.preventDefault();

        const file = $('#esg-edit-form').data('file');
        const name = $('#edit-name').val().trim();
        const description = $('#edit-description').val().trim();

        $.ajax({
            url: esg_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'esg_edit_script',
                security: esg_ajax.nonce,
                file: file,
                edit_name: name,
                edit_description: description
            },
            success: function (res) {
                if (res.success) {
                    alert('✅ Script updated successfully!');
                    location.reload();
                } else {
                    alert('❌ Update failed: ' + (res.data.message || 'Try again.'));
                }
            },
            error: function (xhr, status, error) {
                alert('⛔ AJAX Error: ' + error);
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    });

    // === Upload Script Form Submission ===
    $('#esg-upload-form').on('submit', function (e) {
        e.preventDefault();

        let formData = new FormData(this);
        formData.append('action', 'esg_save_script');
        formData.append('security', esg_ajax.nonce);

        $.ajax({
            url: esg_ajax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', esg_ajax.nonce);
            },
            success: function (res) {
                try {
                    if (res.success) {
                        alert('✅ Script uploaded successfully!');
                        location.reload();
                    } else {
                        alert('❌ Upload failed: ' + (res.data.message || 'Check console for details'));
                    }
                } catch (e) {
                    alert('⚠️ Invalid JSON response. Check console.');
                    console.error('Raw response:', res);
                }
            },
            error: function (xhr, status, error) {
                alert('⛔ AJAX Error: ' + error);
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    });

    // === Drag-and-Drop Reordering ===
    $('#esg-scripts-table tbody').sortable({
        handle: '.esg-sort-handle',
        update: function (event, ui) {
            let order = [];

            $('#esg-scripts-table tbody tr').each(function (i) {
                let file = $(this).find('.esg-delete-btn').data('file');
                order.push({ file: file, order: i });
            });

            $.post(esg_ajax.ajaxurl, {
                action: 'esg_update_order',
                order: JSON.stringify(order),
                security: esg_ajax.nonce
            }, function (res) {
                if (!res?.success) {
                    alert('🔄 Failed to update order: ' + (res.data?.message || 'Unknown error'));
                }
            }).fail(function (xhr) {
                alert('⚠️ Network error when updating order.');
                console.error('Order Update Error:', xhr.responseText);
            });
        }
    });

    // === Delete Script ===
    $(document).on('click', '.esg-delete-btn', function () {
        if (!confirm('Are you sure you want to delete this script?')) return;

        const file = $(this).data('file');

        $.post(esg_ajax.ajaxurl, {
            action: 'esg_delete_script',
            file: file,
            security: esg_ajax.nonce
        }, function (res) {
            if (res?.success) {
                location.reload();
            } else {
                alert('🗑️ Delete failed: ' + (res.data?.message || 'Try again.'));
            }
        }).fail(function (xhr) {
            alert('⚠️ Network error when deleting.');
            console.error('Delete Error:', xhr.responseText);
        });
    });

    // === Download All Scripts as ZIP ===
    $('#esg-download-all').on('click', function () {
        $.post(esg_ajax.ajaxurl, {
            action: 'esg_download_all_scripts',
            security: esg_ajax.nonce
        }, function (res) {
            if (res?.success && res.data?.zip_url) {
                window.open(res.data.zip_url, '_blank');
            } else {
                alert('📦 ZIP download failed: ' + (res.data?.message || 'Unknown error'));
            }
        }).fail(function (xhr) {
            alert('⚠️ Failed to generate ZIP.');
            console.error('ZIP Generation Error:', xhr.responseText);
        });
    });
});
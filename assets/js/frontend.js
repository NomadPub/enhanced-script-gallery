jQuery(document).ready(function($) {
    const nonce = esg_ajax.nonce;

    // Preview code snippets
    $('.esg-code-snippet').each(function() {
        let el = $(this);
        let url = el.data('url');

        fetch(url)
            .then(response => response.text())
            .then(data => {
                el.text(data.substring(0, 500));
                Prism.highlightElement(el[0]);
            })
            .catch(() => el.text('Could not load snippet.'));
    });

    // Filter by type
    $('#filter-type').on('change', function() {
        let selectedType = $(this).val().toLowerCase();
        $('.esg-script-card').each(function() {
            let card = $(this);
            let cardType = card.data('type').toLowerCase();
            if (!selectedType || cardType === selectedType) {
                card.show();
            } else {
                card.hide();
            }
        });
    });

    // Search by name
    $('#search-name').on('keyup', function() {
        let query = $(this).val().toLowerCase();
        $('.esg-script-card').each(function() {
            let name = $(this).data('name').toLowerCase();
            $(this).toggle(name.includes(query));
        });
    });

    // Download all scripts
    $('#download-all-scripts').on('click', function() {
        $.post(esg_ajax.ajaxurl, {
            action: 'esg_download_all_scripts',
            security: nonce
        }, function(res) {
            if (res.success) {
                window.open(res.data.zip_url, '_blank');
            } else {
                alert('Failed to generate ZIP.');
            }
        });
    });
});
<?php
$scripts = get_option('esg_scripts', []);
if (empty($scripts)) {
    echo '<p>No scripts available.</p>';
    return;
}

// Sort by order
usort($scripts, fn($a, $b) => $a['order'] <=> $b['order']);
?>

<style>
.esg-filter-bar {
    margin-bottom: 20px;
}
.esg-filter-bar select,
.esg-filter-bar input {
    padding: 6px 12px;
    font-size: 14px;
    border-radius: 4px;
    border: 1px solid #ccc;
}
.esg-gallery {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: flex-start;
}
.esg-script-card {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    width: 280px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    transition: transform 0.2s ease-in-out;
}
.esg-script-card:hover {
    transform: translateY(-5px);
}
.esg-script-card i {
    font-size: 40px;
    color: #333;
    margin-bottom: 10px;
}
.esg-script-card a {
    display: block;
    margin-top: 10px;
    font-weight: bold;
    color: #0073aa;
    text-decoration: none;
}
.esg-script-card .script-info {
    font-size: 14px;
    color: #555;
    margin-top: 5px;
}
.script-description {
    font-size: 14px;
    color: #666;
    margin-top: 8px;
    max-height: 60px;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<!-- Filter Bar -->
<div class="esg-filter-bar">
    <select id="filter-type">
        <option value="">All Types</option>
        <?php
        $types = array_unique(array_column($scripts, 'type'));
        foreach ($types as $type): ?>
            <option value="<?= esc_attr(strtolower($type)) ?>"><?= esc_html($type) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="text" id="search-name" placeholder="Search by name..." />
</div>

<!-- Script Gallery -->
<div class="esg-gallery" id="esg-gallery">
    <?php foreach ($scripts as $script):
        // Set icon based on script type
        $icon = match(strtolower($script['type'])) {
            'python' => 'fab fa-python',
            'javascript' => 'fab fa-js-square',
            'groovy' => 'fas fa-magic',
            'shell' => 'fas fa-terminal',
            default => 'fas fa-file-code',
        };
    ?>
    <div class="esg-script-card">
    <i class="<?= esc_attr($icon) ?>"></i>
    <br />
    <a href="<?= esc_url($script['file']) ?>" download><?= esc_html($script['original_name']) ?></a>
    <div class="script-info"><?= esc_html($script['type']) ?> • <?= esc_html($script['size']) ?> KB</div>
    <?php if (!empty($script['description'])): ?>
        <div class="script-description"><?= esc_html($script['description']) ?></div>
    <?php endif; ?>
</div>
    <?php endforeach; ?>
</div>

<!-- Syntax Highlighting -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    Prism.highlightAll();
});
</script>

<!-- Filtering & Search Logic -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.esg-script-card');

    // Type filter
    document.getElementById('filter-type').addEventListener('change', function() {
        let selectedType = this.value.toLowerCase();
        cards.forEach(card => {
            let cardType = card.getAttribute('data-type');
            if (!selectedType || cardType === selectedType) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Name search
    document.getElementById('search-name').addEventListener('input', function() {
        let searchTerm = this.value.toLowerCase();
        cards.forEach(card => {
            let cardName = card.getAttribute('data-name').toLowerCase();
            if (cardName.includes(searchTerm)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
});
</script>
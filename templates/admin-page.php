<?php
if (!defined('ABSPATH')) {
    exit;
}
$scripts = get_option('esg_scripts', []);
?>
<div class="wrap">
    <h1>Enhanced Script Gallery</h1>

    <!-- Debug Information -->
    <div class="notice notice-info">
        <p><strong>Debug Info:</strong></p>
        <p>AJAX URL: <?php echo admin_url('admin-ajax.php'); ?></p>
        <p>Nonce: <?php echo wp_create_nonce('esg_nonce'); ?></p>
        <p>User Can Manage: <?php echo current_user_can('manage_options') ? 'Yes' : 'No'; ?></p>
        <p>Upload Dir: <?php $upload_dir = wp_upload_dir(); echo $upload_dir['path']; ?></p>
        <p>ZipArchive Available: <?php echo class_exists('ZipArchive') ? 'Yes' : 'No'; ?></p>
    </div>

    <!-- Upload Form -->
    <div class="esg-upload-section">
        <h2>Add New Script</h2>
        <form id="esg-upload-form" enctype="multipart/form-data">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="script_name">Script Name</label></th>
                    <td>
                        <input type="text" id="script_name" name="name" class="regular-text" required>
                        <p class="description">Enter a descriptive name for your script</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="script_type">Script Type</label></th>
                    <td>
                        <select id="script_type" name="type" required>
                            <option value="">Choose type...</option>
                            <option value="python">Python</option>
                            <option value="javascript">JavaScript</option>
                            <option value="php">PHP</option>
                            <option value="bash">Bash/Shell</option>
                            <option value="html">HTML</option>
                            <option value="css">CSS</option>
                            <option value="other">Other</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="script_description">Description</label></th>
                    <td>
                        <textarea id="script_description" name="description" rows="4" style="width: 100%;"></textarea>
                        <p class="description">Add a short description about what this script does</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="script_file">Script File</label></th>
                    <td>
                        <input type="file" id="script_file" name="script_file" accept=".py,.js,.php,.sh,.html,.css,.txt" required>
                        <p class="description">Upload your script file (will be automatically zipped)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="script_order">Display Order</label></th>
                    <td>
                        <input type="number" id="script_order" name="order" value="<?php echo count($scripts) + 1; ?>" min="1">
                        <p class="description">Order in which this script appears</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" id="esg-upload-btn" class="button button-primary">Add Script</button>
            </p>
        </form>
    </div>

    <!-- Scripts List -->
    <div class="esg-scripts-section">
        <h2>Uploaded Scripts</h2>

        <?php if (empty($scripts)): ?>
            <p>No scripts uploaded yet.</p>
        <?php else: ?>
            <p>
                <button id="esg-download-all" class="button">Download All as ZIP</button>
            </p>
            <table id="esg-scripts-table" class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="30">Order</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Original File</th>
                        <th>Size</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scripts as $index => $script): 
                        $description = !empty($script['description']) ? esc_html($script['description']) : 'No description';
                    ?>
                        <tr>
                            <td>
                                <span class="esg-sort-handle dashicons dashicons-menu" style="cursor: move;"></span>
                                <?= esc_html($script['order']); ?>
                            </td>
                            <td><?= esc_html($script['name']); ?></td>
                            <td>
                                <span class="esg-type-badge esg-type-<?= esc_attr($script['type']); ?>">
                                    <?= ucfirst(esc_html($script['type'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= esc_url($script['file']); ?>" download>
                                    <?= esc_html($script['original_name']); ?>
                                </a>
                            </td>
                            <td><?= esc_html($script['size']); ?> KB</td>
                            <td title="<?= esc_attr($description); ?>">
                                <?= substr($description, 0, 50); ?><?= strlen($description) > 50 ? '...' : ''; ?>
                            </td>
                            <td>
                                <a href="<?= esc_url($script['file']); ?>" class="button button-small" download>Download</a>
                                <button class="button button-small button-link-delete esg-delete-btn"
                                        data-file="<?= esc_attr($script['file']); ?>">Delete</button>
                                <button class="button button-small esg-edit-btn"
                                        data-file="<?= esc_attr($script['file']); ?>"
                                        data-name="<?= esc_attr($script['name']); ?>"
                                        data-description="<?= esc_attr($script['description'] ?? ''); ?>"
                                        data-type="<?= esc_attr($script['type']); ?>">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.esg-type-badge {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    color: white;
}
.esg-type-python { background: #3776ab; }
.esg-type-javascript { background: #f7df1e; color: black; }
.esg-type-php { background: #777bb4; }
.esg-type-bash { background: #4EAA25; }
.esg-type-html { background: #e34c26; }
.esg-type-css { background: #1572b6; }
.esg-type-other { background: #666; }

.esg-upload-section,
.esg-scripts-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

#esg-scripts-table tbody tr {
    cursor: move;
}

.esg-sort-handle {
    color: #666;
    margin-right: 5px;
}
.esg-sort-handle:hover {
    color: #0073aa;
}
</style>
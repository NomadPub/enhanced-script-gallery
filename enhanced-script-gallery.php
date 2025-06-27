<?php
/*
Plugin Name: Enhanced Script Gallery
Description: Upload, manage, and showcase scripts safely. Includes description field and ZIP upload.
Version: 3.3
Author: Damon Noisette
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('ESG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ESG_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load dependencies
require_once ESG_PLUGIN_DIR . 'includes/zip-exporter.php';

// Enqueue frontend assets
function esg_enqueue_frontend_assets() {
    wp_enqueue_style('esg-frontend-css', ESG_PLUGIN_URL . 'assets/css/frontend.css');
    wp_enqueue_script('esg-frontend-js', ESG_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], null, true);

    // Font Awesome + Syntax Highlighting
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css ');
    wp_enqueue_style('prism-css', ESG_PLUGIN_URL . 'assets/prism/prism.css');
    wp_enqueue_script('prism-js', ESG_PLUGIN_URL . 'assets/prism/prism.js', [], null, true);

    wp_localize_script('esg-frontend-js', 'esg_ajax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('esg_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'esg_enqueue_frontend_assets');

// Enqueue admin assets
function esg_enqueue_admin_assets($hook) {
    if ($hook !== 'toplevel_page_enhanced-script-gallery') return;

    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');

    $cache_buster = time();

    wp_enqueue_script('esg-admin-js', ESG_PLUGIN_URL . 'assets/js/admin.js', ['jquery', 'jquery-ui-sortable'], $cache_buster, true);
    wp_enqueue_style('esg-admin-css', ESG_PLUGIN_URL . 'assets/css/admin.css', [], $cache_buster);

    wp_localize_script('esg-admin-js', 'esg_ajax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('esg_nonce')
    ]);
}
add_action('admin_enqueue_scripts', 'esg_enqueue_admin_assets');

// Admin Menu
function esg_admin_menu() {
    add_menu_page(
        'Enhanced Script Gallery',
        'Script Gallery',
        'manage_options',
        'enhanced-script-gallery',
        'esg_admin_page',
        'dashicons-media-code'
    );
}
add_action('admin_menu', 'esg_admin_menu');

// Save uploaded script
function esg_save_script() {
    ob_start(); // Start buffer to catch any accidental output

    check_ajax_referer('esg_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $name = sanitize_text_field($_POST['name']);
    $type = sanitize_text_field($_POST['type']);
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $order = isset($_POST['order']) ? intval($_POST['order']) : count(get_option('esg_scripts', [])) + 1;

    if (empty($_FILES['script_file']) || $_FILES['script_file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(['message' => 'File upload failed']);
    }

    $upload_dir = wp_upload_dir();
    $tmp_name = $_FILES['script_file']['tmp_name'];
    $original_name = basename($_FILES['script_file']['name']);
    $file_basename = pathinfo($original_name, PATHINFO_FILENAME);
    $zip_filename = $file_basename . '.zip';
    $zip_path = trailingslashit($upload_dir['path']) . $zip_filename;

    if (!class_exists('ZipArchive')) {
        wp_send_json_error(['message' => 'ZIP extension not available']);
    }

    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        wp_send_json_error(['message' => 'Cannot create ZIP archive']);
    }

    $zip->addFile($tmp_name, $original_name);
    $zip->close();

    $file_size_kb = round(filesize($zip_path) / 1024, 2);

    $scripts = get_option('esg_scripts', []);
    $scripts[] = [
        'name' => $name,
        'type' => $type,
        'description' => $description,
        'file' => trailingslashit($upload_dir['url']) . $zip_filename,
        'size' => $file_size_kb,
        'original_name' => $original_name,
        'order' => $order
    ];

    usort($scripts, fn($a, $b) => $a['order'] <=> $b['order']);
    update_option('esg_scripts', $scripts);

    $output = ob_get_clean(); // Discard any extra output

    wp_send_json_success([
        'message' => 'Script saved as ZIP!',
        'scripts' => $scripts
    ]);
}
add_action('wp_ajax_esg_save_script', 'esg_save_script');

// Update script order
function esg_update_order() {
    ob_start();

    check_ajax_referer('esg_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $order = json_decode(stripslashes($_POST['order']), true);
    $scripts = get_option('esg_scripts', []);

    foreach ($scripts as &$script) {
        foreach ($order as $item) {
            if ($script['file'] === $item['file']) {
                $script['order'] = $item['order'];
                break;
            }
        }
    }

    usort($scripts, fn($a, $b) => $a['order'] <=> $b['order']);
    update_option('esg_scripts', $scripts);

    ob_clean();

    wp_send_json_success(['message' => 'Order updated']);
}
add_action('wp_ajax_esg_update_order', 'esg_update_order');

// Delete script
function esg_delete_script() {
    ob_start();

    check_ajax_referer('esg_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $file_to_delete = esc_url_raw($_POST['file']);
    $scripts = get_option('esg_scripts', []);

    foreach ($scripts as $index => $script) {
        if ($script['file'] === $file_to_delete) {
            unset($scripts[$index]);
            break;
        }
    }

    $scripts = array_values($scripts);
    update_option('esg_scripts', $scripts);

    ob_clean();

    wp_send_json_success(['message' => 'Script deleted']);
}
add_action('wp_ajax_esg_delete_script', 'esg_delete_script');

// Download all scripts as ZIP
function esg_download_all_scripts() {
    ob_start();

    check_ajax_referer('esg_nonce', 'security');

    if (!current_user_can('read')) {
        wp_send_json_error(['message' => 'Access denied']);
    }

    $scripts = get_option('esg_scripts', []);
    $exporter = new Enhanced_Script_Zip_Exporter();

    $zip_url = $exporter->generate_zip($scripts);

    ob_clean();

    wp_send_json_success(['zip_url' => $zip_url]);
}
add_action('wp_ajax_esg_download_all_scripts', 'esg_download_all_scripts');

// Edit script metadata
function esg_edit_script() {
    ob_start();

    check_ajax_referer('esg_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $file = esc_url_raw($_POST['file']);
    $new_name = sanitize_text_field($_POST['edit_name']);
    $new_description = sanitize_textarea_field($_POST['edit_description']);

    $scripts = get_option('esg_scripts', []);
    $found = false;

    foreach ($scripts as &$script) {
        if ($script['file'] === $file) {
            $script['name'] = $new_name;
            $script['description'] = $new_description;
            $found = true;
            break;
        }
    }

    if ($found) {
        update_option('esg_scripts', $scripts);
        ob_clean();
        wp_send_json_success(['message' => 'Script updated!', 'scripts' => $scripts]);
    } else {
        ob_clean();
        wp_send_json_error(['message' => 'Script not found']);
    }
}
add_action('wp_ajax_esg_edit_script', 'esg_edit_script');

// Admin Page Template
function esg_admin_page() {
    include ESG_PLUGIN_DIR . 'templates/admin-page.php';
}

// Shortcode to display gallery
function esg_shortcode() {
    ob_start();
    include ESG_PLUGIN_DIR . 'templates/gallery-template.php';
    return ob_get_clean();
}
add_shortcode('script_gallery', 'esg_shortcode');

// Inline Debug JS
function esg_add_inline_debug_js() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_enhanced-script-gallery') {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('esg-upload-form');
            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    formData.append('action', 'esg_save_script');
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.text())
                    .then(text => {
                        try {
                            var data = JSON.parse(text);
                            if (data.success) {
                                alert(data.data.message || 'Uploaded!');
                                location.reload();
                            } else {
                                alert('Error: ' + (data.data.message || 'Unknown error'));
                            }
                        } catch (e) {
                            console.error("JSON parse error", text);
                            alert("⚠️ Invalid response from server");
                        }
                    })
                    .catch(err => {
                        console.error("AJAX Error", err);
                        alert("⛔ Network error occurred");
                    });
                });
            }
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'esg_add_inline_debug_js');

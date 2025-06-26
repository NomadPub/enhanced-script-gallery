<?php
class Enhanced_Script_Zip_Exporter {
    public function generate_zip($scripts) {
        $upload_dir = wp_upload_dir();
        $temp_dir = trailingslashit($upload_dir['basedir']) . 'esg-zips/';
        if (!is_dir($temp_dir)) mkdir($temp_dir, 0755, true);

        $zip_filename = 'scripts-' . time() . '.zip';
        $zip_path = $temp_dir . $zip_filename;

        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            return false;
        }

        foreach ($scripts as $script) {
            $file_basename = basename($script['file']);
            $local_file = download_url($script['file']);
            if (!is_wp_error($local_file)) {
                $zip->addFile($local_file, $file_basename);
                unlink($local_file);
            }
        }

        $zip->close();

        return trailingslashit($upload_dir['baseurl']) . 'esg-zips/' . $zip_filename;
    }
}
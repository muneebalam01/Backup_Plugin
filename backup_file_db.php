<?php
/*
 * Plugin Name:       Backup file and database
 * Description:       This plugin is used for backup of your wordpress files and database
 * Version:           1.10.3
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Dot Marketing
 * License:           GPL v2 or later
 * Text Domain:       backup-files-db
 * Domain Path:       /languages
 */

// Register a custom admin menu for exporting files and database
add_action('admin_menu', 'register_export_files_menu');
function register_export_files_menu() {
    add_menu_page(
        'Export Files and Database',
        'Export Files and Database',
        'manage_options',
        'export-files-db',
        'export_files_db_page'
    );
}

// Callback function for the export files and database menu page
function export_files_db_page() {
    if (isset($_POST['export_files_db'])) {
      
        $directory = ABSPATH; // The root directory of your WordPress installation

        // Define the path and name of the zip file
        $zipFile = WP_CONTENT_DIR . '/exported-files.zip'; 

        // Create a new zip archive
        $zip = new ZipArchive();

        // Open the zip file for writing
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            // Create recursive directory iterator
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                // Skip directories (as they would be added automatically)
                if (!$file->isDir()) {
                    // Get the relative path of the file
                    $relativePath = substr($name, strlen($directory) + 1);

                 
                    $zip->addFile($name, $relativePath);
                }
            }

            // Export the database
            global $wpdb;
            $databaseName = $wpdb->dbname;
            $databaseFile = WP_CONTENT_DIR . '/exported-database.sql';

        
            exec("mysqldump --user=" . DB_USER . " --password=" . DB_PASSWORD . " --host=" . DB_HOST . " " . $databaseName . " > " . $databaseFile);

            
            $zip->addFile($databaseFile, basename($databaseFile));

        
            $zip->close();


            header("Content-Type: application/zip");
            header("Content-Disposition: attachment; filename=\"" . basename($zipFile) . "\"");
            header("Content-Length: " . filesize($zipFile));
            readfile($zipFile);

            unlink($zipFile);
            unlink($databaseFile);
        } else {
           
            echo "Error: Unable to create the zip archive.";
        }
    }

    ?>
    <div class="wrap">
        <h1>Export Files and Database</h1>
        <p>Click the button below to export your WordPress files and database as a zip archive.</p>
        <form method="post">
            <button type="submit" class="button button-primary" name="export_files_db">Export Files and Database</button>
        </form>
    </div>
    <?php
}
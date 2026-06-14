<?php
require 'config/config.php';
try {
    $db = db();
    $db->exec('ALTER DATABASE rme_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
    echo "Database default collation updated.\n";
    
    // We should also drop and recreate the procedures so they inherit the new collation, 
    // or just tell the user to re-import the SQL files.
    $sql = file_get_contents('database/01_functions_procedures_triggers.sql');
    $db->exec($sql);
    echo "Stored procedures and functions recreated successfully.\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

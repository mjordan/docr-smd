<?php

// Get the server application's config settings.
require 'config.php';


// Load the file paths into the database.
try {
  // Connect to the database.
  $db = new PDO('sqlite:' . $config['sqlite3_database_path']);

  // If the 'purge' paramter was included, delete all rows from the db.
  if (isset($argv[1]) && $argv[1] == 'purge') {
    $result = $db->query("DELETE FROM Pages");
    $count = $result->rowCount();
    $db = NULL;
    print "Deleted $count rows from database\n";
    exit;
  }

  // If the 'list' paramter was included, list all the rows.
  if (isset($argv[1]) && $argv[1] == 'list') {
    print "ID\tImage path\tChecked out\tTranscript path\n";
    foreach ($db->query("SELECT * FROM 'Pages'") as $row) {
      print $row['Id'] . "\t" . $row['ImagePath'] . "\t" . $row['CheckedOut'] . "\t" . $row['TranscriptPath'] . "\n";
    }
    $db = NULL;
    exit;
  }

  $file_paths = getImageFiles($config['image_base_dir']);

  // If the database does not exist, create it.
  $db->exec("CREATE TABLE IF NOT EXISTS Pages (Id INTEGER PRIMARY KEY, ImagePath TEXT, CheckedOut INTEGER, TranscriptPath TEXT)");

  // Insert file path data. First check to see if a file is already registered in the database.
  foreach ($file_paths as $filepath) {
    $row_check_query = $db->prepare("SELECT ImagePath FROM Pages WHERE ImagePath = :filepath");
    $row_check_query->bindParam(':filepath', $filepath);
    $row_check_query->execute();
    $result = $row_check_query->fetch();
    if ($result) {
      if ($result['ImagePath'] == $filepath) {
        print "Skipped $filepath\n";
      }
    }
    // If the file isn't registered, add it.
    else {
      $query = $db->prepare("INSERT INTO Pages (ImagePath, CheckedOut, TranscriptPath) VALUES (:filepath, 0, '')");
      $query->bindParam(':filepath', $filepath);
      $query->execute();
      print "Registered $filepath\n";
    }
  }

  // Close the database connection.
  $db = NULL;
}
catch(PDOException $e) {
  print 'Exception : '.$e->getMessage();
}

/**
 * Functions.
 */

// Recurse the image base directory and get all file paths for files ending
// with the extenstions listed in $config['image_file_extensions'].
function getImageFiles() {
  global $config;
  $file_paths = array();
  $iterator = new RecursiveDirectoryIterator($config['image_base_dir']);
  foreach (new RecursiveIteratorIterator($iterator) as $filepath => $fileinfo) {
    if (is_file($filepath) && preg_match('/\.(' . $config['image_file_extensions'] . ')$/', $filepath)) {
      $file_paths[] = $filepath;
    }
  }
  return $file_paths;
}

?>

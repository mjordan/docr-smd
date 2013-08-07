<?php

/**
 * docr/smd config file.
 */

$config = array(
  // The path to the sqlite3 database for docr/smd.
  'sqlite3_database_path' => '/tmp/docr.sqlite',
  'image_base_dir' => '/home/mark/Documents/apache_thinkpad/docr_images/',
  // A |-spearated list of extensions for the page image files you want to OCR.
  'image_file_extensions' => 'jpg|tif',
  'transcript_base_dir' => '/tmp/docr_transcripts/',
  // Set to TRUE if you want docr to delete the source images after it has
  // updated the queue database with the path to the transcript file.
  'delete_images' => FALSE,
);
  

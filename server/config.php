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

/**
 * List of token strings that authorize clients to access this docr server.
 * Leave empty if you don't want to restrict access. Clients must send the
 * X-Auth-Key request header containing a key from this list.
 */
$tokens = array();

/**
 * List of regexes matching client IP addresses allowed to access this docr
 * server. Leave empty if you don't want to restrict access by IP address.
 */
$allowed_ip_addresses = array(
  // '/^123\.243\.(\d+)\.(\d+)/', // For range 123.243.0.0 - 123.243.255.255
);

/**
 * Mime types for common image formats. If you are processing formats
 * not in this list, add them.
 */
$image_mime_types = array(
  'bmp' => 'image/x-ms-bmp',
  'gif' => 'image/gif',
  'jpg' => 'image/jpeg',
  'jpeg' => 'image/jpeg',
  'jp2' => 'image/jp2',
  'pcx' => 'image/pcx',
  'png' => 'image/png',
  'psd' => 'image/x-photoshop',
  'tif' => 'image/tiff',
  'tiff' => 'image/tiff',
);
  

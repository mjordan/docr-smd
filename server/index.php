<?php

/**
 * docr/smd page server. https://github.com/mjordan/docr-smd.
 *
 * Written in the Slim micro-framework, slimframework.com.
 * 
 * Distributed under the MIT License, http://opensource.org/licenses/MIT.
 */

// /page GET - retrieves a new image file for OCR'ing.
// /page PUT - adds the text transcript of the image.
// curl -X POST -d "filename=bar" --data-binary @test.txt http://thinkpad/docr/server/page

// Slim setup.
require 'lib/Slim/Slim.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

// Get the plugin config file and provide a default value for
// $plugins if the file cannot be loaded.
require 'config.php';

/**
 * Route for GET /page. Sample request: curl -v -o mj.jpg http://thinkpad/docr/server/page
 *
 * @param object $app
 * The global $app object instantiated at the top of this file.
 */
$app->get('/page', function () use ($app) {
  global $config;
  $request = $app->request();

  try {
    // Connect to the database and get the next file that doesn't
    // isn't checked out or doesn't have a transcript.
    $db = new PDO('sqlite:' . $config['sqlite3_database_path']);
    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $query = $db->prepare("SELECT Id, ImagePath FROM Pages WHERE CheckedOut = 0 AND TranscriptPath = '' LIMIT 1");
    $query->execute();
    $result = $query->fetch();
    if ($result) {
      // image/jpeg
      // @todo: If we allow multiple file extensions in the config,
      // we need to determine the mimetype dynamically.
      $app->response()->header('Content-Type', 'image/jpeg');
      $image_path = $result['ImagePath'];
      // We send the image path as a header so it can in turn be sent back by the
      // client in the POST /page request, which will use it as the key in the database update query.
      $app->response()->header('Content-Disposition', 'inline; filename="' . $image_path . '"');
      readfile($image_path);
      // Set the current page image's record to be checked out.
      try {
        $checked_out_query = $db->prepare("UPDATE Pages SET CheckedOut = 1 WHERE Id = :imagepathid");
        $checked_out_query->bindParam(':imagepathid', $result['Id'], PDO::PARAM_INT);
        $checked_out_query->execute();
      }
      catch(PDOException $c) {
        $log = $app->getLog();
        $log->debug($c->getMessage());
      }
      $db = NULL;
    }
    else {
      // Return an HTTP status code of 204, No Content.
      $app->halt(204);
    }
  }
  catch(PDOException $e) {
    $log = $app->getLog();
    $log->debug($e->getMessage());
  }

});

/**
 * Route for POST /page. The request body will contain the OCR transcript.
 * Example request: curl -X POST -H 'Content-Disposition: inline; filename="/home/mark/Documents/apache_thinkpad/docr_images/Hutchinson1794-1-0253.jpg"' --data-binary @test.txt http://thinkpad/docr/server/page
 *
 * @param object $app
 * The global $app object instantiated at the top of this file.
 */
$app->post('/page', function () use ($app) {
  global $config;
  $request = $app->request();
  // Get the transcript from the request body.
  $transcript_contents = $request->getBody();
 
  $request = $app->request();
  $image_path = getImagePathFromHeader($request->headers('Content-Disposition'));
  $log = $app->getLog();
  $log->debug($image_path);
  $transcript_path = getTranscriptPathFromImagePath($image_path);
  $log->debug($transcript_path);

  if (file_put_contents($transcript_path, $transcript_contents)) {
  // Update the file's row in the database (checked out = 0, transcript = path to txt)
    try {
      // Connect to the database and get the next file that doesn't
      // isn't checked out or doesn't have a transcript.
      $db = new PDO('sqlite:' . $config['sqlite3_database_path']);
      $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
      $query = $db->prepare("UPDATE Pages SET CheckedOut = 0, TranscriptPath = :transcriptpath WHERE ImagePath = :imagepath");
      $query->bindValue(':transcriptpath', $transcript_path, PDO::PARAM_STR);
      $query->bindValue(':imagepath', $image_path, PDO::PARAM_STR);
      $query->execute();
    }
    catch(PDOException $e) {
      $log = $app->getLog();
      $log->debug($e->getMessage());
    }
  }

});

$app->run();

/**
 * Functions.
 */

// Input: inline; filename="/home/mark/Documents/apache_thinkpad/docr_images/Hutchinson1794-1-0253.jpg"
// Output: /home/mark/Documents/apache_thinkpad/docr_images/Hutchinson1794-1-0253.jpg
function getImagePathFromHeader($header) {
  $image_path = preg_replace('/^.+?"/', '', $header);
  $image_path = trim($image_path, '"');
  return $image_path;
}

// Input: /home/mark/Documents/apache_thinkpad/docr_images/Hutchinson1794-1-0253.jpg
// Output: /tmp/docr_transcripts/Hutchinson1794-1-0253.txt
function getTranscriptPathFromImagePath($image_path) {
  global $config;
  $image_base_path_pattern = '#' . $config['image_base_dir'] . '#';
  $tmp_path = preg_replace($image_base_path_pattern, $config['transcript_base_dir'], $image_path);
  $path_parts = pathinfo($tmp_path);
  $transcript_path = $path_parts['dirname'] . '/' . $path_parts['filename'] . '.txt';
  return $transcript_path;
}

?>

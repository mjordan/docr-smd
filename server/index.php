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
 * Route for GET /page. 
 *
 * @param object $app
 * The global $app object instantiated at the top of this file.

 @todo: We will need to add a response header that tells the client what
   the file name to save is.

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
 * Route for POST /page. The request body will contain the OCR transcript file plus
 * a 'filename' parameter.
 *
 * @param object $app
 * The global $app object instantiated at the top of this file.
 */
$app->post('/page', function () use ($app) {
  global $config;
  $request = $app->request();
  // Split the 'filename' parameter from the body, which also contains the
  // transcript file, e.g.:
  // filename=bar&Hello from the file.
  // It's an excellent file.
  $filename = strstr($request->getBody(), '&', TRUE);
  $filename = strstr($filename, '=');
  $filename = ltrim($filename, '=');
  // Get the transcript from the request body.
  $transcript = strstr($request->getBody(), '&');
  $transcript = ltrim($transcript, '&');

  if (file_put_contents($config['transcript_base_dir'] . $filename, $transcript)) {
  // Update the file's row in the database (checked out = 0, transcript = path to txt)
    try {
      // Connect to the database and get the next file that doesn't
      // isn't checked out or doesn't have a transcript.
      $db = new PDO('sqlite:' . $config['sqlite3_database_path']);
      $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
      //  @todo: Where do we get the row ID?
      $query = $db->prepare("UPDATE Pages SET CheckedOut = 0, TranscriptPath = :transcriptpath WHERE Id = :imagepathid");
      $query->bindParam('::transcriptpath', $result['Id'], PDO::PARAM_STR);
      $query->bindParam(':imagepathid', $result['Id'], PDO::PARAM_INT);
      $query->execute();
    }
    catch {

    }
  }

});

$app->run();

/**
 * Functions.
 */

?>

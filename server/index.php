<?php

/**
 * docr/smd page server. https://github.com/mjordan/docr-smd.
 *
 * Written in the Slim micro-framework, slimframework.com.
 * 
 * Distributed under the MIT License, http://opensource.org/licenses/MIT.
 */

// Get the plugin config file and provide a default value for
// $plugins if the file cannot be loaded.
require 'config.php';

// Slim setup.
require 'lib/Slim/Slim.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

/**
 * Slim hook that fires before every request.
 *
 * If $tokens is not empty, all clients must send an X-Auth-Key
 * request header containing a valid key.
 *
 * @param object $app
 * The global $app object instantiated at the top of this file.
 */
$app->hook('slim.before', function () use ($app) {
  global $tokens;
  if (count($tokens)) {
    $request = $app->request();
    if (!in_array($request->headers('X-Auth-Key'), $tokens)) {
      $app->halt(403);
    }
  }

  // @todo: Add check for 'network/cluster/grid' (let's keep 'swarm' out of it)
  // mode here and if it is TRUE, issue a $app->response()->redirect($someotherdocrserver, 303)?
  // $app->response()->redirect('http://www.lib.sfu.ca', 303);
  // $app->stop();

});

/**
 * Route for GET /page. Sample request: curl -o test.jpg http://thinkpad/docr/server/page
 * An OCR client would need to get the filename in the Content-Disposition header returned
 * in the docr/smd response and keep track of it for returning in the POST request containing
 * the OCR output; the client can save the file locally for processing using whatever name
 * or path it wants (test.jpg is just an example).
 *
 * @param object $app
 * The global $app object instantiated at the top of this file.
 */
$app->get('/page', function () use ($app) {
  global $config;
  global $image_mime_types;
  $request = $app->request();

  try {
    // Connect to the database and get the next file that doesn't
    // isn't checked out and doesn't have a transcript.
    $db = new PDO('sqlite:' . $config['sqlite3_database_path']);
    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $query = $db->prepare("SELECT Id, ImagePath FROM Pages WHERE CheckedOut = 0 AND TranscriptPath = '' LIMIT 1");
    $query->execute();
    $result = $query->fetch();
    if ($result) {
      // Get the mime type for the image from the $image_mime_types array.
      $image_extension = pathinfo($result['ImagePath'], PATHINFO_EXTENSION);
      $image_path = $result['ImagePath'];
      $app->response()->header('Content-Type', $image_mime_types[$image_extension]);
      // Get the docr server's URL so we can pass it back to the client.
      // Otherwise, the client won't know what server to POST the transcript
      // to if it redirected to a server in swarm mode.
      $env = $app->environment();
      $docr_server_url = $env['slim.url_scheme'] . '://' . $env['SERVER_NAME'] . $env['SCRIPT_NAME'] . $env['PATH_INFO'];
      $app->response()->header('X-docr-Server-URL', $docr_server_url);
      // Check to see if file exists and if it doesn't, return a 204.
      if (file_exists($image_path)) {
        // We send the image path as a header so it can in turn be sent back by the
        // client in the POST /page request, which will use it as the key in the database update query.
        $log = $app->getLog();
        $log->debug($image_path);
        $app->response()->header('Content-Disposition', 'inline; filename="' . $image_path . '"');
        readfile($image_path);
      }
      else {
        // Return an HTTP status code of 204, No Content.
        $app->halt(204);
      }
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
 *  The global $app object instantiated at the top of this file.
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

  // Create subdirtories under the transcript base directory if they don't exist.
  $path_parts = pathinfo($transcript_path);
  if (!file_exists($path_parts['dirname'])) {
    mkdir($path_parts['dirname'], 0777, TRUE);
  }

  // Write out the transcript returned from the client.
  if (file_put_contents($transcript_path, $transcript_contents)) {
    // Update the file's row in the database (checked out = 0, transcript = path to txt)
    try {
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
  // If the config option to delete the image is set to TRUE, do so.
  // @todo: Remove the images directory if it is empty.
  if ($config['delete_images']) {
    unlink($image_path);
  }
});

// Run the Slim app.
$app->run();

/**
 * Functions.
 */

/**
 * Extracts a full filepath from the Content-Dispostion header sent by the
 * client in the POST /page request.
 *
 * @param string $header
 *  The raw Content-Disposition HTTP request header. For example:
 *  inline; filename="/home/mark/Documents/apache_thinkpad/docr_images/Hutchinson1794-1-0253.jpg"
 *
 * @return string
 *  The full path to the image being processed. For example:
 * /home/mark/Documents/apache_thinkpad/docr_images/Hutchinson1794-1-0253.jpg
 */
function getImagePathFromHeader($header) {
  $image_path = preg_replace('/^.+?"/', '', $header);
  $image_path = trim($image_path, '"');
  return $image_path;
}

/**
 * Creates a path to a transcript from a image path.
 *
 * @param string $image_path
 *  The full path to the image being processed. For example:
 *  /home/mark/Documents/apache_thinkpad/docr_images/Hutchinson1794-1-0253.jpg
 *
 * @return string
 *  The full path to the transcript file corresponding to the image. For example:
 *  /tmp/docr_transcripts/Hutchinson1794-1-0253.txt
 */
function getTranscriptPathFromImagePath($image_path) {
  global $config;
  // Replace the image base directory configuration value with the
  // transcript base directory configuration value.
  $image_base_path_pattern = '#' . $config['image_base_dir'] . '#';
  $tmp_path = preg_replace($image_base_path_pattern, $config['transcript_base_dir'], $image_path);
  $path_parts = pathinfo($tmp_path);
  $transcript_path = $path_parts['dirname'] . DIRECTORY_SEPARATOR . $path_parts['filename'] . '.txt';
  return $transcript_path;
}

?>

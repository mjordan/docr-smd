# docr/smd server

## Overview

The docr/smd page server provides a simple REST API that 1) allows docr clients to request page images and 2) receives the output of the OCR process, i.e., text transcripts of the page images. It is written in the Slim PHP Microframework (http://www.slimframework.com/) and is easy to install and configure. The only requirements on the server side are PHP 5.3.0 and SQLite 3 (and of course, PHP must be configured to connec to SQLite).

The REST endpoint for page image requests is GET /page. The endpoint for returning text transcript files is PUT /page.

## Setup

1. Unzip the docr/smd package and put the contents of the 'server' directory under your web server's web root.
2. Configure docr/sdm by editing the config.php file. Refer to inline documentation in that file for details.
3. Put page image files in the directory identified in the configuration file's $config[''image_base_dir'] variable. This directory need not be under the web server's web root. Images can be arranged in subdirectories.
4. Run the queue managager by issuing the following command: 'php queue_manager.php load'. You should see output indicating that the images in the image base directory have been loaded into the queue.
5. At this point the page server is ready. Before you test the server using the Python client, it is important hat the SQLite database file is writable by both the web server's user and the user who will run queue_manager.php to maintain the queue.
6. Test your server by running the Python client, supplying your docr page server's full URL as a paramter, e.g. http://example.com/docr/server/page.

## Maintaining the docr/smd queue

The queue manager populates the queue, lists items in the queue, and purges the queue of items that have transcripts. A typical workflow for maintaining the queue is to copy image files into the image base directory, run 'php queue_manager.php load', wait until clients have performed their work, and run 'queue_manager.php purge'. All of these tasks can be performed manually or by a timed job. You can configure the server to delete page images after they are processed, so all you need to do is make sure that new images are being added to the queue.



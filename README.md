# docr/smd 

## Overview

docr/smd stands for "Distributed OCR over a Swarm of Mobile Devices." Its purpose is to distribute the work of performing Optical Character Recognition (OCR) to a large number of clients. It does this by providing a server application (called a 'page server') that responds to a client's request for a page image in the form of a JPEG, TIFF, or other image format. The page server provides the client with the image, the client performs the OCR on the image, and sends the resulting text transcript back to the server. 

The power of docr/smd is that is can respond to requests from many clients in a short period of time, in effect parallelizing the OCR task. The technical environment of docr/smd clients is independed of the page server's. Currently, docr/smd provides a Python client, but the 'm' in 'smd' stands for 'mobile': Android and iOS applications are possible because the open source Tesseract OCR engine (https://code.google.com/p/tesseract-ocr/) has been ported to both platforms. These mobile clients are currently in the planning stages. It is likely the Android client will be completed first.

## Technical architecture

The docr/smd server maintains a queue of page images that need to be OCRed. Clients periodically query the page server's REST interface for page images, perform the OCR, and POST the resulting transcript back to the server. The server is a simple PHP application that uses SQLite to maintain its page queue. Client requests and the server's responses make heavy use of HTTP headers to supplement the REST API.

The page queue is loaded and purged by a PHP script called the queue manager, which can be run as a command-line script or as a cron job. It provides options to load the queue, list items in the queue, and purge the queue.

docr/smd has a 'peer' mode that redirects client requests to other page servers if the local server doesn't have any images to process. This ability can vastly increase the number of potential clients available to a given server, and also reduces the likelihood that clients remain idle.

## Client/server interaction

The details of the client/server interaction are as follows:

1. The docr/smd client issues a GET request to the server.
2. The server 'checks out' (flags that the image is currently being processed) the next image in its queue for OCRing and returns it to the client.
3. The server also sends the image's filesystem path to the client, which is used later as a key to update the docr page queue.
4. The client performs OCR on image, and sends transcript back to the page server via a POST; it also sending a header containing the original image's filesysmtem path.
5. The server saves the OCR transcript to disk, updates the queue database with the location of the transcript, and, optionally, deletes the original image file.

## Deployment

The docr/ocr server is easy to install and configure. The only requirements on the server are PHP 5.3 and SQLite. Local settings such as paths to page image and transcript directories, access control via IP whitelisting and client API tokens, and URLs of peer servers are configured in a single file, config.php.

Details on installing and configuring the page server are provided in the README.md file in the 'server' directory. The Python client requires that the Tesseract OCR engine (https://code.google.com/p/tesseract-ocr/) and the Python requests (http://www.python-requests.org/) library be installed.

Details on deploying the queue manager are provided in the server/README.md file.

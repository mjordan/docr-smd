# docr/smd Clients

## Overview

Intended clients are mobile devices. There is a Tesseract library for both Android and iOs platforms.

## Client/server interaction

Client issues a GET request to the server; server 'checks out' an image for OCRing and returns it to the client. Server also sends the image's filesystem path, which is used later as a key to update the docr page queue.

Client performs OCR on image, then sends transcript back to the page server, also sending a header containing the original image's filesysmtem path.

Server saves the OCR transcript to disk, and updates the queue database with the location of the transcript.

That's it.

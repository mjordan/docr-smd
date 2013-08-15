# README for the sample Python docr client.

## Overview

This client is intended to illustrate the high-level functionality of docr/smd clients, but it can also be used to to test docr page servers. In fact, there is no reason that the Python client can't be used in production.

## Installing and using the Python client

1) Install the Tesseract command-line application for your platform.
2) Install the Requests Python library (http://www.python-requests.org/).

Run the client and provide the URL of a working docr/smd page server, e.g., ./docr_client.py http://example.com/docr/server/page. That's it. Create a cron job to run the client and it will perform OCR until the server, and its peers if the server is configured to use them, run out of pages to process. If there are no more pages left to OCR, the client simply exits and waits for the next run of the cron job.


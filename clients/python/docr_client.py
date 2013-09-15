#!/usr/bin/env python

# Sample docr/smd intended to illustrate client/server interaction.
# and to assist in testing the page server.
#
# docr/smd page server. https://github.com/mjordan/docr-smd.
#
# Usage: ./docr_client.py http://some.docr.server/page
#
# Copyright 2013 Mark Jordan.
#
# Distributed under the MIT License, http://opensource.org/licenses/MIT.

import os 
import sys
import requests
import subprocess
from PIL import Image
from StringIO import StringIO

# Path to the OCR engine executable.
ocr_engine = "/usr/bin/tesseract"

# Bail if no docr server URL was provided.
if (len(sys.argv) < 2):
  print "Sorry, you need to provide the URL to a docr server"
  sys.exit()

# If we've made it this far, grab the docr server URL from the command line.
docr_server = sys.argv[1]
# A key is required only if the docr server is configured to use them.
rest_key = ''

# Get an image and save it to disk for processing.
headers = {'X-Auth-Key': rest_key}
r = requests.get(docr_server, headers=headers)

# If the docr page server doesn't have any images left, it returns
# a 204 No Content response code.
if r.status_code == 200:
  i = Image.open(StringIO(r.content))
  i.save('temp.jpg')
else:
  print "Sorry, docr page server at %s is reporting a '%d' response (%s)." \
    % (docr_server, r.status_code, r.reason)
  sys.exit()

# If we've made it this far, get the Content-Disposition header 
# from the docr page server so we can send it back to the server
# with the OCR transcript.
image_file_path = r.headers['Content-Disposition']

# Get the docr server's URL. This may be different than the value used
# above, if the client was redirected.
docr_server = r.headers['X-docr-Server-URL']

# Remove 'inline; filename=' from beginning of Content-Disposition value.
image_file_path = image_file_path[17:]
image_file_path = image_file_path.strip('"')

# Run the image through Tesseract.
subprocess.check_call([ocr_engine, "./temp.jpg", "./temp"])

# Now that we have an OCR transcript file (temp.txt), issue a PUT
# request back to the docr server. We include the transcript file
# as the request body and also include a Content-Disposition header
# containing image_file_path.
transcript = open('./temp.txt', 'rb')
headers = {'X-Auth-Key': rest_key}
headers = {'Content-Disposition': image_file_path}
r = requests.put(docr_server, data=transcript, headers=headers)

# Clean up temporary files.
os.remove("./temp.jpg")
os.remove("./temp.txt")

# That's it.


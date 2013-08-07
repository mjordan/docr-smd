#!/usr/bin/env python

# Sample docr/smd intended to illustrate client/server interaction.

import os 
import sys
import requests
import subprocess
from PIL import Image
from StringIO import StringIO

docr_server = 'http://thinkpad/docr/server/page'

# Get an image and save it to disk for processing.
r = requests.get(docr_server)
# If the docr page server doesn't have any images left,
# it returns a 204 No Content response code.
if r.status_code != 204:
  i = Image.open(StringIO(r.content))
  i.save('temp.jpg')
else:
  print "Sorry, docr page server at %s is reporting 'No content'" % docr_server
  sys.exit()

# If we've made it this far, get the Content-Disposition header 
# from the docr page server so we can send it back to the server
# with the OCR transcript.
image_file_path = r.headers['Content-Disposition']
# Remove 'inline; filename=' from beginning of Content-Disposition value.
image_file_path = image_file_path[17:]
image_file_path = image_file_path.strip('"')
print image_file_path

# Run the image through Tesseract.
subprocess.check_call(["/usr/bin/tesseract", "./temp.jpg", "./temp"])

# Now that we have an OCR transcript file (temp.txt), issue a POST
# request back to the docr server. We include the transcript file
# as the request body and also include a Content-Disposition header
# containing image_file_path.
transcript = open('./temp.txt', 'rb')
headers = {'Content-Disposition': image_file_path}
r = requests.post(docr_server, data=transcript, headers=headers)

# Clean up temporary files.
os.remove("./temp.jpg")
os.remove("./temp.txt")

# That's it.


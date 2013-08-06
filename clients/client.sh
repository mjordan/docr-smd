#!/bin/bash

# Bash script that acts as a simple OCR client.

pages=(Hutchinson1794-1-0253.jpg  Hutchinson1794-1-0255.jpg  Hutchinson1794-1-0257.jpg  Hutchinson1794-1-0259.jpg)

for page in "${pages[@]}"
do
  echo Processing /home/mark/Documents/apache_thinkpad/docr_images/$page
  tesseract /home/mark/Documents/apache_thinkpad/docr_images/$page /tmp/$page -psm 6
  curl -X POST -d "filename=$page.txt" --data-binary @/tmp/$page.txt  http://thinkpad/docr/server/page
  # rm -f /tmp/$page.txt
done

eXtplorer Pre-Installer Instructions:

1. Open preinstall.php in a text editor and set the password by editing
   the $passPhrase = ""; line e.g. to
   $passPhrase = "my secret passphrase";

2. Upload preinstall.php to your website to the folder where the eXtplorer
   should be installed, e.g. to /extplorer if you want that your eXtplorer
   will be installed at http://example.com/extplorer/.
         
   Note: http://example.com is just an example. 
         The address will be different in your case.
   
3. Browse to http://example.com/preinstall.php to start the pre-installer.

4. Enter there your password (e.g. "my secret passphrase" without the 
   quotes) and follow the instructions.

5. Once your eXtplorer is extracted on the server, you can start using it.

6. Remove the preinstall.php script from your server since it is a 
   potential security risk if it is left on the server.

Note: This pre-installer just transfers and extracts eXtplorer to your 
      webserver. It is a convenient and fast alternative to uploading
      all the extracted files yourself with an FTP program.
      If you have SSH / shell access, we recommend extracting the archive
      yourself of course.

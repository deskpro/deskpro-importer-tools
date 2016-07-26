Kayako importer script
======================

This tool will connect to your Kayako database and export your data in the standard DeskPRO Import Format.

After this tool completes, you will run the standard DeskPRO import process to save the data to your live helpdesk.

* download https://github.com/DeskPRO/deskpro-importer-tools/archive/master.zip
* unzip deskpro-importer-tools-master.zip
* mv deskpro-importer-tools-master /path/to/deskpro/bin/deskpro-importer-tools
* Edit the values in the CONFIG in /path/to/deskpro/bin/deskpro-importer-tools/importers/kayako/config.php.
* Run the import process to fetch all of your data from Kayako:

$ cd /path/to/deskpro

$ bin/import kayako

* You can now optionally verify the integrity of your data:

$ bin/import verify

* When you're ready, go ahead and apply the import to your live database:

$ bin/import apply

* And finally, you can clean up the temporary data files from the filesystem:

$ bin/import clean

Kayako Importer Script
======================

This tool will connect to your Kayako database and export your data in the standard DeskPRO Import Format.

After this tool completes, you will run the standard DeskPRO import process to save the data to your live helpdesk.

**Setup**

* Download https://github.com/DeskPRO/deskpro-importer-tools/archive/master.zip
* Unzip `deskpro-importer-tools-master.zip`
* Move `deskpro-importer-tools-master` into DeskPRO's `/bin/` directory
* Edit the config values in the `/path/to/deskpro/bin/deskpro-importer-tools/importers/kayako/config.php`

**Import Data**

Run the import process to fetch all of your data from Kayako:

    $ cd /path/to/deskpro
    $ php bin/import kayako

You can now optionally verify the integrity of your data:

    $ php bin/import verify

When you're ready, go ahead and apply the import to your live database:

    $ php bin/import apply

And finally, you can clean up the temporary data files from the filesystem:

    $ php bin/import clean

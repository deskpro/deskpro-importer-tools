Spiceworks importer script
==========================

This tool will connect to your Spiceworks database and export your data in the standard DeskPRO Import Format.

After this tool completes, you will run the standard DeskPRO import process to save the data to your live helpdesk.

**What does it import?**

* Agents (Staff)
* Users
* Tickets
* Ticket Messages
* Ticket (Agent) Notes
* Ticket Message Attachments

**Setup**

* Download https://github.com/DeskPRO/deskpro-importer-tools/archive/master.zip
* Unzip `deskpro-importer-tools-master.zip`
* Move `deskpro-importer-tools-master` into DeskPRO's `/bin/` directory
* Rename the config file from `/path/to/deskpro/bin/deskpro-importer-tools-master/importers/spiceworks/config.dist.php` to `/path/to/deskpro/bin/deskpro-importer-tools-master/importers/spiceworks/config.php`
* Edit the config values in the `/path/to/deskpro/bin/deskpro-importer-tools-master/importers/spiceworks/config.php`

**Import Data**

Run the import process to fetch all of your data from Spiceworks:

    $ cd /path/to/deskpro
    $ php bin/import spiceworks

You can now optionally verify the integrity of your data:

    $ php bin/import verify

When you're ready, go ahead and apply the import to your live database:

    $ php bin/import apply

And finally, you can clean up the temporary data files from the filesystem:

    $ php bin/import clean

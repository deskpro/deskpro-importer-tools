Spiceworks importer script
==========================

This tool will connect to your Spiceworks database and export your data in the standard Deskpro Import Format.

After this tool completes, you will run the standard Deskpro import process to save the data to your live helpdesk.

**What does it import?**

* Agents (Staff)
* Users
* Tickets
* Ticket Messages
* Ticket (Agent) Notes
* Ticket Message Attachments

**Setup**
* Create a new directory titlted `importer` in your `deskpro/config` directory
* Download https://github.com/deskpro/deskpro-importer-tools/blob/master/importers/spiceworks/config.dist.php to this folder
* Rename the config file from `/path/to/deskpro/config/importer/config.dist.php` to `/path/to/deskpro/config/importer/spiceworks.php`
* Edit the config values in the `/path/to/deskpro/config/importer/spiceworks.php`

**Import Data**

Run the import process to fetch all of your data from Spiceworks:

    $ cd /path/to/deskpro
    $ php bin/console dp:import spiceworks

You can now optionally verify the integrity of your data:

    $ php bin/console dp:import:verify

When you're ready, go ahead and apply the import to your live database:

    $ php bin/console dp:import:apply

And finally, you can clean up the temporary data files from the filesystem:

    $ php bin/console dp:import:clean

ZenDesk importer script
=======================

This tool will connect to your ZenDesk account and export your data in the standard DeskPRO Import Format.

After this tool completes, you will run the standard DeskPRO import process to save the data to your live helpdesk.

**What does it import?**

* Organizations
* Agents (Staff)
* Users
* Tickets
* Ticket Messages
* Ticket Message Attachments
* Ticket (Agent) Notes
* Help Center (Categories & Articles)

**Setup**

* Download https://github.com/DeskPRO/deskpro-importer-tools/archive/master.zip
* Unzip `deskpro-importer-tools-master.zip`
* Move `deskpro-importer-tools-master` into DeskPRO's `/bin/` directory
* Edit the config values in the `/path/to/deskpro/bin/deskpro-importer-tools-master/importers/zendesk/config.php`

**Import Data**

Run the import process to fetch all of your data from ZenDesk:

    $ cd /path/to/deskpro
    $ php bin/import zendesk

You can now optionally verify the integrity of your data:

    $ php bin/import verify

When you're ready, go ahead and apply the import to your live database:

    $ php bin/import apply

And finally, you can clean up the temporary data files from the filesystem:

    $ php bin/import clean

Zendesk importer script
=======================

This tool will connect to your Zendesk account and export your data in the standard DeskPRO Import Format.

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

**Via Command Line**

Prepare your importer config:

* Rename the config file from `/path/to/deskpro/config/importer/zendesk.dist.php` to `/path/to/deskpro/config/importer/zendesk.php`
* Edit the config values in the `/path/to/deskpro/config/importer/zendesk.php`

Run the import process to fetch all of your data from Zendesk:

    $ cd /path/to/deskpro
    $ php bin/console dp:import zendesk

You can now optionally verify the integrity of your data:

    $ php bin/console dp:import:verify

When you're ready, go ahead and apply the import to your live database:

    $ php bin/console dp:import:apply

And finally, you can clean up the temporary data files from the filesystem:

    $ php bin/console dp:import:clean

**Via Admin Interface**

* Navigate `Admin > Apps > Importer` in the Admin Interface.
* Select importer type `Zendesk`.
* Fill the config form and start the import process.

Kayako Importer Script
======================

This tool will connect to your Kayako database and export your data in the standard DeskPRO Import Format.

After this tool completes, you will run the standard DeskPRO import process to save the data to your live helpdesk.

**What does it import?**

* Organizations (name, website, phone numbers, fax numbers, addresses)
* Agents (Staff)
* Usergroups
* Users (name, email, organization, organization position, is_disabled, phone)
* Tickets (status, subject, person, agent, department)
* Ticket Messages
* Ticket (Agent) Notes
* Knowledgebase (Categories & Articles)
* News

**Via Command Line**

Prepare your importer config:

* Rename the config file from `/path/to/deskpro/config/importer/kayako.dist.php` to `/path/to/deskpro/config/importer/kayako.php`
* Edit the config values in the `/path/to/deskpro/config/importer/kayako.php`

Run the import process to fetch all of your data from Kayako:

    $ cd /path/to/deskpro
    $ php bin/console dp:import kayako

You can now optionally verify the integrity of your data:

    $ php bin/console dp:import:verify

When you're ready, go ahead and apply the import to your live database:

    $ php bin/console dp:import:apply

And finally, you can clean up the temporary data files from the filesystem:

    $ php bin/console dp:import:clean

**Via Admin Interface**

* Navigate `Admin > Apps > Importer` in the Admin Interface.
* Select importer type `Kayako`.
* Fill the config form and start the import process.

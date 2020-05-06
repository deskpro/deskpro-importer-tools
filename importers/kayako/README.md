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
* Ticket Attachments
* Knowledgebase (Categories & Articles)
* News
* Chat Conversations

**Via Command Line**

Prepare your importer config:

Create the config file:

    $ cd /path/to/deskpro/app/BUILD/modules/importer-tools/importers/kayako
    $ cp config.dist.php config.php

Edit the config values in the `/path/to/deskpro/app/BUILD/modules/importer-tools/importers/kayako/config.php`

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

**Import attachments from filesystem**

We can only import ticket attachments if they are stored in the database, i.e. `swattachmentchunks` table.

So if you use filesystem for your ticket attachments you need to migrate them into your Kayako database first before running the import process.
We've created a script that will help you to do that. To run the script you need to follow these steps:

Create the config file:

    $ cd /path/to/deskpro/app/BUILD/modules/importer-tools/importers/kayako
    $ cp config.dist.php config.php

Edit the config values in the `/path/to/deskpro/app/BUILD/modules/importer-tools/importers/kayako/config.php`.

Copy `migrate_attachments.php` and `config.php` to the Kayako root dir.

Run the migration script:

    $ php migrate_attachments.php

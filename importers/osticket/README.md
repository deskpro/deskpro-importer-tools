OsTicket importer script
========================

This tool will connect to your OsTicket database and export your data in the standard DeskPRO Import Format.

After this tool completes, you will run the standard DeskPRO import process to save the data to your live helpdesk.

**What does it import?**

* Organizations (name, custom form fields)
* Agents (Staff)
* Usergroups
* Users (name, email, organization, contact data, custom form fields)
* Tickets (status, subject, person, agent, department, custom form fields)
* Ticket Messages
* Ticket (Agent) Notes
* Ticket Message Attachments
* Knowledgebase (Categories & Articles)

**Setup**

* Rename the config file from `/path/to/deskpro/config/importer/osticket.dist.php` to `/path/to/deskpro/config/importer/osticket.php`
* Edit the config values in the `/path/to/deskpro/config/importer/osticket.php`

**Import Data**

Run the import process to fetch all of your data from OsTicket:

    $ cd /path/to/deskpro
    $ php bin/console dp:import osticket

You can now optionally verify the integrity of your data:

    $ php bin/console dp:import:verify

When you're ready, go ahead and apply the import to your live database:

    $ php bin/console dp:import:apply

And finally, you can clean up the temporary data files from the filesystem:

    $ php bin/console dp:import:clean

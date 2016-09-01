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

* Download https://github.com/DeskPRO/deskpro-importer-tools/archive/master.zip
* Unzip `deskpro-importer-tools-master.zip`
* Move `deskpro-importer-tools-master` into DeskPRO's `/bin/` directory
* Edit the config values in the `/path/to/deskpro/bin/deskpro-importer-tools-master/importers/osticket/config.php`

**Import Data**

Run the import process to fetch all of your data from OsTicket:

    $ cd /path/to/deskpro
    $ php bin/import osticket

You can now optionally verify the integrity of your data:

    $ php bin/import verify

When you're ready, go ahead and apply the import to your live database:

    $ php bin/import apply

And finally, you can clean up the temporary data files from the filesystem:

    $ php bin/import clean

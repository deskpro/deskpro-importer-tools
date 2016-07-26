Spiceworks importer script
==========================

This tool will connect to your SpiceWorks database and export your data into the data/ directory in
the standard DeskPRO Import Format.

After this tool completes, you will run the standard DeskPRO import process to save the data to
your live helpdesk. Refer to the README for more details.

1) Edit the values in the CONFIG section below.

2) Run this script from the command-line:

    $ cd /path/to/deskpro-importer-tools
    $ php spiceworks.php

3) Run the DeskPRO import script
    $ cd /path/to/deskpro
    $ php cmd.php dp:import:run json --input-path "/path/to/deskpro-importer-tools/data"

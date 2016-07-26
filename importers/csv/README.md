CSV importer script
===================

This tool is a half-completed script that will open a CSV file.

1) Edit the values in the CONFIG section below.

2) You will need custom code in the "CUSTOM CODE" section to handle what to do with each row of your file.

3) Run this script from the command-line:

    $ cd /path/to/deskpro-importer-tools
    $ php csv.php

3) Run the DeskPRO import script
    $ cd /path/to/deskpro
    $ php cmd.php dp:import:run json --input-path "/path/to/deskpro-importer-tools/data"

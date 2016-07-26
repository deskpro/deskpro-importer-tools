## Download the importer tool: [Click here to download](https://github.com/DeskPRO/deskpro-importer-tools/archive/master.zip)

---

This repository contains a tools that help you connect to various types of databases/products to download
data and save it in a standard format that the DeskPRO importer can understand. This allows you to use the importer to import virtually any product into your live DeskPRO helpdesk.

The process of importing data is a two-step process:

  1. You first run a tool from this repository that connects to your database/product to download its data. These tools will download and format your data into a standard file format that the importer can read.
  2. Then you run the DeskPRO Importer to import it into your real, live DeskPRO helpdesk.


## Running the tools

Each file in this directory is a separate tool designed to work with a specific database/product. Just
open the file and read the instruction at the top of the file. Often you will need to insert a few
configuration options before running the tool.

After you have successfully run one of the export tools, the `data/` directory will be populated with many
files (perhaps thousands or hundreds of thousands, depending on how much data you are importing).

After that, you need to run the special DeskPRO command from the folder on your server that has DeskPRO.

Here's an example putting it all togehter:

    cd /path/to/deskpro-importer-tools
    php mytool.php

    # this will run and populate the data/ directory with JSON files

    cd /path/to/deskpro
    bin/console dp:import:run json --input-path "/path/to/deskpro-importer-tools/data"

---

### Example: SpiceWorks

Here's a full example for SpiceWorks:

Step 1: Open spiceworks.php and edit the few configuration options.

Step 2: Run the SpiceWorks tool from the command-line:

    $ cd /path/to/deskpro-importer-tools
    $ php spiceworks.php
    Exporting tickets and messages ...
    Exporting users ...
    All done.

Step 3: Run DeskPRO's import command:

    $ cd /path/to/deskpro
    $ php cmd.php dp:import:run json --input-path "/path/to/deskpro-importer-tools/data"


---


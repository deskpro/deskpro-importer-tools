DeskPRO features an importer tool that makes it easy to import large amounts of data into an active helpdesk. The
importer tool reads data from a directory of files in a standard format. So long as the data is in this standard
format, DeskPRO will be able to import the data.

This repository contains a tools that help you connect to various types of databases/products to download
data and save it in the standard format that the importer can understand.

So the process of importing data is a two-step process:

  1. You first run the tool that connects to your database/product and downloads data and saves it into
  the standard DeskPRO Importer Format.
  2. Then you run the DeskPRO tool that reads the data and installs it into your real, live DeskPRO helpdesk.


# Running the tools

Each file in this directory is a separate tool designed to work with a specific database/product. Just
open the file and read the instruction at the top of the file. Often you will need to insert a few
configuration options before running the tool.

# Running the DeskPRO Importer

After you have successfully run one of the export tools, the `data/` directory will be populated with many
files (perhaps thousands or hundreds of thousands, depending on how much data you are importing).

Now you need to run the DeskPRO tool to import this data. You do this by running the following command from DeskPRO's
root directory:

    php cmd.php dp:import:run json --input-path "path/to/data"

Where the path is the path to the `data/` directory here in this tool directory.

# Example: SpiceWorks

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

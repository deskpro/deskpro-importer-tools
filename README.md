# Importing Data into DeskPRO

We know how important *your* data is; data portability is part of our company's core values. We are, for example, one of the few providers that will provide SaaS customers with a full export of all their data.

The DeskPRO team has put a lot of effort into helping you migrate your data into DeskPRO. You have a number of options on how to proceed.

### 1. I just want to import users and organizations.

DeskPRO includes a tool to upload a CSV of your user data directly from within the admin interface. This works on both On-Premise and Cloud deployments of DeskPRO.

### 2. I'm currently using Kayako, Zendesk or Spiceworks.

We currently provide importers for these 3 products. Please follow the instructions for the product you wish to import your data from.

 1. [Importing my data from Kayako into DeskPRO](https://github.com/DeskPRO/deskpro-importer-tools/blob/master/importers/kayako/README.md)
 2. [Importing my data from Zendesk into DeskPRO](https://github.com/DeskPRO/deskpro-importer-tools/blob/master/importers/zendesk/README.md)
 3. [Importing my data from Spiceworks into DeskPRO](https://github.com/DeskPRO/deskpro-importer-tools/blob/master/importers/spiceworks/README.md)
 
### 3. I'm using a different product.

The DeskPRO importer is a platform that helps simplify writing your own importer. The following [Custom Importer Instructions](https://github.com/DeskPRO/deskpro-importer-tools/blob/master/customer/README.md) will guide you to building your own tool.

Alternatively, The DeskPRO consultancy team can write an import for you. These projects typically range from $2,000 to $20,000 in cost. Please contact sales@deskpro.com for more information.


----------


----------


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


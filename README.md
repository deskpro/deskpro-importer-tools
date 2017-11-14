# Importing Data into DeskPRO

We know how important *your* data is; data portability is part of our company's core values. We are, for example, one of the few providers that will provide SaaS customers with a full export of all their data.

The DeskPRO team has put a lot of effort into helping you migrate your data into DeskPRO. You have a number of options on how to proceed.

### 1. I ONLY want to import users/organizations

If you ONLY want to import users/organizations, then you can use the simplified user importer tool that is built in to DeskPRO. This works on both On-Premise and Cloud deployments of DeskPRO.

[DeskPRO Manual : CSV User Import](https://support.deskpro.com/en_GB/guides/admin-guide/importing-data/csv-user-import)

Note that this is only useful for users/organizations. If you want to import other data, you will need to use one of the other two options described below.

### 2. I'm using Kayako, Zendesk, OsTicket, Spiceworks

We currently provide importers for the following products. Please follow the instructions for the product you wish to import your data from.

 1. [Importing my data from Kayako into DeskPRO](./importers/kayako/README.md)
 2. [Importing my data from Zendesk into DeskPRO](./importers/zendesk/README.md)
 3. [Importing my data from Spiceworks into DeskPRO](./importers/spiceworks/README.md)
 4. [Importing my data from OsTicket into DeskPRO](./importers/osticket/README.md)
 
### 3. I'm using a different product / I need to import custom data

The DeskPRO importer is a platform that helps simplify writing your own importer. The [Custom Importer Instructions](./importers/custom/README.md) will guide you to building your own tool.

**What's invovled?**

Your custom tool must take the data you have and convert it into a series of JSON files on the filesystem so that DeskPRO can import it. Our importer platform makes this easy. You can write your own tool in just a few dozen lines of code. Refer to the [Custom Importer Instructions](./importers/custom/README.md) for examples and details.

**Have us write your importer**

Alternatively, The DeskPRO consultancy team can write an import for you. These projects typically range from $2,000 to $20,000 in cost. Please contact sales@deskpro.com for more information.

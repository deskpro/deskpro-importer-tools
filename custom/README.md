# Creating a custom tool

If you are using a different product that does not have already written importer for that product then you can create your own importer.

### Get started

* Download https://github.com/DeskPRO/deskpro-importer-tools/archive/master.zip
* Unzip `deskpro-importer-tools-master.zip`
* Move `deskpro-importer-tools-master` into DeskPRO's `/bin/` directory
* Create a new PHP file (e.g. copy `custom.php` to `importers/mytool/mytool.php`).
* Run the import process:

```bash
    $ php bin/import mytool
```

Sample data files exist in the `example-data/` directory to show you what format the files are expected to be in. 
Each object in DeskPRO will have 1 corresponding JSON file. For example, all data about a ticket (such as subject, messages, attachments and field data) will all exist in `tickets/some-id.json`.

The filename of the file in the filesystem is arbitrary; it doesn't matter. But obviously the filename needs to be unique per object you want to import, so often the easiest thing to do is use the ID of the object. 
E.g., a ticket with ID 5433 might be a file called `tickets/5433.json`.

Your tool needs to write files to the filesystem with the data you want to import. You just need to make sure the data is in the format you see in the example-data.

```php
$path = __DIR__.'/data/tickets/123.json';
file_put_contents($path, json_encode([
    'department' => 'Sales',
    'person' => 'user@example.com',
    'messages' => [
        [
            'message' => 'Hello',
            'date_created' => '2016-01-01 00:01:01',
            'person' => 'user@example.com'
        ]
    ]
]));
```

To help you write these files, you can use the [special helpers](../inc/Helpers). 

They allow to easily fetch the existing database data and create the json files like in the sample code below:

```php
$pager = $db->getPager('SELECT * FROM users');
foreach ($pager as $n) {
    $writer->writePerson($n['id'], [
        'name'   => $n['name'],
        'emails' => [
            $n['email'],
        ],
        'is_disabled'  => !$n['is_enabled'],
        'organization' => $n['organization_id'],
    ];
}
```

For more examples you can see the existing importer scripts:

 - [kayako.php](../importers/kayako/kayako.php)
 - [osticket.php](../importers/osticket/osticket.php)
 - [spiceworks.php](../importers/spiceworks/spiceworks.php)
 - [zendesk.php](../importers/zendesk/zendesk.php)

### Classes autoload and using third-party libs

Basically the importer script is a self-contained file with a simple data mapping. It connects to the external database, fetches the data and creates the json files.
But sometimes it's pretty hard to write a simple script in a single file or needs to use composer to install third-party components.

Use `importers/mytool/lib` directory for your classes and vendors. See the ZenDesk importer [files structure](../importers/zendesk/lib).

### File structure

If you are not going to build own import tool based on `custom.php` and just want to import existing json files then you need to prepare the following directory structure. The file path template is "{base_path}/{batch_num}/{object_type}/{filename.json}":

```
  example_data
       |--1 (e.g. batch num)
           |--ticket
                 |--ticket1.json
                 |--ticket2.json
                 |--ticket3.json
                 ...
           |--person
                 |--person1.json
                 |--person2.json
                 |--person3.json
           |--article
                 |--article1.json
                 |--article2.json
                 |--article3.json
           ...
       |--2 
           |--ticket 
                 |--ticket101.json 
                 |--ticket102.json 
                 |--ticket103.json
                 ...
       |--3 
           |--ticket 
                 |--ticket201.json 
                 |--ticket202.json 
                 |--ticket203.json 
                 ...
```

### How to run import

You can now optionally verify the integrity of your data:

`$ php bin/import verify --input-path=/path/to/your/json/files/base/path`

When you're ready, go ahead and apply the import to your live database:

`$ php bin/import apply --input-path=/path/to/your/json/files/base/path`

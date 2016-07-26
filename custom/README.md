# Creating a custom tool

Create a new PHP file (e.g. copy `custom.php` to `mytool.php`). Your tool must write JSON files to the `data/` directory for all data you want DeskPRO to import.

Sample data files exist in the `example-data/` directory to show you what format the files are expected to be in. Each object in DeskPRO will have 1 corresponding JSON file. For example, all data about a ticket (such as subject, messages, attachments and field data) will all exist in `tickets/some-id.json`.

The filename of the file in the filesystem is arbitrary; it doesn't matter. But obviously the filename needs to be unique per object you want to import, so often the easiest thing to do is use the ID of the object. E.g., a ticket with ID 5433 might be a file called `tickets/5433.json`.

Your tool needs to write files to the filesystem with the data you want to import. You just need to make sure the data is in the format you see in the example-data.

    $path = __DIR__.'/data/tickets/123.json';
    file_put_contents($path, json_encode([
        'id' => '123',
        'department' => 'Sales',
        'person' => 'user@example.com',
        'message' => [
            [
                'message_text' => 'Hello',
                'date_created' => '2016-01-01 00:01:01',
                'person' => 'user@example.com'
            ]
        ]
    ]));

To help you write these files, you can use the special "writer":

    $writer = create_writer();
    $writer->ticket([
        'id' => '123',
        'department' => 'Sales',
        'person' => 'user@example.com',
        'message' => [
            [
                'message_text' => 'Hello',
                'date_created' => '2016-01-01 00:01:01',
                'person' => 'user@example.com'
            ]
        ]
    ]);

The writer helps write files a bit easier, and will help create related objects. For example, this examlpe ticket has a person with an email address user@example.com. The writer will also create a corresponding `people/record.json` file for the user record automatically so you don't have to.

If you call the writer on an object multiple times, data is merged, allowing you to progressively "build up" data files in multiple steps. For instance, maybe in step 1 you want to generate a list of tickets, then in step 2 you want to download messages for each ticket, then in step 3 you add attachments, etc. The writer will let you do this easily because each time you `$writer->ticket(['id' => 123, ...])`, the data from the previous write is kept and merged with whatever new data you want to add.

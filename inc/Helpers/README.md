# Importer helpers

- [Attachment helper](#attachment-helper)
- [Writer helper](#writer-helper)
- [Database helper](#database-helper)
- [Format helper](#format-helper)
- [Output helper](#output-helper)

***

### Attachment helper

Common methods to work with attachments.

Add the following code to your importer script to inject the helper:

```php
use DeskPRO\ImporterTools\Helpers\AttachmentHelper;
$loader = AttachmentHelper::getHelper();
```

**loadAttachment($url)**

Downloads the attachment by the provided url and returns the encoded data in base64 format.

Returns the attachment encoded `string` or `null` on failure.

***

### Database helper

Works with relational databases (e.g. MySQL, SQLite). Connects to your database to simplify data fetching.

Add the following code to your importer script to inject the helper:

```php
use DeskPRO\ImporterTools\Helpers\DbHelper;
$db = DbHelper::getHelper();
```

**setCredentials(array $credentials)**

Provide your database credentials to establish the connection before any fetch request. 

`$credentials` - uses Doctrine PDO driver. See http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html to learn more how to set the credentials.

**getPager($query, array $params = [], $perPage = 1000)**

Allows you to iterate through the big database tables.

`$query` - PDO sql query string, you can use data placeholders.
`$params` - placeholder values, you can also set arrays as placeholder value.
`$perPage` - per page rows limit, internal splits db request dynamically adding `LIMIT` and `OFFSET`.

Returns table row `\Iterator` so you do not need to care about data batching by manually adding `LIMIT` and `OFFSET` to your sql queries.

The the following code

```php
$pager = $db->getPager('SELECT * FROM users', [], 500);
foreach ($pager as $row) {
    ...
}
```

will do the following database requests until it fetches whole table data:

```sql
SELECT * FROM users LIMIT 500 OFFSET 0
SELECT * FROM users LIMIT 500 OFFSET 500
SELECT * FROM users LIMIT 500 OFFSET 1000
```

**findOne($query, array $params = [])**

`$query` - PDO sql query string, you can use data placeholders.
`$params` - placeholder values, you can also set arrays as placeholder value.

Returns `array` of the single row or `false` on failure.

**findAll($query, array $params = [])**

`$query` - PDO sql query string, you can use data placeholders.
`$params` - placeholder values, you can also set arrays as placeholder value.

Returns `array` of the whole database table during the single request.

***

### Format helper

Prepares the data to the DeskPRO format. Some of data types should be in the proper format so you can use these methods to ensure that values pass the validation.

Add the following code to your importer script to inject the helper:

```php
use DeskPRO\ImporterTools\Helpers\FormatHelper;
$formatter = FormatHelper::getHelper();
```

**getFormattedNumber($number)**

Transforms and validates your phone number to the DeskPRO phone number format. Can automatically add the country code.

Expected format is `country_code region_code number`.

`$number` - phone number string.

Returns `string` with the formatted phone number or `false` on failure.

**getFormattedUrl($url)**

Transforms and validates your website url, prepends `http://` to the url if does not exist.

`$url` - website url string.

Returns `string` with the formatted url or `false` on failure.

**getFormattedDate($date)**

Transforms date, datetime or timestamp string to ISO 8601 format.

`$date` - date string or timestamp.

Returns `string` with formatted date or current date on failure.

**isEmailValid($email)**

Validates the email address.

`$email` - email address string.

Returns `true` if email is valid otherwise `false`.

***

### Writer helper

Converts the plain arrays into the json files with proper data structure, splits the files by type and batch groups. 
It also validates the object values to ensure you prepared the correct data.

Add the following code to your importer script to inject the helper:

```php
use DeskPRO\ImporterTools\Helpers\WriteHelper;
$writer = WriteHelper::getHelper();
```

Next you can call the helper methods to write the data:
    
```php
$writer->writeTicket($oid, $data);
```

where `$oid` is a unique record identifier in the system you are importing from (will be used as name of the generated json file) and `$data` is the array with the article custom definition data. 
    
```php
$writer->writeTicket(123, [
    'department' => 'Sales',
    'person' => 'user@example.com',
    'messages' => [
        [
            'message' => 'Hello',
            'date_created' => '2016-01-01 00:01:01',
            'person' => 'user@example.com'
        ]
    ]
]);
```

#### writeArticle($oid, array $data)

Creates a json file of article record in the `data/article` dir.

See the [format](../../custom/example-data/article/README.md) and the json file examples with [required only params](../../custom/example-data/article/article.min.json) or with [all params](../../custom/example-data/article/article.all.json).

#### writeArticleCategory($oid, array $data)

Creates a json file of article category record in the `data/article_category` dir.

See the [format](../../custom/example-data/article_category/README.md) and the json file examples with [required only params](../../custom/example-data/article_category/article_category.min.json) or with [all params](../../custom/example-data/article_category/article_category.all.json).

**Note:** If you have flat knowledge base category structure or you don't care about the empty categories you can create categories via the articles by their category names:

```php
$writer->writeArticle(123, [
    ...
    'categories' => [
        'Sample category',
    ],
]);
```
    
If the category with name 'Sample category' does not exist yet then it will be automatically created.

#### writeArticleCustomDef($oid, array $data)

Creates a json file of article custom definition record in the `data/article_custom_def` dir.

See the [format](../../custom/example-data/article_custom_def/README.md) and the json file examples with [required only params](../../custom/example-data/person_custom_def/person_custom_def.min.json) or with [all params](../../custom/example-data/person_custom_def/person_custom_def.all.json).

#### writeDownload($oid, array $data)

Creates a json file of download record in the `data/download` dir.

See the [format](../../custom/example-data/download/README.md) and the json file examples with [required only params](../../custom/example-data/download/download.min.json) or with [all params](../../custom/example-data/download/download.all.json).

**writeFeedback($oid, array $data)**

Creates a json file of feedback record in the `data/feedback` dir.

See the [format](../../custom/example-data/feedback/README.md) and the json file examples with [required only params](../../custom/example-data/feedback/feedback.min.json) or with [all params](../../custom/example-data/feedback/feedback.all.json).

**writeFeedbackCustomDef($oid, array $data)**

Creates a json file of feedback custom definition record in the `data/feedback_custom_def` dir.

See the [format](../../custom/example-data/feedback_custom_def/README.md) and the json file examples with [required only params](../../custom/example-data/person_custom_def/person_custom_def.min.json) or with [all params](../../custom/example-data/person_custom_def/person_custom_def.all.json).

**writeNews($oid, array $data)**

Creates a json file of news post record in the `data/news` dir.

See the [format](../../custom/example-data/news/README.md) and the json file examples with [required only params](../../custom/example-data/news/news.min.json) or with [all params](../../custom/example-data/news/news.all.json).

**writeOrganization($oid, array $data)**

Creates a json file of organization record in the `data/organization` dir.

See the [format](../../custom/example-data/organization/README.md) and the json file examples with [required only params](../../custom/example-data/organization/organization.min.json) or with [all params](../../custom/example-data/organization/organization.all.json).

**writeOrganizationCustomDef($oid, array $data)**

Creates a json file of organization custom definition record in the `data/organization_custom_def` dir.

See the [format](../../custom/example-data/organization_custom_def/README.md) and the json file examples with [required only params](../../custom/example-data/person_custom_def/person_custom_def.min.json) or with [all params](../../custom/example-data/person_custom_def/person_custom_def.all.json).

**writeUser($oid, array $data, $oidWithPrefix = true)**

Creates a json file of user record in the `data/person` dir.

See the [format](../../custom/example-data/person/README.md) and the json file examples with [required only params](../../custom/example-data/person/person.min.json) or with [all params](../../custom/example-data/person/person.all.json).

**writeAgent($oid, array $data, $oidWithPrefix = true)**

Creates a json file of agent record in the `data/person` dir.

See the [format](../../custom/example-data/person/README.md) and the json file examples with [required only params](../../custom/example-data/person/person.min.json) or with [all params](../../custom/example-data/person/person.all.json).

**Note:** Users and agents are based on a single object type `person` and they are located in the same `data/person` dir.

**Note:** Sometimes agent and user records are contained in a single database table and sometimes they have different ones. 
So we have additional option `$oidWithPrefix` that automatically adds prefix for all user `$oids`.

`$writer->writeUser(1, [...], true);` will create a file with name `user_1`. So to refer that user we should use `userOid($oid)` method that will add automatically prefix too.

`$writer->writeAgent(1, [...], true);` will create a file with name `agent_1`. So to refer that agent we should use `agentOid($oid)` method that will add automatically prefix too.

Example:

```php
$pager = $db->getPager('SELECT * from tickets');
foreach ($pager as $n) {
    $writer->writeTicket(1, [
        'subject' => $n['subject'],
        'agent    => $writer->agentOid($n['assignee']),
        'person   => $writer->userOid($n['created_by']),
        
        ...
    ]);
}
```

**writePersonCustomDef($oid, array $data)**

Creates a json file of person custom definition record in the `data/person_custom_def` dir.

See the [format](../../custom/example-data/person_custom_def/README.md) and the json file examples with [required only params](../../custom/example-data/person_custom_def/person_custom_def.min.json) or with [all params](../../custom/example-data/person_custom_def/person_custom_def.all.json).

**writeTicket($oid, array $data)**

Creates a json file of ticket record in the `data/ticket` dir.

See the [format](../../custom/example-data/ticket/README.md) and the json file examples with [required only params](../../custom/example-data/ticket/ticket.min.json) or with [all params](../../custom/example-data/ticket/ticket.all.json).

**writeTicketCustomDef($oid, array $data)**

Creates a json file of ticket custom definition record in the `data/ticket_custom_def` dir.

See the [format](../../custom/example-data/ticket_custom_def/README.md) and the json file examples with [required only params](../../custom/example-data/person_custom_def/person_custom_def.min.json) or with [all params](../../custom/example-data/person_custom_def/person_custom_def.all.json).

**writeTextSnippet($oid, array $data)**

Creates a json file of text snippet record in the `data/text_snippet` dir.

See the [format](../../custom/example-data/text_snippet/README.md) and the json file examples with [required only params](../../custom/example-data/text_snippet/text_snippet.min.json) or with [all params](../../custom/example-data/text_snippet/text_snippet.all.json).

**writeTextSnippetCategory($oid, array $data)**

Creates a json file of text snippet category record in the `data/text_snippet_category` dir.

See the [format](../../custom/example-data/text_snippet_category/README.md) and the json file examples with [required only params](../../custom/example-data/text_snippet_category/text_snippet_category.min.json) or with [all params](../../custom/example-data/text_snippet_category/text_snippet_category.all.json).

**writeChat($oid, array $data)**

Creates a json file of chat record in the `data/chat` dir.

See the [format](../../custom/example-data/chat/README.md) and the json file examples with [required only params](../../custom/example-data/chat/chat.min.json) or with [all params](../../custom/example-data/chat/chat.all.json).

**writeChatCustomDef($oid, array $data)**

Creates a json file of chat custom definition record in the `data/chat_custom_def` dir.

See the [format](../../custom/example-data/chat_custom_def/README.md) and the json file examples with [required only params](../../custom/example-data/person_custom_def/person_custom_def.min.json) or with [all params](../../custom/example-data/person_custom_def/person_custom_def.all.json).

**writeSetting($oid, array $data)**

Creates a json file of setting record in the `data/setting` dir.

See the [format](../../custom/example-data/setting/README.md) and the [json file example](../../custom/example-data/setting/setting.json).

***

### Output helper

Add the following code to your importer script to inject the helper:

```php
use DeskPRO\ImporterTools\Helpers\OutputHelper;
$output = OutputHelper::getHelper();
```

**startSection($title)**

Writes a custom title to console log before section execution process.

`$title` - section header title.

```php
$output->startSection('Users');
$pager = $db->getPager('SELECT * FROM users');
foreach ($pager as $n) {
    ...
}

$output->startSection('Tickets');
$pager = $db->getPager('SELECT * FROM tickets');
foreach ($pager as $n) {
    ...
}
```

**finishProcess()**

Writes the complete process message to console log.

**debug($message)**

Writes custom `debug` message to console log.

`$message` - custom text string.

**info($message)**

Writes custom `info` message to console log.

**warning($message)**

Writes custom `warning` message to console log.

**error($message)**

Writes custom `error` message to console log.

**notice($message)**

Writes custom `notice` message to console log.

**alert($message)**

Writes custom `alert` message to console log.

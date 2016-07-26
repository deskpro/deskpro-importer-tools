Person custom definitions
=========================

### Base fields

| Column name               | Type                | Required | Possible values                                                                                                                                                       | Default | Description                                                                                                                                                                        | Examples                           |
| --------------------------|---------------------|----------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| title                     | string              |  *       |                                                                                                                                                                       |         | Custom definition title.                                                                                                                                                           |                                    |
| description               | string              |          |                                                                                                                                                                       |         | Custom definition description, help block.                                                                                                                                         |                                    |
| widget_type               | string              |  *       | "text" <br/> "textarea" <br/> "toggle" <br/> "date" <br/> "datetime" <br/> "choice" <br/> "multichoice" <br/> "checkbox" <br/> "radio" <br/> "display" <br/> "hidden" |         | Field type.                                                                                                                                                                        |                                    |
| is_enabled                | boolean             |          |                                                                                                                                                                       | true    | Is field active.                                                                                                                                                                   |                                    |
| is_agent_field            | boolean             |          |                                                                                                                                                                       | true    | Only show this field to agents.                                                                                                                                                    |                                    |
| default_value             | string              |          |                                                                                                                                                                       |         | Custom field default value.                                                                                                                                                        |                                    |
| options                   | array               |          |                                                                                                                                                                       | [ ]     | Validation options.                                                                                                                                                                |                                    |
| choices                   | CustomDefChoice[]   |          |                                                                                                                                                                       | [ ]     | Used for choice definitions, contains choice definitions. If widget type is <b>choice</b>, <b>multichoice</b>, <b>checkbox</b> or <b>radio</b> then should be at least one choice. |                                    |

#### CustomDefChoice

For choice type fields you can provide choice hierarchy like:

<pre>
 -- Choice 1
    -- Sub choice 1
        -- Sub choice 1a
        -- Sub choice 1b
    -- Sub choice 2
 -- Choice 2
 -- Choice 3
</pre>

Custom def choice fields are:

| Column name               | Type                | Required | Possible values                                                                                                                                 | Default | Description                                                                                                                                                                        | Examples                           |
| --------------------------|---------------------|----------|-------------------------------------------------------------------------------------------------------------------------------------------------|---------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| title                     | string              |  *       |                                                                                                                                                 |         | Custom definition choice title.                                                                                                                                                    |                                    |
| choices                   | CustomDefChoice[]   |          |                                                                                                                                                 | [ ]     | Hierarchy deep choices. Choice could be parent and has sub choices.                                                                                                                |                                    |


<b>Note:</b> Not always need to export the definition params before the definition values. The custom def could be auto created when try to import custom field values. <br/>

1. If the custom definition does not exist then it will be created with <b>text</b> type.
2. If the custom definition exists and it's one of the choice types (**choice**, **multichoice**, **checkbox** or **radio**) but there are no choices then they will be automatically created by choice path from custom field value.

### Validation options

You can set field validation option as plain associative array. Possible values:

#### Text/Textarea field validation

User options:

| Option name | Type    | Possible values | Default | Description                          | Examples                  |
|-------------|---------|-----------------|---------|--------------------------------------|---------------------------|
| required    | boolean |                 | false   | Require the user to provide a value. | ```{"required": true}```  |
| min_length  | integer |                 |         | Min characters length for users.     | ```{"min_length": 10}```  |
| max_length  | integer |                 |         | Max characters length for users.     | ```{"max_length": 20}```  |
| regex       | string  |                 |         | Match regular expression for users.  | ```{"regex": "[0-9]+"}``` |

Agent options:

| Option name       | Type    | Possible values | Default | Description                           | Examples                        |
|-------------------|---------|-----------------|---------|---------------------------------------|---------------------------------|
| agent_required    | boolean |                 | false   | Require the agent to provide a value. | ```{"agent_required": true}```  |
| agent_min_length  | integer |                 |         | Min characters length for agents.     | ```{"agent_min_length": 10}```  |
| agent_max_length  | integer |                 |         | Max characters length for agents.     | ```{"agent_max_length": 20}```  |
| agent_regex       | string  |                 |         | Match regular expression for agents.  | ```{"agent_regex": "[0-9]+"}``` |


#### Toggle field validation

User options:

| Option name       | Type    | Possible values     | Default | Description                                                   | Examples                              |
|-------------------|---------|---------------------|---------|---------------------------------------------------------------|---------------------------------------|
| validation_type   | string  | "required" or empty |         | The checkbox should checked (is checked validator) for users. | ```{"validation_type": "required"}``` |

Agent options:

| Option name             | Type    | Possible values     | Default | Description                                                    | Examples                                    |
|-------------------------|---------|---------------------|---------|----------------------------------------------------------------|---------------------------------------------|
| agent_validation_type   | string  | "required" or empty |         | The checkbox should checked (is checked validator) for agents. | ```{"agent_validation_type": "required"}``` |


#### Date/DateTime field validation

Common options:

| Option name       | Type    | Possible values     | Default | Description                                             | Examples                                 |
|-------------------|---------|---------------------|---------|---------------------------------------------------------|------------------------------------------|
| date_valid_type   | string  | "date"<br/> "range" |         | Require the user to provide a value.                    | ```{"date_valid_type": "range"}```       |
| date_valid_range1 | integer |                     |         | Number of days before current date (for "range" type).  | ```{"date_valid_range1": 2}```           |
| date_valid_range2 | integer |                     |         | Number of days after current date (for "range" type).   | ```{"date_valid_range2": 2}```           |
| date_valid_date1  | integer |                     |         | Min date in datetime format (for "date" type).          | ```{"date_valid_date1": "2016-07-25"}``` |
| date_valid_date2  | integer |                     |         | Max date in datetime format (for "date" type).          | ```{"date_valid_date2": "2016-07-30"}``` |

User options:

| Option name | Type    | Possible values | Default | Description                          | Examples                  |
|-------------|---------|-----------------|---------|--------------------------------------|---------------------------|
| required    | boolean |                 | false   | Require the user to provide a value. | ```{"required": true}```  |

Agent options:

| Option name       | Type    | Possible values | Default | Description                           | Examples                        |
|-------------------|---------|-----------------|---------|---------------------------------------|---------------------------------|
| agent_required    | boolean |                 | false   | Require the agent to provide a value. | ```{"agent_required": true}```  |

#### Choice field validation

Supported for all choice types including "multichoice"", "checkbox" and "radio".

User options:

| Option name | Type    | Possible values | Default | Description                          | Examples                  |
|-------------|---------|-----------------|---------|--------------------------------------|---------------------------|
| required    | boolean |                 | false   | Require the user to provide a value. | ```{"required": true}```  |

Agent options:

| Option name       | Type    | Possible values | Default | Description                           | Examples                        |
|-------------------|---------|-----------------|---------|---------------------------------------|---------------------------------|
| agent_required    | boolean |                 | false   | Require the agent to provide a value. | ```{"agent_required": true}```  |

### How to set custom field values

"Person", "Organization" "Ticket", "Feedback" and "Article" support custom fields. They all have the same data format. To set custom field value you need to provide the custom def **oid** (custom def id in the system you are exporting from) or **field name** and **value**.<br/>

So it could be:

```json
{
    "custom_fields": [
        {"oid": 123, "value": "some value"},
        {"name": "My Field", "value": "some value"},
    ]
}
```

All field types have specific value formats:

#### Text/Textarea/Hidden field custom data value

```json
{
    "custom_fields": [
        {
            "name": "Some field name",
            "value": "some text value" 
        }
    ]
}
```

#### Date field custom data value

```json
{
    "custom_fields": [
        {
            "name": "Some field name",
            "value": "2016-07-25" 
        }
    ]
}
```


#### Datetime field custom data value

```json
{
    "custom_fields": [
        {
            "name": "Some field name",
            "value": "2016-07-25 12:00:00" 
        }
    ]
}
```

#### Datetime field custom data value

```json
{
    "custom_fields": [
        {
            "name": "Some field name",
            "value": "2016-07-25 12:00:00" 
        }
    ]
}
```

#### Toggle field custom data value

Value could be "0" or "1".

```json
{
    "custom_fields": [
        {
            "name": "Some field name",
            "value": 1
        }
    ]
}
```

#### Choice field custom data value

Supported for all choice types including "multichoice"", "checkbox" and "radio". Field value should contain hierarchy choice path separated by ">".

```json
{
    "custom_fields": [
        {
            "name": "Some field name",
            "value": "Choice 1 > Sub choice 1 > Sub choice 1a"
        }
    ]
}
```

**Note:** Custom def default value is set in the same format as custom field value.

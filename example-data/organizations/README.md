Organizations
=============

**Base organization fields.**

| Column name               | Type    | Required | Possible values                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|-------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| oid                       | int     |  *       |                                                                                                             |         | External source id.                                                                                                                     |                                    |
| import_map_key            | string  |  *       |                                                                                                             |         | External source type. <br/><br/>  "oid" + "import_map_key" is the unique key for proper item updates when you try to re-import the item.| dp_organization, zd_organization   |
| name                      | string  |  *       |                                                                                                             |         | Organization name.                                                                                                                      |                                    |
| picture                   | object  |          |                                                                                                             |         | Organization picture.                                                                                                                   |                                    |
| importance                | int     |          | 0-5                                                                                                         | 1       | Organization importance.                                                                                                                |                                    |
| date_created              | string  |          |                                                                                                             | NOW()   | Organization date created.                                                                                                              | 2016-07-12 00:00:00                |
| custom_fields             | array   |          |                                                                                                             | [ ]     | Organization custom fields.                                                                                                             |                                    |
| labels                    | array   |          |                                                                                                             | [ ]     | Organization labels.                                                                                                                    | ["label 1", "label 2"]             |
| contact_data              | array   |          |                                                                                                             | [ ]     | Organization contact data.                                                                                                              |                                    |

**Organization picture.**

| Column name               | Type    | Required | Possible values                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|-------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| blob_data                 | string  |          |                                                                                                             |         | Blob data. You need to use one of "blob_data",  "blob_url" or "blob_path" fields to get blob data.                                      |                                    |
| blob_url                  | string  |          |                                                                                                             |         | Blob url.                                                                                                                               |                                    |
| blob_path                 | string  |          |                                                                                                             |         | Blob path.                                                                                                                              |                                    |
| file_name                 | string  |  *       |                                                                                                             |         | Blob filename.                                                                                                                          |                                    |
| content_type              | string  |  *       |                                                                                                             |         | Blob content type.                                                                                                                      |                                    |

Custom data fields.

| Column name               | Type    | Required | Possible values                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|-------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| key                       | string  |  *       |                                                                                                             |         | Custom definition name.                                                                                                                 |                                    |
| value                     | string  |  *       |                                                                                                             |         | Custom data value.                                                                                                                      |                                    |


**Organization contact data.**

Supported contact types:

 - address
 - facebook
 - fax
 - instant_message
 - linked_in
 - mobile
 - phone
 - skype
 - twitter
 - website
 
Contact data common fields:

| Column name               | Type    | Required | Possible values                                                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| oid                       | int     |  *       |                                                                                                                                             |         | External source id.                                                                                                                     |                                    |
| contact_type              | string  |  *       | address <br/> facebook <br/> fax <br/> instant_message <br/> linked_in <br/>  mobile <br/>  phone <br/>  skype <br/> twitter  <br/> website |         | Contact data type.                                                                                                                      |                                    |
| comment                   | string  |  *       |                                                                                                                                             |         | Contact data comment.                                                                                                                   |                                    |

Contact data specific fields:

1) Address type

| Column name               | Type    | Required | Possible values                                                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| address                   | string  |  *       |                                                                                                                                             |         |                                                                                                                                         |                                    |
| city                      | string  |  *       |                                                                                                                                             |         |                                                                                                                                         |                                    |
| state                     | string  |  *       |                                                                                                                                             |         |                                                                                                                                         |                                    |
| zip                       | string  |  *       |                                                                                                                                             |         |                                                                                                                                         |                                    |
| country                   | string  |  *       |                                                                                                                                             |         |                                                                                                                                         |                                    |

2) Facebook type

| Column name               | Type    | Required | Possible values                                                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| profile_url               | string  |  *       |                                                                                                                                             |         |                                                                                                                                         | https:://facebook.com/profile      |

3) Fax/Mobile/Phone types

| Column name               | Type    | Required | Possible values                                                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| country_calling_code      | string  |  *       |                                                                                                                                             |         |                                                                                                                                         | +1                                 |
| number                    | string  |  *       |                                                                                                                                             |         |                                                                                                                                         | 1234567                            |
| type                      | string  |          |                                                                                                                                             | phone   |                                                                                                                                         |                                    |

4) Instant message type

| Column name               | Type    | Required | Possible values                                                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| username                  | string  |  *       |                                                                                                                                             |         |                                                                                                                                         |                                    |
| service                   | string  |  *       |                                                                                                                                             |         |                                                                                                                                         |                                    |


5) Skype type

| Column name               | Type    | Required | Possible values                                                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| username                  | string  |  *       |                                                                                                                                             |         |                                                                                                                                         |                                    |

6) Twitter type

| Column name               | Type    | Required | Possible values                                                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| username                  | string  |  *       |                                                                                                                                             |         |                                                                                                                                         |                                    |
| display_feed              | boolean |          |                                                                                                                                             | false   |                                                                                                                                         |                                    |

7) Website type

| Column name               | Type    | Required | Possible values                                                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| url                       | string  |  *       |                                                                                                                                             |         |                                                                                                                                         | https:://deskpro.com               |

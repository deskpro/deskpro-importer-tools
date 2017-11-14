Organizations
=============

### Base fields

| Column name               | Type                                                                   | Required | Possible values                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|------------------------------------------------------------------------|----------|-------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| name                      | string                                                                 |  *       |                                                                                                             |         | Organization name.                                                                                                                      |                                    |
| picture                   | [Blob](../download#blob-fields)                                        |          |                                                                                                             |         | Organization picture.                                                                                                                   |                                    |
| importance                | integer                                                                |          | 0-5                                                                                                         | 1       | Organization importance.                                                                                                                |                                    |
| date_created              | DateTime                                                               |          |                                                                                                             | NOW()   | Organization date created.                                                                                                              | 2016-07-12 00:00:00                |
| custom_fields             | [CustomField\[\]](../person_custom_def#how-to-set-custom-field-values) |          |                                                                                                             | [ ]     | Organization custom fields.                                                                                                             |                                    |
| labels                    | string[]                                                               |          |                                                                                                             | [ ]     | Organization labels.                                                                                                                    | ["label 1", "label 2"]             |
| contact_data              | [ContactData](#contact-data-fields)                                    |          |                                                                                                             | [ ]     | Organization contact data.                                                                                                              |                                    |


### Contact data fields

| Column name               | Type                                                                   | Required | Default | Description                   |
| --------------------------|------------------------------------------------------------------------|----------|---------|-------------------------------|
| address                   | [Address\[\]](#address-fields)                                         |          | [ ]     | Address info.                 |
| facebook                  | [Facebook\[\]](#facebook-fields)                                       |          | [ ]     | Facebook profiles.            |
| phone                     | [Phone\[\]](#phone-fields)                                             |          | [ ]     | Phone numbers.                |
| instant_message           | [InstantMessage\[\]](#instantmessage-fields)                           |          | [ ]     | Instant message accounts.     |
| linked_in                 | [LinkedIn\[\]](#linkedin-fields)                                       |          | [ ]     | LinkedIn profiles.            |
| twitter                   | [Twitter\[\]](#twitter-fields)                                         |          | [ ]     | Twitter accounts.             |
| website                   | [Website\[\]](#website-fields)                                         |          | [ ]     | Website urls.                 |

#### Address fields

| Column name               | Type    | Required | Possible values                                                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| oid                       | int     |          |                                                                                                                                             |         | External source id.                                                                                                                     |                                    |
| comment                   | string  |          |                                                                                                                                             |         | Contact data comment.                                                                                                                   |                                    |
| address                   | string  | *        |                                                                                                                                             |         |                                                                                                                                         |                                    |
| city                      | string  | *        |                                                                                                                                             |         |                                                                                                                                         |                                    |
| state                     | string  |          |                                                                                                                                             |         |                                                                                                                                         |                                    |
| zip                       | string  |          |                                                                                                                                             |         |                                                                                                                                         |                                    |
| country                   | string  | *        |                                                                                                                                             |         |                                                                                                                                         |                                    |

#### Facebook fields

| Column name               | Type    | Required | Possible values                                                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| oid                       | int     |          |                                                                                                                                             |         | External source id.                                                                                                                     |                                    |
| comment                   | string  |          |                                                                                                                                             |         | Contact data comment.                                                                                                                   |                                    |
| profile_url               | string  |  *       |                                                                                                                                             |         |                                                                                                                                         | https:://facebook.com/profile      |

#### Phone fields

| Column name               | Type    | Required | Possible values                                                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| oid                       | int     |          |                                                                                                                                             |         | External source id.                                                                                                                     |                                    |
| comment                   | string  |          |                                                                                                                                             |         | Contact data comment.                                                                                                                   |                                    |
| number                    | string  |  *       |                                                                                                                                             |         |                                                                                                                                         | 1234567                            |
| type                      | string  |          | "fax" <br/> "mobile" <br/> "phone"                                                                                                          | phone   |                                                                                                                                         |                                    |

#### InstantMessage fields

| Column name               | Type    | Required | Possible values                                                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| oid                       | int     |          |                                                                                                                                             |         | External source id.                                                                                                                     |                                    |
| comment                   | string  |          |                                                                                                                                             |         | Contact data comment.                                                                                                                   |                                    |
| username                  | string  |  *       |                                                                                                                                             |         |                                                                                                                                         |                                    |
| service                   | string  |  *       | "aim" <br/> "msn" <br/> "icq" <br/> "skype" <br/> "gtalk" <br/> "other"                                                                     |         | Application name.                                                                                                                       |                                    |

#### LinkedIn fields

| Column name               | Type    | Required | Possible values                                                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| oid                       | int     |          |                                                                                                                                             |         | External source id.                                                                                                                     |                                    |
| comment                   | string  |          |                                                                                                                                             |         | Contact data comment.                                                                                                                   |                                    |
| url                       | string  |  *       |                                                                                                                                             |         | LinkedIn profile url.                                                                                                                   | https:://linkedin.com/profile      |

#### Twitter fields

| Column name               | Type    | Required | Possible values                                                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| oid                       | int     |          |                                                                                                                                             |         | External source id.                                                                                                                     |                                    |
| comment                   | string  |          |                                                                                                                                             |         | Contact data comment.                                                                                                                   |                                    |
| username                  | string  |  *       |                                                                                                                                             |         |                                                                                                                                         |                                    |
| display_feed              | boolean |          |                                                                                                                                             | false   |                                                                                                                                         |                                    |

#### Website fields

| Column name               | Type    | Required | Possible values                                                                                                                             | Default | Description                                                                                                                             | Examples                           |
| --------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| oid                       | int     |          |                                                                                                                                             |         | External source id.                                                                                                                     |                                    |
| comment                   | string  |          |                                                                                                                                             |         | Contact data comment.                                                                                                                   |                                    |
| url                       | string  |  *       |                                                                                                                                             |         | Website url.                                                                                                                            | https:://deskpro.com               |
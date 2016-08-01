Article categories
==================

Allows to create full article categories hierarchy and set additional category properties. 
Note that you can also create category when you try to import article with category reference. If the category does not exist it will be created automatically (category will be created by category path).

### Base fields

| Column name               | Type                                          | Required | Possible values | Default | Description                                    | Examples             |
| --------------------------|-----------------------------------------------|----------|-----------------|---------|------------------------------------------------|----------------------|
| title                     | string                                        |  *       |                 |         | Category title.                                |                      |
| is_agent                  | boolean                                       |          |                 | false   |                                                |                      |
| user_groups               | string[]                                      |          |                 | [ ]     | Array of usergroup names.                      | everyone, registered |
| categories                | [ArticleSubCategory\[\]](#articlesubcategory) |          |                 | [ ]     | Children categories, array of sub categories.  |                      |

### ArticleSubCategory

Allows to create deep categories hierarchy.

| Column name               | Type                                          | Required | Possible values | Default | Description                                    | Examples             |
| --------------------------|-----------------------------------------------|----------|-----------------|---------|------------------------------------------------|----------------------|
| oid                       | string                                        |          |                 |         | External source id.                            |                      |
| title                     | string                                        |  *       |                 |         | Category title.                                |                      |
| is_agent                  | boolean                                       |          |                 | false   |                                                |                      |
| user_groups               | string[]                                      |          |                 | [ ]     | Array of usergroup names.                      | everyone, registered |
| categories                | [ArticleSubCategory\[\]](#articlesubcategory) |          |                 | [ ]     | Children categories, array of sub categories.  |                      |

**Note:** Use it for proper item mapping when you try to re-import the item, otherwise the item could be deleted and created again.
Parent article category always has "oid" (it's get from json filename) but you need to define it in sub categories manually.

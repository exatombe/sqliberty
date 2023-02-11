# SQLiberty - SQLiberty is a library for make easy the use of SQL in PHP 

## Installation

### Composer

```bash
composer require garder500/sqliberty
```

### Usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use SQLiberty\Database;

$db = new Database("host","database","user","password","port");
```

## Documentation

### Database 

| Method | Description |
| ------ | ----------- |
| model | Create a new model |

#### Example

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use SQLiberty\Database;

$db = new Database("host","database","user","password","port");

$users = $db->model("users",[
    "id" => Type::INT,
    "name" => Type::VARCHAR,
    "email" => Type::VARCHAR,
    "password" => Type::VARCHAR,
    "created_at" => Type::DATETIME,
    "updated_at" => Type::DATETIME
]);
```

### Model

> Model are the representation of a table in the database, you can use the model to make queries to the database.

| Method | Description |
| ------ | ----------- |
| create | Create a new row in the table |
| update | Update a row in the table |
| delete | Delete a row in the table |
| find | Find rows in the table |
| findOne | Find one row in the table |
| get | Get a row in the table based on the primary key |

#### Example

Regular use flow  of the model :
```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use SQLiberty\Database;
use SQLiberty\Type;

$db = new Database("host","database","user","password","port");

$users = $db->model("users",[
    "id" => Type::INT,
    "name" => Type::VARCHAR,
    "email" => Type::VARCHAR,
    "password" => Type::VARCHAR,
    "created_at" => Type::DATETIME,
    "updated_at" => Type::DATETIME
]);

$user = $users->create([
    "name" => "John Doe",
    "email" => "test@test.fr",
    "password" => "password",
    "created_at" => date("Y-m-d H:i:s"),
    "updated_at" => date("Y-m-d H:i:s")
]);

$user = $users->update([
    "name" => "Jack Doe",
]);
```

> Model represent a table in the database, but you can specify multiple tables in the model, the model will be able to make queries between the tables.

```php
<?php

$users = $db->model("users",[
    "id" => Type::INT,
    "name" => Type::VARCHAR,
    "email" => Type::VARCHAR,
    "password" => Type::VARCHAR,
    "created_at" => Type::DATETIME,
    "updated_at" => Type::DATETIME,
    "posts" => [
        "id" => Type::INT,
        "title" => Type::VARCHAR,
        "content" => Type::TEXT,
        "created_at" => Type::DATETIME,
        "updated_at" => Type::DATETIME,
        "user_id" => ["type" => Type::INT,"table" => "users","column" => "id"]
        "comments" => [
            "id" => Type::INT,
            "content" => Type::TEXT,
            "created_at" => Type::DATETIME,
            "updated_at" => Type::DATETIME,
            "post_id" => ["type" => Type::INT,"table" => "posts","column" => "id"]
        ]
    ]
]);

$users->create([
    "name" => "John Doe",
    "email" => "test@maiL.fr",
    "password" => "password",
    "created_at" => date("Y-m-d H:i:s"),
    "updated_at" => date("Y-m-d H:i:s"),
    "posts" => [
        [
            "title" => "Post 1",
            "content" => "Content 1",
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
            "comments" => [
                [
                    "content" => "Comment 1",
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s"),
                ],
                [
                    "content" => "Comment 2",
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s"),
                ]
            ]
        ],
        [
            "title" => "Post 2",
            "content" => "Content 2",
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
            "comments" => [
                [
                    "content" => "Comment 3",
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s"),
                ],
                [
                    "content" => "Comment 4",
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s"),
                ]
            ]
        ]
        ]
    ]);

// This will create 2 post with 2 comments each
```

> You can also specify the primary key of the table, if you don't specify the primary key, the model will use the keyword "id" as primary key or if you didn't specify the "id" column, the model will don't use a primary key.

```php
<?php

$users = $db->model([
    "custom_primary_key" => Type::INT,
    "name" => Type::VARCHAR,
    "email" => Type::VARCHAR,
    "password" => Type::VARCHAR,
    "created_at" => Type::DATETIME,
    "updated_at" => Type::DATETIME,
],"custom_primary_key");
```




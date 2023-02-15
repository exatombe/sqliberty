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
use SQLiberty\Schema;

$db = new Database("host","database","user","password","port");

$users = $db->model(function(Schema $table){
    $table->int("id");
    $table->varchar("name");
    $table->varchar("email");
    $table->varchar("password");
    $table->datetime("created_at");
    $table->datetime("updated_at");
});
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
use SQLiberty\Schema;

$db = new Database("host","database","user","password","port");

$users = $db->model(function(Schema $table){
    $table->int("id");
    $table->varchar("name");
    $table->varchar("email");
    $table->varchar("password");
    $table->datetime("created_at");
    $table->datetime("updated_at");
});

$user = $users->create([
    "name" => "John Doe",
    "email" => "test@test.fr",
    "password" => "password",
    "created_at" => date("Y-m-d H:i:s"),
    "updated_at" => date("Y-m-d H:i:s")
]);

$user = $users->update([
    "name" => "Jack Doe",
])->save();
```

> Model represent a table in the database, but you can specify multiple tables in the model, the model will be able to make queries between the tables.

```php
<?php

$users = $db->model(function(Schema $table){
    $table->int("id");
    $table->varchar("name");
    $table->varchar("email");
    $table->varchar("password");
    $table->datetime("created_at");
    $table->datetime("updated_at");
    $table->model("posts",function(Schema $table){
        $table->int("id");
        $table->int("user_id");
        $table->varchar("title");
        $table->varchar("content");
        $table->datetime("created_at");
        $table->datetime("updated_at");
        $table->belongsTo("user");
        $table->model("comments",function(Schema $table){
            $table->int("id");
            $table->int("post_id");
            $table->varchar("content");
            $table->datetime("created_at");
            $table->datetime("updated_at");
            $table->belongsTo("post");
        });
    });
});
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

$users = $db->model(function(Model $table){
    $table->int("custom_id")->primaryKey()->autoIncrement();
    $table->varchar("name");
    $table->varchar("email");
    $table->varchar("password");
    $table->datetime("created_at");
    $table->datetime("updated_at");
    $table->primaryKey("email");
});
```




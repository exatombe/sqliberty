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

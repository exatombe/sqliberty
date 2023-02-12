<?php

require __DIR__ . '/vendor/autoload.php';

use Sqliberty\Database;
use Sqliberty\Row;
use Sqliberty\Type;

$db = new Database("localhost","cmscustom","root","");


$eleves = $db->model("eleves",[
    "id" => Type::INT,
    "name" => Type::VARCHAR,
    "created_at" => Type::DATETIME,
    "emprunts" => [
        "type" => Type::TABLE,
        "columns" => [
            "id" => Type::INT,
            "livre_id" => ["type" => Type::INT, "foreignKey" => ["table" => "livres", "column" => "id"]],
            "eleve_id" => ["type" => Type::INT, "foreignKey" => ["table" => "eleves", "column" => "id"]],
            "created_at" => Type::DATETIME,
        ],
    ]
]);

$livres = $db->model("livres",[
    "id" => Type::INT,
    "name" => Type::VARCHAR,
    "author" => Type::VARCHAR,
    "created_at" => Type::DATETIME,
    "emprunts" => [
        "type" => Type::TABLE,
        "columns" => [
            "id" => Type::INT,
            "livre_id" => ["type" => Type::INT, "foreignKey" => ["table" => "livres", "column" => "id"]],
            "eleve_id" => ["type" => Type::INT, "foreignKey" => ["table" => "eleves", "column" => "id"]],
            "created_at" => Type::DATETIME,
        ],
    ],
]);

$eleve = $eleves->create([
    "name" => "John Doe",
    "created_at" => date("Y-m-d H:i:s"),
]);

$livre = $livres->create([
    "name" => "Harry Potter",
    "author" => "J.K. Rowling",
    "created_at" => date("Y-m-d H:i:s"),
]);

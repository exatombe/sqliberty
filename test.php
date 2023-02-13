<?php

require __DIR__ . '/vendor/autoload.php';

use Sqliberty\Database;
use Sqliberty\Row;
use Sqliberty\Schema;
use Sqliberty\Type;

$db = new Database("localhost","cmscustom","root","");


$eleves = $db->model("eleve", function(Schema $model){
    $model->int("id")->autoIncrement()->primaryKey();
    $model->varchar("nom")->length(50)->nullable();
    $model->varchar("prenom")->length(50);
    $model->model("notes", function(Schema $ref){
        $ref->int("id")->autoIncrement()->primaryKey();
        $ref->int("eleve_id")->foreignKey("eleve", "id");
        $ref->int("note");
        $ref->varchar("matiere")->length(50);
        $ref->model("commentaires", function(Schema $ref){
            $ref->int("id")->autoIncrement()->primaryKey();
            $ref->int("note_id")->foreignKey("notes", "id");
            $ref->varchar("commentaire")->length(255);
            return $ref;
        });
        return $ref;
    });
    return $model;
});

print_r(json_encode($eleves->error, JSON_PRETTY_PRINT));
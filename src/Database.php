<?php

namespace Sqliberty;

use PDO;
use PDOException;

class Database{
    private \PDO $pdo;
    private string $error = '';
    public function __construct($host,$db,$user,$pass,$port = 3306){
        try{
            $this->pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass,[PDO::ATTR_PERSISTENT => true]);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }catch(PDOException $e){
            $this->error = $e->getMessage();
        }
    }


    public function model(string $table, array $columns, string $primaryKey = 'id', bool $autoIncrement = false): Model{
        return new Model($this->pdo, $table, $columns, $primaryKey, $autoIncrement);
    }

}
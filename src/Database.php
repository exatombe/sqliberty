<?php

namespace Sqliberty;

use PDO;
use PDOException;
/**
 * Class Database
 * @package Sqliberty
 * @method Model model(string $table, array $columns, string $primaryKey = 'id', bool $autoIncrement = false)
 */
class Database{
    private ?PDO $pdo = null;
    private string $error = '';
    public function __construct($host,$db,$user,$pass,$port = 3306){
        try{
            $this->pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass,[PDO::ATTR_PERSISTENT => true]);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }catch(PDOException $e){
            $this->error = $e->getMessage();
        }
    }

    /**
     * Method to create a new model from database (Model is a table in the database)
     * @param string $table
     * @param array $columns
     * @param string $primaryKey
     * @param bool $autoIncrement
     * @return Model
     */
    public function model(string $table, array $columns, string $primaryKey = 'id', bool $autoIncrement = false): Model{
        if($this->pdo === null)
            throw new \Exception("Database connection error: " . $this->error);
            
        return new Model($this->pdo, $table, $columns, $primaryKey, $autoIncrement);
    }

}
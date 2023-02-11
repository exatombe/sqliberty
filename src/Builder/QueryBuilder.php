<?php

namespace Sqliberty\Builder;

class QueryBuilder {
    private $query;
    
    public function __construct()
    {   
        $this->query = "";
    }

    public function createTable(string $table)
    {
       return new createTable($table, false);
    }


    public function createTableIfNotExist(string $table)
    {
        return new createTable($table, true);
    }

    public function select(array $columns)
    {
        return new Select($columns);
    }

    public function insert(string $table)
    {
        return new Insert($table);
    }

    public function update(string $table)
    {
        return new Update($table);
    }

    public function delete(string $table)
    {
        return new Delete($table);
    }

}
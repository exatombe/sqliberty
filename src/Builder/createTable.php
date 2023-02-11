<?php

namespace Sqliberty\Builder;

class createTable{
    private $query = "";
    public function __construct(string $name, bool $exist)
    {
        if($exist){
            $this->query = "CREATE TABLE IF NOT EXISTS $name";
        }else{
            $this->query = "CREATE TABLE $name";
        }
    }

    public function addColumns(CollectionColumn $columns): self
    {
        $this->query .= "($columns)";
        // add the "innoDB" engine to the table
        $this->query .= " ENGINE=InnoDB";
        // set the default charset to utf8-unicode-ci (case-insensitive)
        $this->query .= " DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        return $this;
    }

    public function getSQL(): string
    {
        return $this->query;
    }

    public function __toString(): string
    {
        return $this->query;
    }

}
<?php

namespace Sqliberty\Builder;

class Insert {
    private $query = "";
    public function __construct(string $table)
    {
        $this->query = "INSERT INTO $table";
    }

    public function addColumns(array $columns): self
    {   
        $this->query .= " (";
        foreach ($columns as $column) {
            $this->query .= "$column, ";
        }
        $this->query = substr($this->query, 0, -2);
        $this->query .= ")";
        return $this;
    }

    public function addValues(array $values): self
    {
        $this->query .= " VALUES (";
        foreach ($values as $value) {
            $this->query .= "'$value', ";
        }
        $this->query = substr($this->query, 0, -2);
        $this->query .= ")";
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
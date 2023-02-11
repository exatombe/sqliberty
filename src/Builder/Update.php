<?php

namespace Sqliberty\Builder;

class Update{
    private $query = "";

    public function __construct(string $table)
    {
        $this->query = "UPDATE $table";
    }

    public function set(CollectionSet $set): self
    {
        $this->query .= " SET $set";
        return $this;
    }

    public function where(string $column, string $operator, string $value): self
    {
        $this->query .= " WHERE $column $operator '$value'";
        return $this;
    }

    public function andWhere(string $column, string $operator, string $value): self
    {
        $this->query .= " AND $column $operator '$value'";
        return $this;
    }

    public function orWhere(string $column, string $operator, string $value): self
    {
        $this->query .= " OR $column $operator '$value'";
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
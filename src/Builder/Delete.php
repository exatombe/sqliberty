<?php

namespace Sqliberty\Builder;

class Delete{
    private $query = "";

    public function __construct(string $table)
    {
        $this->query = "DELETE FROM $table";
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

    public function limit(int $limit): self
    {
        $this->query .= " LIMIT $limit";
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
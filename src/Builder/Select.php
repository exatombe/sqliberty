<?php

namespace Sqliberty\Builder;

class Select{

    private $query = "";

    public function __construct(array $columns)
    {
        $this->query = "SELECT ";
        foreach ($columns as $column) {
            $this->query .= "$column, ";
        }
        $this->query = substr($this->query, 0, -2);
    }

    public function from(string $table): self
    {
        $this->query .= " FROM $table";
        return $this;
    }

    public function join(string $table, string $column1, string $operator, string $column2): self
    {
        $this->query .= " JOIN $table ON $column1 $operator $column2";
        return $this;
    }

    public function leftJoin(string $table, string $column1, string $operator, string $column2): self
    {
        $this->query .= " LEFT JOIN $table ON $column1 $operator $column2";
        return $this;
    }

    public function rightJoin(string $table, string $column1, string $operator, string $column2): self
    {
        $this->query .= " RIGHT JOIN $table ON $column1 $operator $column2";
        return $this;
    }

    public function fullJoin(string $table, string $column1, string $operator, string $column2): self
    {
        $this->query .= " FULL JOIN $table ON $column1 $operator $column2";
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

    public function orderBy(string $column, string $order): self
    {
        $this->query .= " ORDER BY $column $order";
        return $this;
    }

    public function groupBy(string $column): self
    {
        $this->query .= " GROUP BY $column";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->query .= " LIMIT $limit";
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->query .= " OFFSET $offset";
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
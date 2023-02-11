<?php

namespace Sqliberty\Builder;

class Set{
    private string $column = "";
    private string $value = "";

    public function __construct(string $column, string $value)
    {
        $this->column = $column;
        $this->value = $value;
    }

    public function getSQL(): string
    {
        return "$this->column = '$this->value'";
    }

    public function __toString(): string
    {
        return "$this->column = '$this->value'";
    }
}
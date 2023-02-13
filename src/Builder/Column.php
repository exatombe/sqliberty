<?php

namespace Sqliberty\Builder;

class Column{

    public string $name;
    public string $type;
    public int $length;
    public bool $nullable;
    public bool $autoIncrement;
    public bool $primaryKey;
    public array $foreignKey;
    public bool $unique;
    public ?string $default;

    public function __construct(string $name)
    {
        $this->name = $name;
        // init default values
        $this->length = 0;
        $this->nullable = false;
        $this->autoIncrement = false;
        $this->primaryKey = false;
        $this->unique = false;
        $this->default = null;
        $this->foreignKey = [];
    }

    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function length(int $length): self
    {
        $this->length = $length;
        return $this;
    }

    public function nullable(): self
    {
        $this->nullable = true;
        return $this;
    }

    public function autoIncrement(): self
    {
        $this->autoIncrement = true;
        return $this;
    }

    public function primaryKey(): self
    {
        $this->primaryKey = true;
        return $this;
    }

    public function unique(): self
    {
        $this->unique = true;
        return $this;
    }

    public function default(string $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function foreignKey(string $table, string $column): self
    {
        $this->foreignKey = [
            "table" => $table,
            "column" => $column
        ];
        return $this;
    }

    public function __toString()
    {

        $column = $this->name . " " . $this->type;

        if($this->length > 0){
            $column .= "($this->length)";
        }

        if($this->nullable){
            $column .= " NULL";
        }else{
            $column .= " NOT NULL";
        }

        if($this->autoIncrement){
            $column .= " AUTO_INCREMENT";
        }

        if($this->primaryKey){
            $column .= " PRIMARY KEY";
        }

        if($this->unique){
            $column .= " UNIQUE";
        }

        if($this->default){
            $column .= " DEFAULT " . $this->default;
        }

        if(count($this->foreignKey) > 0){
            $column .= ", CONSTRAINT fk_" . $this->name . " FOREIGN KEY (" . $this->name . ") REFERENCES " . $this->foreignKey["table"] . "(" . $this->foreignKey["column"] . ")";
        }

        return $column;
        
    }
}
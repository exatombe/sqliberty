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
    public bool $default;

    public function __construct(string $name)
    {
        $this->name = $name;
        // init default values
        $this->length = 0;
        $this->nullable = false;
        $this->autoIncrement = false;
        $this->primaryKey = false;
        $this->unique = false;
        $this->default = false;
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

    public function nullable(bool $nullable): self
    {
        $this->nullable = $nullable;
        return $this;
    }

    public function autoIncrement(bool $autoIncrement): self
    {
        $this->autoIncrement = $autoIncrement;
        return $this;
    }

    public function primaryKey(bool $primaryKey): self
    {
        $this->primaryKey = $primaryKey;
        return $this;
    }

    public function unique(bool $unique): self
    {
        $this->unique = $unique;
        return $this;
    }

    public function default(bool $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function foreignKey(array $foreignKey): self
    {
        $this->foreignKey = $foreignKey;
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
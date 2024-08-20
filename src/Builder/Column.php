<?php

namespace Sqliberty\Builder;

use Sqliberty\Type;

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
    public bool $updateAt;
    public bool $now;

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
        $this->now = false;
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

    public function updateAt(): self
    {
        $this->updateAt = true;
        return $this;
    }

    public function now(): self
    {
        $this->now = true;
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

    public function foreignKey(string $table, string $column, $cascade = true): self
    {
        $this->foreignKey = [
            "table" => $table,
            "column" => $column,
            "cascade" => $cascade
        ];
        return $this;
    }

    public function __toString()
    {

        $column = $this->name . " " . $this->type;

        if($this->length > 0){
            $column .= "($this->length)";
        }else if($this->type === Type::VARCHAR){
            $column .= "(255)";
        } else if($this->type === Type::INT){
            $column .= "(11)";
        } else if($this->type === Type::TEXT){
            $column .= "(65535)";
        }

        if(!$this->nullable){
            $column .= " NOT NULL";
            if($this->type === Type::TIMESTAMP){
                $column .= " DEFAULT CURRENT_TIMESTAMP";
            }
        }

        if($this->type === Type::TIMESTAMP && $this->updateAt){
            $column .= " ON UPDATE CURRENT_TIMESTAMP";
        }

        if($this->type === Type::TIMESTAMP && $this->now && !$this->nullable){
            $column .= " DEFAULT CURRENT_TIMESTAMP";
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
            $column .= ", CONSTRAINT fk_" . $this->name . " FOREIGN KEY (" . $this->name . ") REFERENCES " . $this->foreignKey["table"] . "(" . $this->foreignKey["column"] . ") ON DELETE " . ($this->foreignKey["cascade"] ? "CASCADE" : "SET NULL") . " ON UPDATE " . ($this->foreignKey["cascade"] ? "CASCADE" : "SET NULL");
        }

        return $column;

    }
}

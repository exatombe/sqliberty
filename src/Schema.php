<?php

namespace Sqliberty;

use Sqliberty\Builder\CollectionColumn;
use Sqliberty\Builder\Column;

class Schema
{
    public string $table;
    public CollectionColumn $columns;
    public array $foreignKeys;
    public string $primaryKey;
    /**
     * @var Column[] $uniqueKeys
     */
    public array $uniqueKeys;
    /**
     * @var Schema[] $references
     */
    public $references;

    public function __construct(string $table)
    {
        $this->table = $table;
        $this->columns = new CollectionColumn();
        $this->foreignKeys = [];
        $this->primaryKey = "id";
        $this->uniqueKeys = [];
        $this->references = [];
    }

    public function varchar(string $name){
        $column = new Column($name);
        $column->type(Type::VARCHAR);
        $this->columns->addColumn($column);
        return $column;
    }

    public function int(string $name){
        $column = new Column($name);
        $column->type(Type::INT);
        $this->columns->addColumn($column);
        return $column;
    }

    public function text(string $name){
        $column = new Column($name);
        $column->type(Type::TEXT);
        $this->columns->addColumn($column);
        return $column;
    }

    public function date(string $name){
        $column = new Column($name);
        $column->type(Type::DATE);
        $this->columns->addColumn($column);
        return $column;
    }

    public function datetime(string $name){
        $column = new Column($name);
        $column->type(Type::DATETIME);
        $this->columns->addColumn($column);
        return $column;
    }

    public function timestamp(string $name){
        $column = new Column($name);
        $column->type(Type::TIMESTAMP);
        $this->columns->addColumn($column);
        return $column;
    }

    public function time(string $name){
        $column = new Column($name);
        $column->type(Type::TIME);
        $this->columns->addColumn($column);
        return $column;
    }

    public function year(string $name){
        $column = new Column($name);
        $column->type(Type::YEAR);
        $this->columns->addColumn($column);
        return $column;
    }

    public function float(string $name){
        $column = new Column($name);
        $column->type(Type::FLOAT);
        $this->columns->addColumn($column);
        return $column;
    }

    public function double(string $name){
        $column = new Column($name);
        $column->type(Type::DOUBLE);
        $this->columns->addColumn($column);
        return $column;
    }

    public function decimal(string $name){
        $column = new Column($name);
        $column->type(Type::DECIMAL);
        $this->columns->addColumn($column);
        return $column;
    }

    public function tinyint(string $name){
        $column = new Column($name);
        $column->type(Type::TINYINT);
        $this->columns->addColumn($column);
        return $column;
    }

    public function smallint(string $name){
        $column = new Column($name);
        $column->type(Type::SMALLINT);
        $this->columns->addColumn($column);
        return $column;
    }

    public function mediumint(string $name){
        $column = new Column($name);
        $column->type(Type::MEDIUMINT);
        $this->columns->addColumn($column);
        return $column;
    }

    public function bigint(string $name){
        $column = new Column($name);
        $column->type(Type::BIGINT);
        $this->columns->addColumn($column);
        return $column;
    }

    public function bit(string $name){
        $column = new Column($name);
        $column->type(Type::BIT);
        $this->columns->addColumn($column);
        return $column;
    }

    public function boolean(string $name){
        $column = new Column($name);
        $column->type(Type::BOOLEAN);
        $this->columns->addColumn($column);
        return $column;
    }

    public function serial(string $name){
        $column = new Column($name);
        $column->type(Type::SERIAL);
        $this->columns->addColumn($column);
        return $column;
    }

    public function blob(string $name){
        $column = new Column($name);
        $column->type(Type::BLOB);
        $this->columns->addColumn($column);
        return $column;
    }

    public function binary(string $name){
        $column = new Column($name);
        $column->type(Type::BINARY);
        $this->columns->addColumn($column);
        return $column;
    }

    public function enum(string $name){
        $column = new Column($name);
        $column->type(Type::ENUM);
        $this->columns->addColumn($column);
        return $column;
    }

    public function set(string $name){
        $column = new Column($name);
        $column->type(Type::SET);
        $this->columns->addColumn($column);
        return $column;
    }

    public function json(string $name){
        $column = new Column($name);
        $column->type(Type::JSON);
        $this->columns->addColumn($column);
        return $column;
    }

    public function model(string $name,callable|Schema $scheme){
        $this->references[$name] = is_callable($scheme) ? $scheme(new Schema($name)) : $scheme;
    }

    public function getTable(): string{
        return $this->table;
    }

    public function getColumns(): CollectionColumn{
        return $this->columns;
    }

    public function getForeignKeys(): array{
        return $this->foreignKeys;
    }

    public function getPrimaryKey(): string{
        return $this->primaryKey;
    }
    /**
     * @return Column[]
     */
    public function getUniqueKeys(){
        return $this->uniqueKeys;
    }

    /**
     * @return Schema[]
     */
    public function getReferences(){
        return $this->references;
    }

    /**
     * @return Schema|false
     */

    public function getReference(string $name): bool|Schema{
        $refs = array_filter($this->references, function (Schema $schema) use ($name){
            return $schema->getTable() === $name;
        });
        return count($refs) > 0 ? array_values($refs)[0] : false;
    }

    public function buildSchema(): self{
        $this->foreignKeys = array_filter($this->columns->getArrayCopy(), function (Column $column){
            return count($column->foreignKey) > 0;
        });
        /**
         * @var Column[] $primaryKeys
         */
        $primaryKeys = array_filter($this->columns->getArrayCopy(), function (Column $column){
            return $column->primaryKey;
        });
        if(count($primaryKeys) > 1){
            // throw 
            throw new \Exception("Only one primary key is allowed");
        }elseif(count($primaryKeys) === 1){
            // check if primary key is numeric 
            if(!in_array(array_values($primaryKeys)[0]->type, [Type::INT, Type::TINYINT, Type::SMALLINT, Type::MEDIUMINT, Type::BIGINT, Type::SERIAL])){
                throw new \Exception("Primary key must be numeric, so you need to use type int, tinyint, smallint, mediumint, bigint or serial");
            }
            $this->primaryKey =array_values($primaryKeys)[0]->name;
        }else{
            $this->int("id")->primaryKey()->autoIncrement();
            $this->primaryKey = "id";
        }

        $this->uniqueKeys = array_filter($this->columns->getArrayCopy(), function (Column $column){
            return $column->unique;
        });
        return $this;
    }
}
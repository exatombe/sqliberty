<?php

namespace Sqliberty;

use Sqliberty\Builder\QueryBuilder;
use Sqliberty\Builder\Column;
use Sqliberty\Builder\CollectionColumn;
use Sqliberty\Builder\Select;
use Sqliberty\Builder\Set;
use Sqliberty\Builder\CollectionSet;
use ArrayObject;
use PDO;
use PDOException;
use Sqliberty\Interfaces\ModelInterface;

/**
 * Model class
 * A model is a class that represent a table in the database
 * @package Sqliberty
 */
class Model extends QueryBuilder implements ModelInterface
{
    private PDO $pdo;
    public string $error = '';
    private Schema $schema;
    private ReferenceArray $references;

    public function __construct(PDO $pdo, string $table, callable|Schema $scheme)
    {
        parent::__construct();
        $this->pdo = $pdo;
        $this->references = new ReferenceArray();
        $schema = is_callable($scheme) ? $scheme(new Schema($table)) : $scheme;
        if ($schema instanceof Schema) {
            $this->schema = $schema->buildSchema();
        } else {
            throw new \Exception("The scheme property must be an instance of Schema or a callable that return an instance of Schema");
        }

        $table = $this->createTableIfNotExist($table);
        $table->addcolumns($this->schema->getColumns());
        try {
            $this->pdo->exec($table->getSQL());
            /**
             * @var Schema $references
             */
            foreach ($this->schema->getReferences() as $references) {
                $ref = new Model($this->pdo, $references->getTable(), function ($schema) use ($references) {
                    return $references;
                });
                $this->references->set($references->getTable(), $ref);
            }
        } catch (PDOException $e) {
            $this->errorHandler($e);
        }
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    private function errorHandler(PDOException $e)
    {
        $this->error = $e->getMessage();
    }

    private function reorganizeData($keys, $data) {
        $result = array();
        foreach ($keys as $key) {
          if (array_key_exists($key, $data)) {
            $result[$key] = $data[$key];
          }
        }
        return $result;
      }
      

    public function create(array $data, $iteration = 0): Row|bool
    {
        $insert = $this->insert($this->schema->getTable());
        $columns = $this->schema->getColumns();
        // remove the primary key if it's autoincrement
        $primaryKey = $this->schema->getPrimaryKey();
        /**
         * @var Column[] $column
         */
        $column = array_filter($columns->getArrayCopy(), function (Column $value) use ($primaryKey) {
            return $value->name == $primaryKey;
        });

        $columnsToSelect = array_map(function (Column $column) {
            return $column->name;
        }, $columns->getArrayCopy());
        if (count($column) > 0) {
            $column = array_values($column)[0];
            if ($column->autoIncrement) {
                $columnsToSelect = array_filter(array_values($columnsToSelect), function (string $value) use ($primaryKey) {
                    return $value != $primaryKey;
                });
            }
        }

        $insert->addColumns(array_values($columnsToSelect));

        // Get ref columns
        $refs = $this->schema->getReferences();
        $dataCopy = $data;
        $refsData = [];
        foreach ($refs as $ref) {
            /**
             * @var Column[] $refColumns
             */
            $refColumns = $ref->getColumns()->getArrayCopy();
            $refTable = $ref->getTable();
            $refData = [];
            if(isset($data[$refTable]) && is_array($data[$refTable])){
                $refData = $data[$refTable];
                $refsData[$refTable] = $data[$refTable];
            }
        }
        $data = array_filter($data, function ($value){
            return !is_array($value);
        });
        // reorganize data to match the columns 
        $data = $this->reorganizeData($columnsToSelect, $data);
        $insert->addValues($data);
        try {
            $this->pdo->exec($insert->getSQL());
            $id = $this->pdo->lastInsertId();
            $data = array_merge($dataCopy, [$primaryKey => $id]);
            $row = new Row($this, $data);
            foreach ($this->references as $ref) {
                $row->set($ref->getSchema()->getTable(), new RowsArray($ref));
            }
            foreach ($refsData as $refTable => $refData) {

                $ref = $this->references->get($refTable);
                // get foreign key column if it references the current table 
                $foreignKeys = $ref->getSchema()->getForeignKeys();
                /**
                 * @var Column[] $foreignKey
                 */
                $foreignKey = array_filter($foreignKeys, function (Column $value) use ($refTable) {
                    return $value->foreignKey['table'] == $this->schema->getTable();
                });
                if(count($foreignKey) > 0){
                    $foreignKey = array_values($foreignKey)[0];
                }else{
                    continue;
                }
                if ($this->isReference($refTable)) {
                    foreach ($refData as $key => $value) {
                        if (is_array($value)) {
                            $value[$foreignKey->name] = $id;
                            $row->get($refTable)->add($value);
                        }
                    }
                }
            }
            return $row;
        } catch (PDOException $e) {
            $this->errorHandler($e);
            return false;
        }
    }

    public function find(array $data): RowsArray|bool
    {
        $columnsName = array_filter($this->schema->getColumns()->getArrayCopy(), function (Column|Model $value) {
            return !$value instanceof Model;
        });
        $columnsName = array_map(function (Column $value) {
            return $this->schema->getTable() . '.' . $value->name . ' as ' . $value->name;
        }, $columnsName);
        $select = $this->select($columnsName);
        $select->from($this->schema->getTable());
        $refs = $this->references->getArrayCopy();
        // make a recursive join if there is references in the table and if the reference is passed in the data array
        $refs = $this->schema->getReferences();
        if(count($refs) > 0){
            foreach ($refs as $ref) {
                $select = $this->joinForeignKeys($ref, $data, $select, $this->schema->getTable());
            }
            foreach ($refs as $ref) {
                $select = $this->joinWhere($ref, $data, $select, $this->schema->getTable());
            }
        }else{
            $select = $this->joinWhere($this->schema, $data, $select, $this->schema->getTable());
        }
        try {
            $stmt = $this->pdo->prepare($select->getSQL());
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $rowsArray = new RowsArray($this);
            foreach ($rows as $row) {
                // for each row, add the reference rows 
                $row = new Row($this, $row);
                foreach ($this->references as $ref) {
                    // get foreign key for the current table 
                    $foreignKeys = $ref->getSchema()->getForeignKeys();
                    /**
                     * @var Column[] $foreignKey
                     */
                    $foreignKey = array_filter($foreignKeys, function (Column $value) use ($ref) {
                        return $value->foreignKey['table'] == $this->getSchema()->getTable();
                    });
                    if(count($foreignKey) == 0) {
                        continue;
                    }else {
                        $foreignKey = array_values($foreignKey)[0];
                    }
                    $rowToFind = $ref->find([
                        $foreignKey->name => $row->get($this->schema->getPrimaryKey())
                    ]);
                    $row->set($ref->getSchema()->getTable(), $rowToFind);
                }
                $rowsArray->set($row->get($this->schema->getPrimaryKey()), $row);
            }
            return $rowsArray;
        } catch (PDOException $e) {
            $this->errorHandler($e);
            return false;
        }
    }

    private function joinForeignKeys(Schema $ref, array $data, Select $select, $currentTable): Select
    {
        $foreignKeys = $ref->getForeignKeys();
        /**
         * @var Column[] $foreignKey
         */
        $foreignKey = array_filter($foreignKeys, function (Column $value) use ($currentTable) {
            return $value->foreignKey['table'] == $currentTable;
        });
        if(count($foreignKey) == 0) {
            return $select;
        }else {
            $foreignKey = array_values($foreignKey)[0];
        }
        $refColumn = $ref->getTable() . "." . $foreignKey->name;
        $primaryKey = $this->schema->getTable() . "." . $this->schema->getPrimaryKey();
        // check if $data has the key of the table 
        if (isset($data[$ref->getTable()])) {
            $refData = $data[$ref->getTable()];
            $select->join($ref->getTable(), $refColumn, "=", $primaryKey);
            // recursively join foreign keys of referenced tables
            foreach ($refData as $row) {
                foreach ($row as $key => $value) {
                    $subRef = $ref->getReference($key);
                    if ($subRef) {
                        $this->joinForeignKeys($subRef, $data, $select, $ref->getTable());
                    }
                }
            }
        }
        return $select;
    }

    private function getFirstKey(array $currentData, Schema $table): array
    {
        foreach ($currentData as $key => $value) {
            if (!is_array($value)) {
                return ["key" => $key, "value" => $value, "table" => $table->getTable()];
            } else {
                // get the schema of the referenced table 
                $ref = $table->getReference($key);
                if ($ref) {
                    return $this->getFirstKey($value, $ref);
                } else {
                    return $this->getFirstKey($value, $table);
                }
            }
        }
    }

    private function joinWhere(Schema $ref, array $data, Select $select, $currentTable)
    {

        
        $firstKey = $this->getFirstKey($data, $this->schema);
        $select->where($firstKey["table"] . "." . $firstKey["key"], "=", $firstKey["value"]);
        foreach ($data as $key => $value) {
            if($key != $firstKey["key"]) {
              if (is_array($value)) {
                $subRef = $ref->getReference($key);
                if ($subRef) {
                    $this->joinWhere($subRef, $data, $select, $ref->getTable());
                }
            } else {
                $select->andWhere($ref->getTable() . "." . $key, "=", $value);
            }
        }    
        }

        return $select;
    }

    public function findOne(array $data): Row|bool|null
    {
        $columnsName = array_filter($this->schema->getColumns()->getArrayCopy(), function (Column|Model $value) {
            return !$value instanceof Model;
        });
        $columnsName = array_map(function (Column $value) {
            return $this->schema->getTable() . '.' . $value->name . ' as ' . $value->name;
        }, $columnsName);
        $select = $this->select($columnsName);
        $select->from($this->schema->getTable());
        $refs = $this->references->getArrayCopy();
        // make a recursive join if there is references in the table and if the reference is passed in the data array
        $refs = $this->schema->getReferences();
        foreach ($refs as $ref) {
            $select = $this->joinForeignKeys($ref, $data, $select, $this->schema->getTable());
        }
        foreach ($refs as $ref) {
            $select = $this->joinWhere($ref, $data, $select, $this->schema->getTable());
        }
        $select->limit(1);
        try {
            $stmt = $this->pdo->prepare($select->getSQL());
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $rowsArray = new RowsArray($this);
            foreach ($rows as $row) {
                // for each row, add the reference rows 
                $row = new Row($this, $row);
                foreach ($this->references as $ref) {
                    // get foreign key for the current table 
                    $foreignKeys = $ref->getSchema()->getForeignKeys();
                    /**
                     * @var Column[] $foreignKey
                     */
                    $foreignKey = array_filter($foreignKeys, function (Column $value) use ($ref) {
                        return $value->foreignKey['table'] == $this->getSchema()->getTable();
                    });
                    if(count($foreignKey) == 0) {
                        continue;
                    }else {
                        $foreignKey = array_values($foreignKey)[0];
                    }
                    $rowToFind = $ref->find([
                        $foreignKey->name => $row->get($this->schema->getPrimaryKey())
                    ]);
                    $row->set($ref->getSchema()->getTable(), $rowToFind);
                }
                $rowsArray->set($row->get($this->schema->getPrimaryKey()), $row);
            }
            // return one row or null if there is no row
            return $rowsArray->first() ?? null;
        } catch (PDOException $e) {
            $this->errorHandler($e);
            return false;
        }
    }

    public function get(int $id): Row|null|bool
    {
        return $this->findOne([$this->schema->getPrimaryKey() => $id]);
    }

    public function getReferences(): ReferenceArray
    {
        return $this->references;
    }

    public function isReference(string $table): bool
    {
        return $this->references->has($table);
    }
}

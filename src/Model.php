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

    public function __construct(PDO $pdo,string $table, callable $callback)
    {
        parent::__construct();
        $this->pdo = $pdo;
        $this->references = new ReferenceArray();
        $schema = $callback(new Schema($table));
        if($schema instanceof Schema){
            $this->schema = $schema->buildSchema();
        }else{
            throw new \Exception("The callback must return an instance of Sqliberty\Schema");
        }

        $table = $this->createTableIfNotExist($table);
        $table->addcolumns($this->schema->getColumns());
        try{
            $this->pdo->exec($table->getSQL());
            /**
             * @var Schema $references
             */
             foreach($this->schema->getReferences() as $references){
               $ref = new Model($this->pdo, $references->getTable(), function($schema) use ($references){
                    return $references;
                });
                $this->references->set($references->getTable(), $ref);
             }
        }catch(PDOException $e){
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

    public function create(array $data): Row|bool
    {
        $insert = $this->insert($this->schema->getTable());
        $columns = $this->schema->getColumns();
        // remove the primary key if it's autoincrement
        $primaryKey = $this->schema->getPrimaryKey();
        /**
         * @var Column $column
         */ 
        $column = array_filter($columns->getArrayCopy(), function (Column $value) use ($primaryKey) {
            return $value->name == $primaryKey;
        });
        if($column->autoIncrement()){
            $columns = array_filter($columns->getArrayCopy(), function (Column $value) use ($primaryKey) {
                return $value->name != $primaryKey;
            });
        }

        $insert->addColumns($columns);

        // Get ref columns
        $refs = $this->schema->getReferences();
        $dataCopy = $data;
        $refsData = [];
        foreach ($refs as $ref) {
            $refColumns = $ref->getColumns();
            $refTable = $ref->getTable();
            $refData = [];
            foreach ($refColumns as $refColumn) {
                $refData[$refColumn] = $data[$refColumn];
                unset($data[$refColumn]);
            }
            $refsData[$refTable] = $refData;
        }
        
        $insert->addValues($data);
        try{
            $this->pdo->exec($insert->getSQL());
            $id = $this->pdo->lastInsertId();
            $data = array_merge($dataCopy, [$primaryKey => $id]);
            $row = new Row($this, $data);
            foreach($refsData as $refTable => $refData){

                $ref = $this->references->get($refTable);
                // get foreign key column if it references the current table 
                $foreignKeys = $ref->getSchema()->getForeignKeys();
                /**
                 * @var Column[] $foreignKey
                 */
                $foreignKey = array_filter($foreignKeys, function (Column $value) use ($refTable) {
                    return $value->foreignKey['table'] == $refTable;
                });
                $foreignKey = $foreignKey[0];
                $refData[$foreignKey->name] = $id;
                $ref->create($refData);
            }
            return $row;
        }catch(PDOException $e){
            $this->errorHandler($e);
            return false;
        }

    }

    public function find(array $data): RowsArray
    {
        $columnsName = array_filter($this->schema->getColumns()->getArrayCopy(), function (Column|Model $value) {
            return !$value instanceof Model;
        });
        $columnsName = array_map(function (Column $value) {
            return $value->name;
        }, $columnsName);
        $select = $this->select($columnsName);
        $select->from($this->schema->getTable());
        return new RowsArray($this, $data);
    }

    public function findOne(array $data): Row
    {
        return new Row($this, $data);
    }

    public function get(int $id): Row
    {
        return new Row($this, ["id" => $id]);
    }

    public function getReferences(): ReferenceArray
    {
        return $this->references;
    }

    public function isReference(string $table): bool
    {
        return $this->references->has($table);
    }

    // /**
    //  * Method to update a row in the table
    //  * @return Row|Model
    //  */
    // public function update(array $data): Row|Model
    // {
    //     $update = $this->builder->update($this->table);
    //     $set = new CollectionSet();
    //     // si primaryKey is undefined return error
    //     if (!isset($this->primaryKey)) {
    //         return "Unknow primary key can't update";
    //     }
    //     foreach ($data as $key => $value) {
    //         if (in_array($key, $this->columns)) {
    //             if (is_string($value)) {
    //                 $set->addSet(new Set($key, $value));
    //             }
    //         }
    //     }

    //     // check if their are relations columns with a foreign key
    //     $relations = array_filter($this->columns, function ($value) {
    //         return $value instanceof Model;
    //     });
    //     // get relations data
    //     $relationData = array_filter($data, function ($value) {
    //         return is_array($value);
    //     });
    //     $update->set($set)
    //         ->where($this->primaryKey, "=", $data[$this->primaryKey]);
    //     try {
    //         $this->pdo->exec($update->getSQL());
    //         $row = new Row($this, $data);
    //         // check if there is relations columns
    //         if (count($relations) > 0) {
    //             /**
    //              * @var Model[] $relations
    //              */
    //             foreach ($relations as $relation) {
    //                 $foreignKey = array_filter($this->schema[$relation->table]["columns"], function ($value) {
    //                     return is_array($value);
    //                 });
    //                 $foreignKey = array_keys($foreignKey)[0];
    //                 $relationRow = $relation->find([
    //                     $foreignKey => $data[$this->primaryKey]
    //                 ]);
    //                 foreach ($relationData[$relation->table] as $key => $value) {
    //                     $value[$foreignKey] = $data[$this->primaryKey];
    //                     $relationRow->append($relation->create($value));
    //                 }
    //                 // find for the relation data
    //                 $row->set($relation->table, $relationRow);
    //             }
    //         }
    //         return $row;
    //     } catch (PDOException $e) {
    //         $this->error = $e->getMessage();
    //         return $this;
    //     }
    // }
    // /**
    //  * Method to delete a row in the table
    //  * @return Row|Model
    //  */
    // public function delete(array $data)
    // {
    //     $delete = $this->builder->delete($this->table);
    //     // si primaryKey is undefined return error
    //     if (!isset($this->primaryKey)) {
    //         return "Unknow primary key can't delete";
    //     }
    //     // check if their are relations columns with a foreign key
    //     $relations = array_filter($this->columns, function ($value) {
    //         return $value instanceof Model;
    //     });
    //     if (count($relations) > 0) {
    //         /**
    //          * @var Model[] $relations
    //          */
    //         foreach ($relations as $relation) {
    //             $foreignKey = array_filter($this->schema[$relation->table]["columns"], function ($value) {
    //                 return is_array($value);
    //             });
    //             $foreignKey = array_keys($foreignKey)[0];
    //             $relation->delete([$foreignKey => $data[$this->primaryKey]]);
    //         }
    //     }
    //     // if primary key is not in the data check other columns 
    //     if (!isset($data[$this->primaryKey])) {
    //         $data = array_filter($data, function ($value) {
    //             return !is_array($value);
    //         });
    //         // get the first column of the data
    //         $column = array_keys($data)[0];
    //         $delete->where($column, "=", $data[$column]);
    //         // remove the column from the data

    //         array_shift($data);
    //         // check every column in the data
    //         foreach ($data as $key => $value) {
    //             $delete->orWhere($key, "=", $value);
    //         }

    //         $delete->limit(1);
    //     } else {
    //         $delete->where($this->primaryKey, "=", $data[$this->primaryKey]);
    //     }
    //     try {
    //         $this->pdo->exec($delete->getSQL());
    //         return true;
    //     } catch (PDOException $e) {
    //         $this->error = $e->getMessage();
    //         return $this;
    //     }
    // }
    // /**
    //  * Method to find a row in the table based on the primary key
    //  * @return Row|Model
    //  */
    // public function get(int $id)
    // {
    //     $select = $this->builder->select(["*"]);
    //     $select->from($this->table);
    //     if (!isset($this->primaryKey))
    //         return "Unknow primary key can't find";

    //     // check if their are relations columns with a foreign key
    //     $relations = array_filter($this->columns, function ($value) {
    //         return $value instanceof Model;
    //     });

    //     $select->where($this->primaryKey, "=", $id);


    //     try {
    //         $stmt = $this->pdo->query($select->getSQL());
    //         $result = $stmt->fetch(PDO::FETCH_ASSOC);
    //         // if($result === false || $result === null || $result === [] || $result === "")
    //         if ($result === false || $result === null) {
    //             return null;
    //         }
    //         $row = new Row($this, $result);
    //         if (count($relations) > 0) {
    //             /**
    //              * @var Model[] $relations
    //              */
    //             foreach ($relations as $relation) {
    //                 $foreignKey = array_filter($this->schema[$relation->table]["columns"], function ($value) {
    //                     return is_array($value);
    //                 });
    //                 $foreignKey = array_keys($foreignKey)[0];
    //                 $rowRel = $relation->find([
    //                     $foreignKey => $row->get($this->primaryKey)
    //                 ]);
    //                 if (count($rowRel) > 0) {
    //                     $row->set($relation->table, $rowRel);
    //                 }
    //             }
    //         }
    //         return $row;
    //     } catch (PDOException $e) {
    //         $this->error = $e->getMessage();
    //         return $this;
    //     }
    // }
    // /**
    //  * Method to find multiple rows in the table based on data (recursive search is possible)
    //  * @return Row|Model
    //  */
    // public function find(array $data)
    // {
    //     $rows = new ArrayObject();
    //     // check if their are relations search in the data
    //     $relationsDatas = array_filter($data, function ($value) {
    //         return is_array($value);
    //     });
    //     $relations = array_filter($this->columns, function ($value) {
    //         return $value instanceof Model;
    //     });

    //     // get all columns of the table 
    //     $tableColumns = array_filter($this->schema, function ($value) {
    //         return !is_array($value);
    //     });

    //     $tableColumns = array_keys($tableColumns);
    //     $arrayOfColumn = ["*"];
    //     if (count($relationsDatas) > 0) {
    //         $arrayOfColumn = array_map(function ($value) {
    //             return $this->table . "." . $value . " as " . $value;
    //         }, $tableColumns);
    //     }
    //     $select = $this->builder->select($arrayOfColumn);
    //     $select->from($this->table);
    //     // select the first element of the array
    //     $first = array_key_first($data);

    //     if (count($relationsDatas) == 0) {
    //         $select->where($first, "=", $data[$first]);
    //         // remove the first element of the array 
    //         array_shift($data);
    //         foreach ($data as $key => $value) {
    //             if (!is_array($value))
    //                 $select->andWhere($key, "=", $value);
    //         }
    //     } else {

    //         // inner join the relation table 
    //         $select = $this->recursiveSelect($select, $this->schema, $relationsDatas, $this->table, $this->primaryKey);
    //         // select the first element of the array
    //         // check from where table the column is

    //         $select = $this->recursiveWhere($select, $this->schema, $data, $relationsDatas, $this->table, $this->primaryKey, $first);

    //         $select = $this->recursiveAndWhere($select, $this->schema, $data, $relationsDatas, $this->table, $this->primaryKey);
    //     }

    //     try {
    //         $stmt = $this->pdo->query($select->getSQL());
    //         $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //         // get number of rows
    //         $count = $stmt->rowCount();
    //         $rows = new ArrayObject();
    //         for ($i = 0; $i < $count; $i++) {
    //             $rows->append(new Row($this, $result[$i]));
    //         }
    //         $rows = array_map(function ($row) use ($relations) {
    //             // check if their are relations columns with a foreign key

    //             if (count($relations) > 0) {
    //                 /**
    //                  * @var Model[] $relations
    //                  */
    //                 foreach ($relations as $relation) {
    //                     $foreignKey = array_filter($this->schema[$relation->table]["columns"], function ($value) {
    //                         return is_array($value);
    //                     });
    //                     $foreignKey = array_filter($foreignKey, function ($value) {
    //                         return array_key_exists("foreignKey", $value);
    //                     });
    //                     if ($foreignKey) {
    //                         $foreignKey = array_keys($foreignKey)[0];
    //                         $rowRel = $relation->find([
    //                             $foreignKey => $row->get($this->primaryKey)
    //                         ]);
    //                         if ($rowRel instanceof Model) {
    //                             die($rowRel->error);
    //                         }
    //                         if (count($rowRel) > 0) {
    //                             $row->set($relation->table, $rowRel);
    //                         }
    //                     } else {
    //                         // search in foreingkey on the current table 
    //                         $foreignKey = array_filter($this->schema, function ($value) {
    //                             return is_array($value);
    //                         });

    //                         // look if each array contain "foreignKeys" key 
    //                         $foreignKey = array_filter($foreignKey, function ($value) {
    //                             return array_key_exists("foreignKey", $value);
    //                         });

    //                         // get the first key of the array
    //                         $foreignKey = array_keys($foreignKey)[0];
    //                         $rowRel = $relation->find([
    //                             $relation->primaryKey => $row->get($foreignKey)
    //                         ]);

    //                         if ($rowRel instanceof Model) {
    //                             die($rowRel->error);
    //                         }
    //                         if (count($rowRel) > 0) {
    //                             $row->set($relation->table, $rowRel);
    //                         }
    //                     }
    //                 }
    //             }
    //             return $row;
    //         }, $rows->getArrayCopy());
    //         return new ArrayObject($rows);
    //     } catch (PDOException $e) {
    //         $this->error = $e->getMessage();
    //         return $this;
    //     }
    // }

    // private function recursiveWhere(Select $select, $schema, $data, $relationData, $table, $primaryKey, $first)
    // {
    //     // verify if $data[$first] exist 
    //     if (!is_array($data[$first])) {
    //         $select->where($table . "." . $first, "=", $data[$first]);
    //         return $select;
    //     } else {
    //         $first = array_key_first($data[$first]);
    //         $table = array_key_first($data);
    //         if (is_array($data[$table][$first])) {
    //             $first = array_key_first($data[$table][$first]);
    //             $table = array_key_first($data[$table]);
    //             $data = $data[$table][$first];
    //             $this->recursiveWhere($select, $schema, $data, $relationData, $table, $primaryKey, $first);
    //         }
    //     }
    //     return $select;
    // }

    // private function recursiveAndWhere(Select $select, $schema, $data, $relationData, $table, $primaryKey)
    // {
    //     foreach ($relationData as $key => $value) {
    //         if (is_string($value)) {
    //             break;
    //         }
    //         foreach ($value as $k => $v) {
    //             if (!is_array($v)) {
    //                 $select->andWhere($key . "." . $k, "=", $v);
    //             } else {
    //                 $k = array_key_first($v);
    //                 $v = $v[$k];
    //                 $key = array_key_first($relationData);
    //                 $relationData = $relationData[$key];
    //                 $this->recursiveAndWhere($select, $schema, $v, $relationData, $key, $primaryKey);
    //             }
    //         }
    //     }
    //     if (is_array($data)) {
    //         foreach ($data as $key => $value) {
    //             if (!is_array($value))
    //                 $select->andWhere($this->table . "." . $key, "=", $value);
    //         }
    //     }
    //     return $select;
    // }

    // private function recursiveSelect(Select $select, $schema, $relationsDatas, $table, $primaryKey)
    // {
    //     // inner join the relation table 
    //     foreach ($relationsDatas as $key => $value) {
    //         if (is_string($value)) {
    //             break;
    //         }

    //         if (is_string($schema[$key])) {
    //             break;
    //         }
    //         $foreignKey = array_filter($schema[$key]["columns"], function ($value) {
    //             return is_array($value);
    //         });

    //         $foreignKey = array_keys($foreignKey)[0];
    //         $select->join($key, $table . "." . $primaryKey, "=", $key . "." . $foreignKey);
    //         // check if $value is an array 
    //         if (is_array($value)) {
    //             // get the next table name
    //             $nextTable = $key;
    //             // get the next schema 
    //             $nextSchema = $schema[$nextTable]["columns"];
    //             // get the next relation data
    //             $nextRelationData = $relationsDatas[$nextTable];
    //             // get the next primary key
    //             $nextPrimaryKey = "id";
    //             $this->recursiveSelect($select, $nextSchema, $nextRelationData, $nextTable, $nextPrimaryKey);
    //         } else {
    //             return $select;
    //         }
    //     }
    //     return $select;
    // }
    // /**
    //  * Find one row in the table based on the data (recursive search is possible)
    //  * @param array $data
    //  * @return Row|Model
    //  */
    // public function findOne(array $data)
    // {
    //     $relationsDatas = array_filter($data, function ($value) {
    //         return is_array($value);
    //     });
    //     $relations = array_filter($this->columns, function ($value) {
    //         return $value instanceof Model;
    //     });

    //     // get all columns of the table 
    //     $tableColumns = array_filter($this->schema, function ($value) {
    //         return !is_array($value);
    //     });

    //     $tableColumns = array_keys($tableColumns);
    //     $arrayOfColumn = ["*"];
    //     if (count($relationsDatas) > 0) {
    //         $arrayOfColumn = array_map(function ($value) {
    //             return $this->table . "." . $value . " as " . $value;
    //         }, $tableColumns);
    //     }
    //     $select = $this->builder->select($arrayOfColumn);
    //     $select->from($this->table);
    //     // select the first element of the array
    //     $first = array_key_first($data);

    //     if (count($relationsDatas) == 0) {
    //         $select->where($first, "=", $data[$first]);
    //         // remove the first element of the array 
    //         array_shift($data);
    //         foreach ($data as $key => $value) {
    //             if (!is_array($value))
    //                 $select->andWhere($key, "=", $value);
    //         }
    //     } else {

    //         // inner join the relation table 
    //         $select = $this->recursiveSelect($select, $this->schema, $relationsDatas, $this->table, $this->primaryKey);
    //         // select the first element of the array
    //         // check from where table the column is

    //         $select = $this->recursiveWhere($select, $this->schema, $data, $relationsDatas, $this->table, $this->primaryKey, $first);

    //         $select = $this->recursiveAndWhere($select, $this->schema, $data, $relationsDatas, $this->table, $this->primaryKey);
    //     }

    //     try {
    //         $stmt = $this->pdo->query($select->getSQL());
    //         $result = $stmt->fetch(PDO::FETCH_ASSOC);
    //         $row = new Row($this, $result);
    //         if (count($relations) > 0) {
    //             /**
    //              * @var Model[] $relations
    //              */
    //             foreach ($relations as $relation) {
    //                 $foreignKey = array_filter($this->schema[$relation->table]["columns"], function ($value) {
    //                     return is_array($value);
    //                 });
    //                 $foreignKey = array_filter($foreignKey, function ($value) {
    //                     return array_key_exists("foreignKey", $value);
    //                 });
    //                 if ($foreignKey) {
    //                     $foreignKey = array_keys($foreignKey)[0];
    //                     $rowRel = $relation->find([
    //                         $foreignKey => $row->get($this->primaryKey)
    //                     ]);
    //                     if ($rowRel instanceof Model) {
    //                         die($rowRel->error);
    //                     }
    //                     if (count($rowRel) > 0) {
    //                         $row->set($relation->table, $rowRel);
    //                     }
    //                 } else {
    //                     // search in foreingkey on the current table 
    //                     $foreignKey = array_filter($this->schema, function ($value) {
    //                         return is_array($value);
    //                     });

    //                     // look if each array contain "foreignKeys" key 
    //                     $foreignKey = array_filter($foreignKey, function ($value) {
    //                         return array_key_exists("foreignKey", $value);
    //                     });

    //                     // get the first key of the array
    //                     $foreignKey = array_keys($foreignKey)[0];
    //                     $rowRel = $relation->find([
    //                         $relation->primaryKey => $row->get($foreignKey)
    //                     ]);

    //                     if ($rowRel instanceof Model) {
    //                         die($rowRel->error);
    //                     }
    //                     if (count($rowRel) > 0) {
    //                         $row->set($relation->table, $rowRel);
    //                     }
    //                 }
    //             }
    //         }
    //         return $row;
    //     } catch (PDOException $e) {
    //         $this->error = $e->getMessage();
    //         return $this;
    //     }
    // }
}

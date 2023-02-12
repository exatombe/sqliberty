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
/**
 * Model class
 * A model is a class that represent a table in the database
 * @package Sqliberty
 */
class Model
{
    private PDO $pdo;
    private QueryBuilder $builder;
    public string $error = '';
    private $columns = [];
    private ?string $primaryKey;
    public $relationsColumns = [];
    private bool $autoIncrement = false;
    public string $table;
    public array $schema = [];

    public function __construct(PDO $pdo, string $table, array $columns = [], string $primaryKey = 'id', bool $autoIncrement = true)
    {
        $this->pdo = $pdo;
        $this->builder = new QueryBuilder();
        $this->table = $table;
        $this->schema = $columns;
        $autoIncrement = $autoIncrement || true;
        // check if 
        $table = $this->builder->createTableIfNotExist($table);
        $colColumns = new CollectionColumn();
        $relationsColumns = [];
        // the array columns is ["name" => "type"] 
        foreach ($columns as $name => $type) {
            // add also columns to $this->columns
            // check if the type is a string or not
            if (!is_string($type)) {
                // make foreign key here to link to another table 
                if ($type["type"] == "table") {
                    array_push($relationsColumns, [
                        "name" => $name,
                        "columns" => $type["columns"],
                    ]);
                } else {
                    $curCol = new Column($name);
                    $curCol->type($type["type"]);
                    $curCol->length(11);
                    $curCol->foreignKey($type);
                    $colColumns->addColumn($curCol);
                    array_push($this->columns, $name);
                }
            } else {
                $curCol = new Column($name);
                $curCol->type($type);
                // check if the type is VARCHAR, TEXT, MEDIUMTEXT, LONGTEXT and add the length*
                if ($this->addLength($type) > 0) {
                    $curCol->length($this->addLength($type));
                }
                // check if the column is the primary key
                if ($name == $primaryKey) {
                    $this->primaryKey = $name;
                    $curCol->primaryKey(true);
                    // check if the primary key is autoincrement
                    if ($autoIncrement) {
                        $curCol->autoIncrement(true);
                    } else {
                        $this->autoIncrement = false;
                        array_push($this->columns, $name);
                    }
                } else {
                    array_push($this->columns, $name);
                }
                $colColumns->addColumn($curCol);
            }
        }

        $table->addColumns($colColumns);

        // execute the query 
        try {
            $this->pdo->exec($table->getSQL());

            // check if there is relations columns
            if (count($relationsColumns) > 0) {
                foreach ($relationsColumns as $relation) {
                    $rel = new Model($this->pdo, $relation["name"], $relation["columns"]);
                    array_push($this->columns, $rel);
                }
            }
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
        }
    }
    /**
     * Method to add length to the column
     * @param string $type
     * @return int
     */
    private function addLength(string $type)
    {
        switch ($type) {
            case Type::VARCHAR:
                return 255;
            case Type::TEXT:
                return 65535;
            case Type::MEDIUMTEXT:
                return 16777215;
            case Type::LONGTEXT:
                return 4294967295;
            default:
                return 0;
        }
    }
    /**
     * Method to create a new row in the table
     * @param array $data
     * @return Row|Model
     */
    public function create(array $data): Row|Model
    {
        $insert = $this->builder->insert($this->table);

        $columns = $this->columns;
        // remove the primary key if it's autoincrement 
        if (isset($this->autoIncrement)) {
            if($this->autoIncrement){
                $columns = array_filter($this->columns, function ($value) {
                    return $value != $this->primaryKey;
                });
            }
        }
        $relations = array_filter($this->columns, function ($value) {
            return $value instanceof Model;
        });
        $relationData = array_filter($data, function ($value) {
            return is_array($value);
        });

        $columns = array_filter($columns, function ($value) {
            return !$value instanceof Model;
        });
        $data = array_filter($data, function ($value) {
            return !is_array($value);
        });
        $insert->addColumns($columns);
        $insert->addValues($data);
        try {
            // return the current data inserted with a new 
            $this->pdo->exec($insert->getSQL());
            // add id to the data
            $id = $this->pdo->lastInsertId();
            $data["id"] = $id;

            $row = new Row($this, $data);

            // check if there is relations columns
            if (count($relations) > 0) {
                /**
                 * @var Model[] $relations
                 */
                foreach ($relations as $relation) {
                    $foreignKey = array_filter($this->schema[$relation->table]["columns"], function ($value) {
                        return is_array($value);
                    });
                    $foreignKey = array_keys($foreignKey)[0];
                    $arrayOfRows = [];
                    foreach ($relationData[$relation->table] as $key => $value) {
                        $value[$foreignKey] = $id;
                        array_push($arrayOfRows, $relation->create($value));
                    }
                    $row->set($relation->table, $arrayOfRows);
                }
            }

            return $row;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return $this;
        }
    }
    /**
     * Method to update a row in the table
     * @return Row|Model
     */
    public function update(array $data): Row|Model
    {
        $update = $this->builder->update($this->table);
        $set = new CollectionSet();
        // si primaryKey is undefined return error
        if (!isset($this->primaryKey)) {
            return "Unknow primary key can't update";
        }
        foreach ($data as $key => $value) {
            if (in_array($key, $this->columns)) {
                if (is_string($value)) {
                    $set->addSet(new Set($key, $value));
                }
            }
        }

        // check if their are relations columns with a foreign key
        $relations = array_filter($this->columns, function ($value) {
            return $value instanceof Model;
        });
        // get relations data
        $relationData = array_filter($data, function ($value) {
            return is_array($value);
        });
        $update->set($set)
            ->where($this->primaryKey, "=", $data[$this->primaryKey]);
        try {
            $this->pdo->exec($update->getSQL());
            $row = new Row($this, $data);
            // check if there is relations columns
            if (count($relations) > 0) {
                /**
                 * @var Model[] $relations
                 */
                foreach ($relations as $relation) {
                    $foreignKey = array_filter($this->schema[$relation->table]["columns"], function ($value) {
                        return is_array($value);
                    });
                    $foreignKey = array_keys($foreignKey)[0];
                    $relationRow = $relation->find([
                        $foreignKey => $data[$this->primaryKey]
                    ]);
                    foreach ($relationData[$relation->table] as $key => $value) {
                        $value[$foreignKey] = $data[$this->primaryKey];
                        $relationRow->append($relation->create($value));
                    }
                    // find for the relation data
                    $row->set($relation->table, $relationRow);
                }
            }
            return $row;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return $this;
        }
    }
    /**
     * Method to delete a row in the table
     * @return Row|Model
     */
    public function delete(array $data)
    {
        $delete = $this->builder->delete($this->table);
        // si primaryKey is undefined return error
        if (!isset($this->primaryKey)) {
            return "Unknow primary key can't delete";
        }
        // check if their are relations columns with a foreign key
        $relations = array_filter($this->columns, function ($value) {
            return $value instanceof Model;
        });
        if (count($relations) > 0) {
            /**
             * @var Model[] $relations
             */
            foreach ($relations as $relation) {
                $foreignKey = array_filter($this->schema[$relation->table]["columns"], function ($value) {
                    return is_array($value);
                });
                $foreignKey = array_keys($foreignKey)[0];
                $relation->delete([$foreignKey => $data[$this->primaryKey]]);
            }
        }
        // if primary key is not in the data check other columns 
        if (!isset($data[$this->primaryKey])) {
            $data = array_filter($data, function ($value) {
                return !is_array($value);
            });
            // get the first column of the data
            $column = array_keys($data)[0];
            $delete->where($column, "=", $data[$column]);
            // remove the column from the data

            array_shift($data);
            // check every column in the data
            foreach ($data as $key => $value) {
                $delete->orWhere($key, "=", $value);
            }

            $delete->limit(1);
        } else {
            $delete->where($this->primaryKey, "=", $data[$this->primaryKey]);
        }
        try {
            $this->pdo->exec($delete->getSQL());
            return true;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return $this;
        }
    }
    /**
     * Method to find a row in the table based on the primary key
     * @return Row|Model
     */
    public function get(int $id)
    {
        $select = $this->builder->select(["*"]);
        $select->from($this->table);
        if (!isset($this->primaryKey))
            return "Unknow primary key can't find";

        // check if their are relations columns with a foreign key
        $relations = array_filter($this->columns, function ($value) {
            return $value instanceof Model;
        });

        $select->where($this->primaryKey, "=", $id);


        try {
            $stmt = $this->pdo->query($select->getSQL());
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            // if($result === false || $result === null || $result === [] || $result === "")
            if ($result === false || $result === null) {
                return null;
            }
            $row = new Row($this, $result);
            if (count($relations) > 0) {
                /**
                 * @var Model[] $relations
                 */
                foreach ($relations as $relation) {
                    $foreignKey = array_filter($this->schema[$relation->table]["columns"], function ($value) {
                        return is_array($value);
                    });
                    $foreignKey = array_keys($foreignKey)[0];
                    $rowRel = $relation->find([
                        $foreignKey => $row->get($this->primaryKey)
                    ]);
                    if (count($rowRel) > 0) {
                        $row->set($relation->table, $rowRel);
                    }
                }
            }
            return $row;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return $this;
        }
    }
    /**
     * Method to find multiple rows in the table based on data (recursive search is possible)
     * @return Row|Model
     */
    public function find(array $data)
    {
        $rows = new ArrayObject();
        // check if their are relations search in the data
        $relationsDatas = array_filter($data, function ($value) {
            return is_array($value);
        });
        $relations = array_filter($this->columns, function ($value) {
            return $value instanceof Model;
        });

        // get all columns of the table 
        $tableColumns = array_filter($this->schema, function ($value) {
            return !is_array($value);
        });

        $tableColumns = array_keys($tableColumns);
        $arrayOfColumn = ["*"];
        if (count($relationsDatas) > 0) {
            $arrayOfColumn = array_map(function ($value) {
                return $this->table . "." . $value . " as " . $value;
            }, $tableColumns);
        }
        $select = $this->builder->select($arrayOfColumn);
        $select->from($this->table);
        // select the first element of the array
        $first = array_key_first($data);

        if (count($relationsDatas) == 0) {
            $select->where($first, "=", $data[$first]);
            // remove the first element of the array 
            array_shift($data);
            foreach ($data as $key => $value) {
                if (!is_array($value))
                    $select->andWhere($key, "=", $value);
            }
        } else {

            // inner join the relation table 
           $select = $this->recursiveSelect($select, $this->schema, $relationsDatas, $this->table, $this->primaryKey);
            // select the first element of the array
            // check from where table the column is

           $select = $this->recursiveWhere($select, $this->schema, $data,$relationsDatas , $this->table, $this->primaryKey, $first);

           $select = $this->recursiveAndWhere($select, $this->schema, $data, $relationsDatas, $this->table, $this->primaryKey);
        }

        try {
            $stmt = $this->pdo->query($select->getSQL());
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // get number of rows
            $count = $stmt->rowCount();
            $rows = new ArrayObject();
            for ($i = 0; $i < $count; $i++) {
                $rows->append(new Row($this, $result[$i]));
            }
            $rows = array_map(function ($row) use ($relations) {
                // check if their are relations columns with a foreign key

                if (count($relations) > 0) {
                    /**
                     * @var Model[] $relations
                     */
                    foreach ($relations as $relation) {
                        $foreignKey = array_filter($this->schema[$relation->table]["columns"], function ($value) {
                            return is_array($value);
                        });
                        $foreignKey = array_keys($foreignKey)[0];
                        $rowRel = $relation->find([
                            $foreignKey => $row->get($this->primaryKey)
                        ]);

                        if (count($rowRel) > 0) {
                            $row->set($relation->table, $rowRel);
                        }
                    }
                }
                return $row;
            }, $rows->getArrayCopy());
            return new ArrayObject($rows);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return $this;
        }
    }

    private function recursiveWhere(Select $select, $schema, $data, $relationData, $table, $primaryKey, $first){
        // verify if $data[$first] exist 
        if (!is_array($data[$first])) {
                $select->where($table . "." . $first, "=", $data[$first]);
                return $select;
        } else {
            $first = array_key_first($data[$first]);
            $table = array_key_first($data);
            if(is_array($data[$table][$first])){
                $first = array_key_first($data[$table][$first]);
                $table = array_key_first($data[$table]);
                $data = $data[$table][$first];
                $this->recursiveWhere($select, $schema, $data, $relationData, $table, $primaryKey, $first);
            }
        }
        return $select;
    }

    private function recursiveAndWhere(Select $select, $schema, $data, $relationData, $table, $primaryKey){
        foreach ($relationData as $key => $value) {
            if(is_string($value)){
                break;
            }
            foreach ($value as $k => $v) {
                if(!is_array($v)){
                    $select->andWhere($key . "." . $k, "=", $v);

                }else{
                    $k = array_key_first($v);
                    $v = $v[$k];
                    $key = array_key_first($relationData);	
                    $relationData = $relationData[$key];
                    $this->recursiveAndWhere($select, $schema, $v, $relationData, $key, $primaryKey);
                }
            }
        }
        if(is_array($data)){
        foreach ($data as $key => $value) {
            if (!is_array($value))
                $select->andWhere($this->table . "." . $key, "=", $value);
        }
    }
        return $select;
    }

    private function recursiveSelect(Select $select, $schema, $relationsDatas, $table, $primaryKey){
           // inner join the relation table 
           foreach ($relationsDatas as $key => $value) {
            if(is_string($value)){
                break;
            }
            $foreignKey = array_filter($schema[$key]["columns"], function ($value) {
                return is_array($value);
            });

            $foreignKey = array_keys($foreignKey)[0];
            $select->join($key, $table . "." . $primaryKey, "=", $key . "." . $foreignKey);
            // check if $value is an array 
            if (is_array($value)) {
                // get the next table name
                $nextTable = $key;
                // get the next schema 
                $nextSchema = $schema[$nextTable]["columns"];
                // get the next relation data
                $nextRelationData = $relationsDatas[$nextTable];
                // get the next primary key
                $nextPrimaryKey = "id";
                $this->recursiveSelect($select, $nextSchema, $nextRelationData, $nextTable, $nextPrimaryKey);
            }else{
                return $select;
            }
        }
        return $select;
    }
    /**
     * Find one row in the table based on the data (recursive search is possible)
     * @param array $data
     * @return Row|Model
     */
    public function findOne(array $data)
    {
        $relationsDatas = array_filter($data, function ($value) {
            return is_array($value);
        });
        $relations = array_filter($this->columns, function ($value) {
            return $value instanceof Model;
        });

        // get all columns of the table 
        $tableColumns = array_filter($this->schema, function ($value) {
            return !is_array($value);
        });

        $tableColumns = array_keys($tableColumns);
        $arrayOfColumn = ["*"];
        if (count($relationsDatas) > 0) {
            $arrayOfColumn = array_map(function ($value) {
                return $this->table . "." . $value . " as " . $value;
            }, $tableColumns);
        }
        $select = $this->builder->select($arrayOfColumn);
        $select->from($this->table);
        // select the first element of the array
        $first = array_key_first($data);

        if (count($relationsDatas) == 0) {
            $select->where($first, "=", $data[$first]);
            // remove the first element of the array 
            array_shift($data);
            foreach ($data as $key => $value) {
                if (!is_array($value))
                    $select->andWhere($key, "=", $value);
            }
        } else {

            // inner join the relation table 
           $select = $this->recursiveSelect($select, $this->schema, $relationsDatas, $this->table, $this->primaryKey);
            // select the first element of the array
            // check from where table the column is

           $select = $this->recursiveWhere($select, $this->schema, $data,$relationsDatas , $this->table, $this->primaryKey, $first);

           $select = $this->recursiveAndWhere($select, $this->schema, $data, $relationsDatas, $this->table, $this->primaryKey);
        }
        
        try {
            $stmt = $this->pdo->query($select->getSQL());
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $row = new Row($this, $result);
            if (count($relations) > 0) {
                /**
                 * @var Model[] $relations
                 */
                foreach ($relations as $relation) {
                    $foreignKey = array_filter($this->schema[$relation->table]["columns"], function ($value) {
                        return is_array($value);
                    });
                    $foreignKey = array_keys($foreignKey)[0];
                    $rowRel = $relation->find([
                        $foreignKey => $row->get($this->primaryKey)
                    ]);
                    if (count($rowRel) > 0) {
                        $row->set($relation->table, $rowRel);
                    }
                }
            }
            return $row;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return $this;
        }
    }
}

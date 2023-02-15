<?php

namespace Sqliberty;

use ArrayObject;
use PDO;
use Sqliberty\Builder\CollectionSet;
use Sqliberty\Builder\Set;

/**
 * Class Row (Row is a row in the database)
 * @package Sqliberty
 */
class Row extends ArrayObject
{
    private Model $model;
    private PDO $pdo;
    private string $error = "";
    public function __construct(Model $model,array $data)
    {
        parent::__construct($data);
        $this->model = $model;
        $this->pdo = $model->getPdo();
    }
    /**
     * Get a single property from the row
     * @param string $name
     * @return string|RowsArray
     */
    public function get(string $name)
    {
        return $this->offsetGet($name);
    }
    /**
     * Set a single property from the row
     * @param string $name
     * @param string $value
     */
    public function set(string $name, $value)
    {
        $this->offsetSet($name, $value);
    }

    public function __isset(string $name)
    {
        return $this->offsetExists($name);
    }

    public function __unset(string $name)
    {
        $this->offsetUnset($name);
    }
    /**
     * Find inside the model 
     * @return \ArrayObject<Row>
     */
    public function find(array $data)
    {
        return $this->model->find($data);
    }

    public function has(string $name): bool
    {
        return $this->offsetExists($name);
    }
    /**
     * Update the current row
     * @param array $data
     * @return Row
     */
    public function update(array $data): self
    {
     foreach ($data as $key => $value) {
         // Check if the key is a reference or if it doesn't exist
         if(!$value instanceof RowsArray) {
            if($this->has($key)) {
                // check if the value is the primary key
                if($key != $this->model->getSchema()->getPrimaryKey()) {
                    $this->set($key, $value);
                }
            }
        }
     }
        return $this;
    }
    /**
     * Save modifications to the row and update the database
     * @return Row
     */
    public function save(): self|bool
    {
        $update = $this->model->update($this->model->getSchema()->getTable());
        $col = new CollectionSet(); 

        foreach ($this as $key => $value) {
           if(!$value instanceof RowsArray) {
                if($this->has($key)) {
                    $col->addSet(new Set($key, $value));
                }
            }
        }
        $update->set($col);
        $key = $this->model->getSchema()->getPrimaryKey();
        $update->where($key,"=", $this->get($key));
        try {
            $this->pdo->exec($update->getSQL());
            return $this;
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function delete(): bool
    {
        $delete = $this->model->delete($this->model->getSchema()->getTable());
        $key = $this->model->getSchema()->getPrimaryKey();
        $delete->where($key,"=", $this->get($key));
        try {
            $this->pdo->exec($delete->getSQL());
            return true;
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function getError(): string
    {
        return $this->error;
    }
}
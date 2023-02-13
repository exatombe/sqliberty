<?php

namespace Sqliberty;

use ArrayObject;
/**
 * Class Row (Row is a row in the database)
 * @package Sqliberty
 */
class Row extends ArrayObject
{
    private Model $model;
    public function __construct(Model $model,array $data)
    {
        parent::__construct($data);
        $this->model = $model;
    }
    /**
     * Get a single property from the row
     * @param string $name
     * @return string|Row[]
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
    /**
     * Update the current row
     * @param array $data
     * @return Row
     */
    public function update(array $data): self
    {
     foreach ($data as $key => $value) {
         $this->offsetSet($key, $value);
     }
        return $this;
    }
    /**
     * Save modifications to the row and update the database
     * @return Row
     */
    public function save(): self
    {
        return $this;
    }

    public function delete(): bool
    {
        return true;
    }
}
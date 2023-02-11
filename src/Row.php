<?php

namespace Sqliberty;

use ArrayObject;

class Row extends ArrayObject
{
    private Model $model;
    public function __construct(Model $model,array $data)
    {
        parent::__construct($data);
        $this->model = $model;
    }

    public function get(string $name)
    {
        return $this->offsetGet($name);
    }

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

    public function find(array $data)
    {
        return $this->model->find($data);
    }
    
    public function update(array $data): self
    {
     foreach ($data as $key => $value) {
         $this->offsetSet($key, $value);
     }
        return $this;
    }

    public function save(): self
    {
        $this->model->update($this->getArrayCopy());
        return $this;
    }
}
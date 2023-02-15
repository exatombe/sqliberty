<?php

namespace Sqliberty;

use ArrayObject;

class RowsArray extends ArrayObject{
    private Model $model;
    public function __construct(Model $model,array $data = [])
    {
        parent::__construct([]);
        $this->model = $model;
    }

    public function get(int $index): Row
    {
        return $this->offsetGet($index);
    }

    public function set(int $index, Row $row)
    {
        $this->offsetSet($index, $row);
    }

    public function has(int $index): bool
    {
        return $this->offsetExists($index);
    }

    public function remove(int $index)
    {
        $this->offsetUnset($index);
    }

    public function __isset(string $index)
    {
        return $this->offsetExists($index);
    }

    public function __unset(string $index)
    {
        $this->offsetUnset($index);
    }

    public function add(array $data): self
    {
        $row = $this->model->create($data);
        if($row){
            $this->set($row->get($this->model->getSchema()->getPrimaryKey()), $row);
        }else{
            throw new \Exception($this->model->error);
        }
        return $this;
    }

    public function first(): Row|null
    {
        if($this->count() > 0){
            foreach ($this as $row) {
                return $row;
            }
        }else{
            return null;
        }
    }
    

}
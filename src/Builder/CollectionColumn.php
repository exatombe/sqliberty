<?php

namespace Sqliberty\Builder;

use ArrayObject;

class CollectionColumn extends ArrayObject
{
    public function __construct()
    {
        parent::__construct();
    }

    public function addColumn(Column $column): self
    {
        $this->offsetSet($column->name, $column);
        return $this;
    }

    public function getColumn(string $name): Column
    {
        return $this->offsetGet($name);
    }

    public function removeColumn(string $name): self
    {
        $this->offsetUnset($name);
        return $this;
    }


    public function __toString()
    {
        $columns = "";
        foreach ($this as $column) {
            $columns .= $column . ", ";
        }
        return substr($columns, 0, -2);
    }
}

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
        $this->append($column);
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
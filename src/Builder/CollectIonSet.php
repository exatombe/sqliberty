<?php

namespace Sqliberty\Builder;

use ArrayObject;

class CollectionSet extends ArrayObject
{
    public function __construct()
    {
        parent::__construct();
    }

    public function addSet(Set $set): self
    {
        $this->append($set);
        return $this;
    }

    public function __toString()
    {
        $sets = "";
        foreach ($this as $set) {
            $sets .= $set . ", ";
        }
        return substr($sets, 0, -2);
    }
}
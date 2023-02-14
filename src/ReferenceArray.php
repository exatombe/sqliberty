<?php

namespace Sqliberty;

use ArrayObject;

class ReferenceArray extends ArrayObject
{
    public function __construct()
    {
        parent::__construct();
    }

    public function set(string $table, Model $ref): self
    {
        $this->offsetSet($table, $ref);
        return $this;
    }

    public function get(string $table): Model
    {
        return $this->offsetGet($table);
    }

    public function has(string $table): bool
    {
        return $this->offsetExists($table);
    }

    public function __toString()
    {
        $references = "";
        foreach ($this as $reference) {
            $references .= $reference . ", ";
        }
        return substr($references, 0, -2);
    }
}
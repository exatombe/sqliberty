<?php

namespace Sqliberty\Interfaces;

use Sqliberty\Schema;
use Sqliberty\Model;
use Sqliberty\Row;
use Sqliberty\RowsArray;


interface ModelInterface
{
    /**
     * Get the generated schema for the model
     * @return Schema
     */
    public function getSchema(): Schema;
    /**
     * Make a new row in the database
     * @return string
     */
    public function create(array $data): Row;
    /**
     * Get a row from the database based on the primary key if it's set 
     * @param int $id
     * @return Row
     */
    public function get(int $id): Row;

    /**
     * Get all rows from the database based on data given 
     * @return RowsArray
     */
    public function find(array $data): RowsArray;
    /**
     * Get a single row from the database based on data given 
     * @return Row
     */
    public function findOne(array $data): Row;
    
}
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
     * @return Row|bool
     */
    public function create(array $data): Row|bool;
    /**
     * Get a row from the database based on the primary key if it's set
     * @param int $id
     * @return Row
     */
    public function get(int $id): Row|bool|null;

    /**
     * Get all rows from the database based on data given
     * @return RowsArray
     */
    public function findAll(array $data): RowsArray|bool;
    /**
     * Get a single row from the database based on data given
     * @return Row
     */
    public function findOne(array $data): Row|bool|null;

}

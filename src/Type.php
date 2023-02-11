<?php

namespace Sqliberty;


abstract class Type{
     const INT = "INT";
     const VARCHAR = "VARCHAR";
     const TEXT = "TEXT";
     const DATETIME = "DATETIME";
     const TIMESTAMP = "TIMESTAMP";
     const DATE = "DATE";
     const TIME = "TIME";
     const YEAR = "YEAR";
     const TINYINT = "TINYINT";
     const SMALLINT = "SMALLINT";
     const MEDIUMINT = "MEDIUMINT";
     const BIGINT = "BIGINT";
     const DECIMAL = "DECIMAL";
     const FLOAT = "FLOAT";
     const DOUBLE = "DOUBLE";
     const REAL = "REAL";
     const BIT = "BIT";
     const BOOLEAN = "BOOLEAN";
     const SERIAL = "SERIAL";
     const BINARY = "BINARY";
     const VARBINARY = "VARBINARY";
     const TINYBLOB = "TINYBLOB";
     const BLOB = "BLOB";
     const MEDIUMBLOB = "MEDIUMBLOB";
     const LONGBLOB = "LONGBLOB";
     const TINYTEXT = "TINYTEXT";
     const MEDIUMTEXT = "MEDIUMTEXT";
     const LONGTEXT = "LONGTEXT";
     const ENUM = "ENUM";
     const SET = "SET";
     const JSON = "JSON";
}
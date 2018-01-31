<?php

/**----------------------------------------------------------------------------
BSD 3-Clause License

Copyright (c) 2017, ND.K
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this
  list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice,
  this list of conditions and the following disclaimer in the documentation
  and/or other materials provided with the distribution.

* Neither the name of the copyright holder nor the names of its
  contributors may be used to endorse or promote products derived from
  this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
-----------------------------------------------------------------------------*/

define("MYSQLHOST", "");
define("MYSQLDB", "");
define("MYSQLUSER", "");
define("MYSQLPW", "");

class DB {
    
    /**
     * PREDEFINED CLASS VARIABLES
     */
    public static $handler = null;
    private static $prev_handler = null;
    private static $valid_col_names = null;
    
    /**
     * INIT WRAPPER CLASS
     */
    public static function init() {
        self::$valid_col_names = "/[a-z0-9_-]+/i";
        $db = new PDO("mysql:host=".MYSQLHOST.";dbname=".MYSQLDB, MYSQLUSER, MYSQLPW);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->query("SET NAMES 'utf8'");
        self::$handler =& $db;
    }
    
    /**
     * REPLACE INTERNAL DATABASE HANDLER WITH OTHER PDO CONNECTOR
     * @var OBJECT database connection
     */
    
    public static function setHandler($handler) {
        self::$prev_handler = self::$handler;
        $handler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $handler->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$handler = $handler;
    }
    
    /**
     * REVERSE TO PREVIOUS HANDLER IF NEW EXTERNAL HANDLER WAS GIVEN
     */
    
    public static function reverseHandler() {
        if (self::$prev_handler !== null) {
            self::$handler = self::$prev_handler;
            self::$prev_handler = null;
        }
    }
    
    /**
     * GET QUERY DATA FROM DATABASE
     * @var STRING QRY QUERY TO EXECUTE
     * @var ARRAY STATMENTS ARRAY
     * @return ARRAY SELECT RESULTS
     */
    public static function get($qry, $statements=array()) {
        if (empty($statements)) {
            $qry = self::$handler->query($qry);
        }
        else {
            $qry = self::$handler->prepare($qry);
            $qry->execute($statements);
        }
        return $qry->fetchAll();
    }
    
    /**
     * ALIAS FUNCTION FOR GET FUNCTION
     */
    public static function getArray($qry, $statements=array()) {
        return self::get($qry, $statements);
    }
    
    /**
     * ENABLES TRANSACTION
     */
    public static function beginTrans() {
        self::$handler->beginTransaction();
    }
    
    /**
     * EXECUTE STORED TRANSACTIONS
     */
    public static function commit() {
        self::$handler->commit();
    }
    
    /**
     * ROLL BACK TRANSACTIONS IN CASE OF ERROR
     */
    public static function rollBack() {
        self::$handler->rollBack();
    }
    
    /**
     * INSERT DATA INTO DATABASE
     * @var STRING TABLE NAME
     * @var ARRAY DATA (FORMAT: "COLUMNS" => "VALUE")
     * @return INT LAST ROW ID INSERTED
     */
    public static function insert($table, $data) {
        $keys = array();
        $qmarks = array();
        $values = array();
        foreach($data as $key => $value) {
            if (!preg_match(self::$valid_col_names, $key)) {
                throw new Exeption("Invalid column detected => ".$key);
            }
            array_push($keys, $key);
            array_push($qmarks, "?");
            array_push($values, $value);
        }
        $keys_str = implode(", ", $keys);
        $qmarks_str = implode(", ", $qmarks);
        $qry = self::$handler->prepare("INSERT INTO {$table} ({$keys_str}) VALUES ({$qmarks_str})");
        foreach ($values as $key => $val) {
            $qry->bindValue($key+1, $val);
        }
        $qry->execute();
        return self::$handler->lastInsertId();
    }
    
    /**
     * UPDATE DATA IN DATABASE
     * @var STRING TABLE NAME
     * @var ARRAY DATA (FORMAT: "COLUMN" => "VALUE")
     * @var STRING SQL WHERE STRING
     * @var ARRAY SQL WHERE DATA (USED IF WHERE STRING CONTAINS "?")
     */
    public static function update($table, $data, $where=false, $where_data=false) {
        $keys = array();
        $values = array();
        foreach($data as $key => $val) {
            if (!preg_match(self::$valid_col_names, $key)) {
                throw new Exeption("Invalid column detected => ".$key);
            }
            array_push($keys, "{$key}=?");
            array_push($values, $val);
        }
        $keys_str = implode(", ", $keys);
        $qry_str = "UPDATE {$table} SET {$keys_str}";
        if ($where != false) {
            $qry_str .= " WHERE {$where}";
        }
        $qry = self::$handler->prepare($qry_str);
        $n = 1;
        foreach ($values as $val) {
            $qry->bindValue($n, $val);
            $n += 1;
        }
        if ($where_data != false) {
            foreach($where_data as $val) {
                $qry->bindValue($n, $val);
                $n += 1;
            }
        }
        $qry->execute();
    }
    
    /**
     * DELETE FROM DATABASE
     * @var STRING TABLE NAME
     * @var STRING SQL WHERE STRING
     * @var ARRAY SQL WHERE DATA
     */
    public static function remove($table, $where=false, $where_data=false) {
        $qry_str = "DELETE FROM {$table}";
        if ($where != false) {
            $qry_str .= " WHERE {$where}"; 
        }
        $qry = self::$handler->prepare($qry_str);
        if ($where_data != false) {
            foreach($where_data as $n => $val) {
                $qry->bindValue($n+1, $val);
            }
        }
        $qry->execute();
    }
    
    /**
     * EXECUTE PLAIN QUERY
     * @var STRING QUERY TO EXECUTE
     * @var ARRAY params
     */
    public static function exec($qry, $params=null) {
        if ($params) {
            $qry = self::$handler->prepare($qry);
            $qry->execute((array) $params);
        }
        else {
            self::$handler->query($qry);
        }
    }

    /**
     * ALIAS FOR EXEC METHOD
     */
    public static function query($qry, $params=null) {
       self::exec($qry, $params);
    }
    
    /**
     * CHECKS IF THERE IS A TRANSACITON IN PROGRESS
     * @var NONE
     * @return BOOL TRUE/FALSE
     */
    public static function hasTrans() {
        return self::$handler->inTransaction();
    }
    
    /**
     * GET COLUMN NAMES FROM TABLE
     * @var STRING TABLE NAME
     * @var ARRAY COLUMN NAME FILTER
     */
    public static function getColumnNames($table, $filter=array()) {
        $table = (string) $table;
        $filter = (array) $filter;
        $res = self::get("
            SELECT column_name FROM information_schema.columns
            WHERE table_name=?
            ORDER BY ordinal_position ASC
            ", array($table));
        $columns = array();
        foreach ($res as $col) {
            if (empty($filter)) {
                array_push($columns, $col['column_name']);
            }
            else if (in_array($col['column_name'], $filter)) {
                array_push($columns, $col['column_name']);
            }
        }
        return $columns;
    }
    
}

DB::init();

?>

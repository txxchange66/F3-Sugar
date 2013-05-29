<?php

/**
    SQL Table Schema Builder extension for the PHP Fat-Free Framework

    The contents of this file are subject to the terms of the GNU General
    Public License Version 3.0. You may not use this file except in
    compliance with the license. Any of the license terms and conditions
    can be waived if you get permission from the copyright holder.

    crafted by   __ __     __
                |__|  |--.|  |--.-----.-----.
                |  |    < |    <|  -__|-- __|
                |__|__|__||__|__|_____|_____|

    Copyright (c) 2013 by ikkez
    Christian Knuth <ikkez0n3@gmail.com>
    https://github.com/ikkez/F3-Sugar/

        @package DB
        @version 2.0.0
 **/


namespace DB\SQL;

use DB\SQL;

class Schema extends DB_Utils {

    public
        $dataTypes = array(
            'BOOLEAN' =>    array('mysql|sqlite2?' => 'BOOLEAN',
                                  'pgsql' => 'text',
                                  'mssql|sybase|dblib|odbc|sqlsrv' => 'bit',
                                  'ibm' => 'numeric(1,0)',
            ),
            'INT1' =>       array('mysql' => 'TINYINT UNSIGNED',
                                  'sqlite2?' => 'integer',
                                  'mssql|sybase|dblib|odbc|sqlsrv' => 'tinyint',
                                  'pgsql|ibm' => 'smallint',
            ),
            'INT2' =>       array('mysql' => 'SMALLINT',
                                  'sqlite2?' => 'integer',
                                  'pgsql|ibm|mssql|sybase|dblib|odbc|sqlsrv' => 'smallint',
            ),
            'INT4' =>       array('sqlite2?|pgsql|sybase|odbc|sqlsrv|imb' => 'integer',
                                  'mysql|mssql|dblib' => 'int',
            ),
            'INT8' =>       array('sqlite2?' => 'integer',
                                  'pgsql|mysql|mssql|sybase|dblib|odbc|sqlsrv|imb' => 'bigint',
            ),
            'FLOAT' =>      array('mysql|sqlite2?' => 'FLOAT',
                                  'pgsql' => 'double precision',
                                  'mssql|sybase|dblib|odbc|sqlsrv' => 'float',
                                  'imb' => 'decfloat'
            ),
            'DOUBLE' =>     array('mysql|sqlite2?|ibm' => 'DOUBLE',
                                  'pgsql|sybase|odbc|sqlsrv' => 'double precision',
                                  'mssql|dblib' => 'decimal',
            ),
            'VARCHAR128' => array('mysql|pgsql|sqlite2?|ibm|mssql|sybase|dblib|odbc|sqlsrv' => 'varchar(128)',
            ),
            'VARCHAR256' => array('mysql|pgsql|sqlite2?|ibm|mssql|sybase|dblib|odbc|sqlsrv' => 'varchar(256)',
            ),
            'VARCHAR512' => array('mysql|pgsql|sqlite2?|ibm|mssql|sybase|dblib|odbc|sqlsrv' => 'varchar(512)',
            ),
            'TEXT' =>       array('mysql|sqlite2?|pgsql|mssql|sybase|dblib|odbc|sqlsrv' => 'text',
                                  'ibm' => 'BLOB SUB_TYPE TEXT',
            ),
            'LONGTEXT' =>   array('mysql' => 'LONGTEXT',
                                  'sqlite2?|pgsql|mssql|sybase|dblib|odbc|sqlsrv' => 'text',
                                  'ibm' => 'CLOB(2000000000)',
            ),
            'DATE' =>       array('mysql|sqlite2?|pgsql|mssql|sybase|dblib|odbc|sqlsrv|ibm' => 'date',
            ),
            'DATETIME' =>   array('pgsql' => 'timestamp without time zone',
                                  'mysql|sqlite2?|mssql|sybase|dblib|odbc|sqlsrv' => 'datetime',
                                  'ibm' => 'timestamp',
            ),
            'TIMESTAMP' =>  array('mysql|dblib|sqlsrv|ibm' => 'timestamp',
                                  'pgsql|odbc' => 'timestamp without time zone',
                                  'sqlite2?|mssql|sybase'=>'DATETIME',
            ),
            'BLOB' =>       array('mysql|odbc|sqlite2?|ibm' => 'blob',
                                  'pgsql' => 'bytea',
                                  'mssql|sybase|dblib' => 'image',
                                  'sqlsrv' => 'varbinary(max)',
            ),
        ),
        $defaultTypes = array(
            'CUR_STAMP' =>  array('mysql|mssql|sybase|dblib|odbc|sqlsrv' => 'CURRENT_TIMESTAMP',
                                  'pgsql' => 'LOCALTIMESTAMP(0)',
                                  'sqlite2?' => "(datetime('now','localtime'))",
            ),
        );

    public
        $name;

    /** @var \Base */
    protected $fw;

    const
        // DataTypes
        DT_BOOL = 'BOOLEAN',
        DT_BOOLEAN = 'BOOLEAN',
        DT_INT1 = 'INT1',
        DT_TINYINT = 'INT1',
        DT_INT2 = 'INT2',
        DT_SMALLINT = 'INT2',
        DT_INT4 = 'INT4',
        DT_INT = 'INT4',
        DT_INT8 = 'INT8',
        DT_BIGINT = 'INT8',
        DT_FLOAT = 'FLOAT',
        DT_DOUBLE = 'DOUBLE',
        DT_DECIMAL = 'DOUBLE',
        DT_VARCHAR128 = 'VARCHAR128',
        DT_VARCHAR256 = 'VARCHAR256',
        DT_VARCHAR512 = 'VARCHAR512',
        DT_TEXT = 'TEXT',
        DT_LONGTEXT = 'LONGTEXT',
        DT_DATE = 'DATE',
        DT_DATETIME = 'DATETIME',
        DT_TIMESTAMP = 'TIMESTAMP',
        DT_BLOB = 'BLOB',
        DT_BINARY = 'BLOB',

        // column default values
        DF_CURRENT_TIMESTAMP = 'CUR_STAMP',

        // error messages
        TEXT_NoDataType = 'The specified datatype %s is not defined in %s driver',
        TEXT_NotNullFieldNeedsDefault = 'You cannot add the not nullable column `%s´ without specifying a default value';

    public function __construct(\DB\SQL $db)
    {
        $this->fw = \Base::instance();
        parent::__construct($db);
    }

    /**
     * get a list of all databases
     * @return array|bool
     */
    public function getDatabases() {
        $cmd = array(
            'mysql' => 'SHOW DATABASES',
            'pgsql' => 'SELECT datname FROM pg_catalog.pg_database',
            'mssql|sybase|dblib|sqlsrv' => 'EXEC SP_HELPDB',
        );
        $query = $this->findQuery($cmd);
        if (!$query) return false;
        $result = $this->db->exec($query);
        foreach($result as &$db)
            if (is_array($db)) $db = array_shift($db);
        return $result;
    }

    /**
     * get all tables of current DB
     * @return bool|array list of tables, or false
     */
    public function getTables()
    {
        $cmd = array(
            'mysql' => array(
                "show tables"),
            'sqlite2?' => array(
                "SELECT name FROM sqlite_master WHERE type='table' AND name!='sqlite_sequence'"),
            'mssql|sqlsrv|pgsql|sybase|dblib|odbc' => array(
                "select table_name from information_schema.tables where table_schema = 'public'"),
            'ibm' => array("select TABLE_NAME from sysibm.tables"),
        );
        $query = $this->findQuery($cmd);
        if (!$query[0]) return false;
        $tables = $this->db->exec($query[0]);
        if ($tables && is_array($tables) && count($tables) > 0)
            foreach ($tables as &$table)
                $table = array_shift($table);
        return $tables;
    }

    /**
     * returns a table object for creation
     * @param $name
     * @return bool|TableCreator
     */
    public function createTable($name)
    {
        return new TableCreator($name,$this);
    }

    /**
     * returns a table object for altering operations
     * @param $name
     * @return bool|TableAlterer
     */
    public function alterTable($name)
    {
        return new TableAlterer($name,$this);
    }

    /**
     * rename a table
     * @param string $name
     * @param string $new_name
     * @param bool $exec
     * @return bool
     */
    public function renameTable($name, $new_name, $exec = true)
    {
        $name = $this->db->quotekey($name);
        $new_name = $this->db->quotekey($new_name);
        if (preg_match('/odbc/', $this->db->driver())) {
            $querys = array();
            $querys[] = "SELECT * INTO $new_name FROM $name;";
            $querys[] = $this->dropTable($name, false);
            return ($exec) ? $this->execQuerys($querys) : implode("\n",$querys);
        } else {
            $cmd = array(
                'sqlite2?|pgsql' =>
                    "ALTER TABLE $name RENAME TO $new_name;",
                'mysql|ibm' =>
                    "RENAME TABLE $name TO $new_name;",
                'mssql|sqlsrv|sybase|dblib|odbc' =>
                    "sp_rename $name, $new_name"
            );
            $query = $this->findQuery($cmd);
            return ($exec) ? $this->execQuerys($query) : $query;
        }
    }

    /**
     * drop a table
     * @param \DB\SQL\TableBuilder|string $name
     * @param bool $exec
     * @return bool
     */
    public function dropTable($name, $exec = true)
    {
        if (is_object($name) && $name instanceof TableBuilder)
            $name = $name->name;
        $query = 'DROP TABLE IF EXISTS '.$this->db->quotekey($name).';';
        return ($exec) ? $this->execQuerys($query) : $query;
    }

}

abstract class TableBuilder extends DB_Utils {

    protected   $columns, $pkeys, $queries;
    public      $name, $schema;

    /**
     * @param string $name
     * @param Schema $schema
     */
    public function __construct($name, Schema $schema)
    {
        if (!$this->valid($name)) return false;
        $this->name = $name;
        $this->schema = $schema;
        $this->columns = array();
        $this->queries = array();
        $this->pkeys = array('id');
        parent::__construct($schema->db);
    }

    /**
     * generate SQL query and execute it if $exec is true
     * @param bool $exec
     */
    abstract public function build($exec = TRUE);

    /**
     * add a new column to this table
     * @param string|Column $key column name or object
     * @param null|array $args optional config array
     * @return \DB\SQL\Column
     */
    public function addColumn($key,$args = null)
    {
        if($key instanceof Column) {
            $args = $key->getColumnArray();
            $key = $key->name;
        }
        if (array_key_exists($key,$this->columns))
            trigger_error(sprintf("column '%s' already exists",$key));
        $column = new Column($key, $this);
        if($args)
            foreach ($args as $arg => $val)
                $column->{$arg} = $val;
        // TODO: improve that, skip default pkey fields
        if (count($this->pkeys) == 1 && in_array($key,$this->pkeys))
            return $column;
        return $this->columns[$key] =& $column;
    }

}

class TableCreator extends TableBuilder {
    
    /**
     * generate SQL query for creating a basic table, containing an ID serial field
     * and execute it if $exec is true, otherwise just return the generated query string
     * @param bool $exec
     * @return bool|TableAlterer|string
     */
    public function build($exec = TRUE)
    {
        // check if already existing
        if ($exec && in_array($this->name, $this->schema->getTables()))
            trigger_error(sprintf("table '%s' already exists. cannot create it.",$this->name));
        $cols = '';
        if (!empty($this->columns))
            foreach ($this->columns as $cname => $column) {
                // no defaults for TEXT type
                if($column->default !== false && is_int(strpos(strtoupper($column->type),'TEXT'))) {
                    trigger_error(sprintf("column `%s` of type text can't have a default value", $column->name));
                    return false;
                }
                $cols .= ', '.$column->getColumnQuery();
            }
        $table = $this->db->quotekey($this->name);
        $cmd = array(
            'sqlite2?|sybase|dblib|odbc' =>
                "CREATE TABLE $table (id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT".$cols.")",
            'mysql' =>
                "CREATE TABLE $table (id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT".$cols.") DEFAULT CHARSET=utf8",
            'pgsql' =>
                "CREATE TABLE $table (id SERIAL PRIMARY KEY".$cols.")",
            'mssql' =>
                "CREATE TABLE $table (id INT PRIMARY KEY".$cols.");",
            'ibm' =>
                "CREATE TABLE $table (id INTEGER AS IDENTITY NOT NULL $cols, PRIMARY KEY(id));",
        );
        $query = $this->findQuery($cmd);
        if (!$exec)
            return $query;
        $this->execQuerys($query);
        return new TableAlterer($this->name,$this->schema);
    }
}

class TableAlterer extends TableBuilder {

    protected
        $colTypes, $rebuild_cmd;

    /**
     * generate SQL querys for altering the table and execute it if $exec is true,
     * otherwise return the generated query string
     */
    public function build($exec = TRUE)
    {
        // check if table exists
        if (!in_array($this->name, $this->schema->getTables()))
            trigger_error(sprintf("Unable to alter table '%s'. It does not exist.", $this->name));

        if ($sqlite = preg_match('/sqlite2?/', $this->db->driver())) {
            $sqlite_queries = array();
        }
        $rebuild = false;
        // add new columns
        foreach ($this->columns as $cname => $column) {
            // not nullable fields should have a default value, when altering a table
            if ($column->default === false && $column->nullable === false) {
                trigger_error(sprintf(self::TEXT_NotNullFieldNeedsDefault, $column->name));
                return false;
            }
            // no defaults for TEXT type
            if($column->default !== false && is_int(strpos(strtoupper($column->type),'TEXT'))) {
                trigger_error(sprintf("column `%s` of type text can't have a default value", $column->name));
                return false;
            }
            if ($column->pkey && !in_array($cname, $this->pkeys))
                $this->pkeys[] = $cname;

            $table = $this->db->quotekey($this->name);
            $col_query = $column->getColumnQuery();
            if ($sqlite) {
                // sqlite: dynamic column default only works when rebuilding the table
                if($column->default === Schema::DF_CURRENT_TIMESTAMP) {
                    $rebuild = true;
                    break;
                } else
                    $sqlite_queries[] = "ALTER TABLE $table ADD $col_query;";
            } else {
                $cmd = array(
                    'mysql|pgsql|mssql|sybase|dblib|odbc' =>
                        "ALTER TABLE $table ADD $col_query;",
                    'ibm' =>
                        "ALTER TABLE $table ADD COLUMN $col_query;",
                );
                $this->queries[] = $this->findQuery($cmd);
            }
        }
        if ($sqlite)
            if ($rebuild || !empty($this->rebuild_cmd)) $this->_sqlite_rebuild();
            else $this->queries += $sqlite_queries;
        if (empty($this->queries))
            return false;
        $result = ($exec) ? $this->execQuerys($this->queries) : $this->queries;
        $this->queries = $this->columns = $this->rebuild_cmd = array();
        $rebuild = false;
        return $result;
    }

    /**
     * rebuild a sqlite table with additional schema changes
     */
    protected function _sqlite_rebuild()
    {
        $new_columns = $this->columns;
        $existing_columns = $this->getCols(true);
        // find after sorts
        $after = array();
        foreach ($new_columns as $cname => $column)
            if(!empty($column->after))
                $after[$column->after][] = $cname;
        // find rename commands
        $rename = (!empty($this->rebuild_cmd) && array_key_exists('rename',$this->rebuild_cmd))
                  ? $this->rebuild_cmd['rename'] : array();
        // remember primary-key fields
        foreach ($existing_columns as $key => $col)
            if ($col['pkey'])
                $pkeys[] = array_key_exists($key,$rename) ? $rename[$key] : $key;
        // create new table
        $oname = $this->name;
        $this->queries[] = $this->rename($oname.'_temp', false);
        $newTable = $this->schema->createTable($oname);
        // add existing fields
        foreach ($existing_columns as $name => $col) {
            $colName = array_key_exists($name, $rename) ? $rename[$name] : $name;
            $newTable->addColumn($colName, $col)->passThrough();
            // add new fields with after flag
            if (array_key_exists($name,$after))
                foreach(array_reverse($after[$name]) as $acol) {
                    $newTable->addColumn($new_columns[$acol]);
                    unset($new_columns[$acol]);
                }
        }
        // add remaining new fields
        foreach($new_columns as $ncol)
            $newTable->addColumn($column)->passThrough();
        $this->queries[] = $newTable->build(false);
        // copy data
        if(!empty($existing_columns)) {
            foreach(array_keys($existing_columns) as $name) {
                $fields_from[] = $this->db->quotekey($name);
                $toName = array_key_exists($name, $rename) ? $rename[$name] : $name;
                $fields_to[] = $this->db->quotekey($toName);
            }
            $this->queries[] =
                'INSERT INTO '.$this->db->quotekey($newTable->name).' ('.implode(', ', $fields_to).') '.
                'SELECT '.implode(', ', $fields_from).' FROM '.$this->db->quotekey($this->name).';';
        }
        $this->queries[] = $this->drop(false);
        $this->name = $oname;
    }

    /**
     * get columns of a table
     * @param bool $types
     * @return array
     */
    public function getCols($types = false)
    {
        $columns = array();
        $schema = $this->db->schema($this->name, 0);
        if (!$types)
            return array_keys($schema);
        else
            foreach ($schema as $name => &$cols) {
                $default = ($cols['default'] === '') ? null : $cols['default'];
                if (!is_null($default) && (
                    is_int(strpos($this->findQuery($this->schema->defaultTypes['CUR_STAMP']), $default))
                        || $default == "('now'::text)::timestamp(0) without time zone")
                ) {
                    $default = 'CUR_STAMP';
                } else {
                    // remove single-qoutes in sqlite
                    if (preg_match('/sqlite2?/', $this->db->driver()))
                        $default = substr($default, 1, -1);
                    // extract value from character_data in postgre
                    if (preg_match('/pgsql/', $this->db->driver()) && !is_null($default))
                        if (is_int(strpos($default, 'nextval')))
                            $default = null; // drop autoincrement default
                        elseif (preg_match("/\'(.*)\'/", $default, $match))
                            $default = $match[1];
                }
                $cols['default'] = $default;
            }
        return $schema;
    }


    /**
     * removes a column from a table
     * @param $column
     * @return bool
     */
    public function dropColumn($column)
    {
        // TODO: fix that
        $colTypes = $this->getCols(true);
        // check if column exists
        if (!in_array($column, array_keys($colTypes))) return true;
        if (preg_match('/sqlite2?/', $this->db->driver())) {
            // SQlite does not support drop column directly
            // unset dropped field
            unset($colTypes[$column]);
            // remember primary-key fields
            foreach ($colTypes as $key => $col)
                if ($col['pkey']) {
                    $pkeys[] = $key;
                    if ($key == 'id') unset($colTypes[$key]);
                }
            $new = new self($this->db, $this->schema);
            if (!$new->db->inTransaction())
                $new->db->begin();
            $new->createTable($this->name.'_temp_drop');
            foreach ($colTypes as $name => $col)
                $new->addColumn($name, $col['type'], $col['nullable'], $col['default'], true);
            $fields = !empty($colTypes) ? ', '.implode(', ', array_keys($colTypes)) : '';
            $new->setPKs($pkeys);
            $new->db->exec('INSERT INTO `'.$new->name.'` '.
                'SELECT id'.$fields.' FROM '.$this->name);
            $tname = $this->name;
            $this->dropTable();
            $new->renameTable($tname);
            if ($new->db->inTransaction())
                $new->db->commit();
            $this->alterTable($tname);
            return true;
        } else {
            $quotedTable = $this->db->quotekey($this->name);
            $cmd = array(
                'mysql|mssql|sybase|dblib' => array(
                    "ALTER TABLE $quotedTable DROP $column;"),
                'pgsql|odbc|ibm' => array(
                    "ALTER TABLE $quotedTable DROP COLUMN $column;"),
            );
            $query = $this->findQuery($cmd);
            return $this->execQuerys($query);
        }
    }

    /**
     * rename a column
     * @param $name
     * @param $new_name
     * @return void
     */
    public function renameColumn($name, $new_name)
    {
        $existing_columns = $this->getCols(true);
        // check if column is already existing
        if (!in_array($name, array_keys($existing_columns)))
            trigger_error('cannot rename column. it does not exist.');
        if (in_array($new_name, array_keys($existing_columns)))
            trigger_error('cannot rename column. new column already exist.');
        
        if (preg_match('/sqlite2?/', $this->db->driver()))
            // SQlite does not support drop or rename column directly
            $this->rebuild_cmd['rename'][$name] = $new_name;
        elseif (preg_match('/odbc/', $this->db->driver())) {
            // no rename column for odbc, create temp column
            $this->addColumn($new_name, $existing_columns[$name])->passThrough();
            $this->queries[] = "UPDATE $this->name SET $new_name = $name";
            $this->dropColumn($name);
        } else {
            $existing_columns = $this->getCols(true);
            $quotedTable = $this->db->quotekey($this->name);
            $quotedColumn = $this->db->quotekey($name);
            $quotedColumnNew = $this->db->quotekey($new_name);
            $cmd = array(
                'mysql' =>
                    "ALTER TABLE $quotedTable CHANGE $quotedColumn $quotedColumnNew ".$existing_columns[$name]['type'].";",
                'pgsql|ibm' =>
                    "ALTER TABLE $quotedTable RENAME COLUMN $quotedColumn TO $quotedColumnNew;",
                'mssql|sybase|dblib' =>
                    "sp_rename $quotedTable.$quotedColumn, $quotedColumnNew;",
            );
            $this->queries[] = $this->findQuery($cmd);
        }
    }

    /**
     * rename this table
     * @param string $new_name
     * @param bool $exec
     * @return $this|bool
     */
    public function rename($new_name, $exec = true) {
        $query = $this->schema->renameTable($this->name, $new_name, $exec);
        $this->name = $new_name;
        return ($exec) ? $this : $query;
    }

    /**
     * drop this table
     * @param bool $exec
     * @return mixed
     */
    public function drop($exec = true) {
        return $this->schema->dropTable($this,$exec);
    }

    /**
     * create index on one or more columns
     * @param string|array $columns Column(s) to be indexed
     * @param bool $unique Unique index
     * @return bool
     */
    public function addIndex($columns, $unique = FALSE)
    {
        if (!is_array($columns))
            $columns = array($columns);
        $columns = array_map(array($this->db, 'quotekey'), $columns);
        $cols = implode(',', $columns);
        $name = implode('_', $columns);
        $index = $unique ? 'UNIQUE INDEX' : 'INDEX';
        $cmd = array(
            'pgsql|sqlite2?|ibm|mssql|sybase|dblib|odbc|sqlsrv' => array(
                "CREATE $index $name ON ".$this->db->quotekey($this->name)." ($cols);"
            ),
            'mysql' => array( //ALTER TABLE is used because of MySQL bug #48875
                "ALTER TABLE `$this->name` ADD $index `$name` ($cols);"
            ),
        );
        $query = $this->findQuery($cmd);
        return $this->execQuerys($query);
    }

    /**
     * set primary keys
     * @param $pks array
     * @return bool
     * @deprecated
     */
    public function __setPKs($pks)
    {
        // TODO: fix that
        $pk_string = implode(', ', $pks);
        if (preg_match('/sqlite2?/', $this->db->driver())) {
            $this->db->exec('DROP TRIGGER IF EXISTS '.$this->name.'_insert');
            // collect primary key field information
            $colTypes = $this->getCols(true);
            $pk_def = array();
            foreach ($pks as $name) {
                $dfv = $colTypes[$name]['default'];
                $df_def = ($dfv !== false) ? " DEFAULT '".$dfv."'" : '';
                $pk_def[] = $name.' '.$colTypes[$name]['type'].$df_def;
            }
            $currentPKs = array();
            foreach ($colTypes as $colname => $conf)
                if ($conf['pkey']) $currentPKs[] = $colname;
            // from comp to single pkey
            if (count($pks) <= 1 && count($currentPKs) >= 1) {
                // already the right pk
                if ((count($pks) == 1 && count($currentPKs) == 1) && $pks[0] == $currentPKs[0])
                    return true;
                // rebuild with single pk
                $oname = $this->name;
                $this->renameTable($this->name.'_temp');
                $this->createTable($oname);
                foreach ($colTypes as $name => $col)
                    $this->addColumn($name, $col['type'], $col['nullable'], $col['default'], true);
                $fields = implode(', ', array_keys($colTypes));
                $this->db->exec('INSERT INTO '.$this->name.'('.$fields.') '.
                'SELECT '.$fields.' FROM '.$this->name.'_temp');
                // drop old table
                $this->dropTable($this->name.'_temp');
                return true;
            }
            $pk_def = implode(', ', $pk_def);
            // fetch all new non primary key fields
            $newCols = array();
            foreach ($colTypes as $colname => $conf)
                if (!in_array($colname, $pks)) $newCols[$colname] = $conf;
            if (!$this->db->inTransaction())
                $this->db->begin();
            $oname = $this->name;
            // rename to temp
            $this->renameTable($this->name.'_temp');
            // find dynamic defaults
            foreach ($newCols as $name => $col)
                if ($col['default'] == self::DF_CURRENT_TIMESTAMP) $dyndef[$name] = $col;
            $dynfields = '';
            if (!empty($dyndef))
                foreach ($dyndef as $n => $col) {
                    $dynfields .= $n.' '.$col['type'].' '.(($col['nullable']) ? 'NULL' : 'NOT NULL').
                        ' DEFAULT '.$this->findQuery($this->defaultTypes[$col['default']]).',';
                    unset($newCols[$n]);
                }
            // create new origin table, with new private keys and their fields
            $this->db->exec("CREATE TABLE $oname ( $pk_def, $dynfields PRIMARY KEY ($pk_string) );");
            // add non-pk fields
            $this->alterTable($oname);
            foreach ($newCols as $name => $col)
                $this->addColumn($name, $col['type'], $col['nullable'], $col['default'], true);
            // create insert trigger to work-a-round autoincrement in multiple primary keys
            // is set on first PK if it's an int field
            if (strstr(strtolower($colTypes[$pks[0]]['type']), 'int'))
                $this->db->exec('CREATE TRIGGER '.$oname.'_insert AFTER INSERT ON '.$oname.
                ' WHEN (NEW.'.$pks[0].' IS NULL) BEGIN'.
                ' UPDATE '.$oname.' SET '.$pks[0].' = ('.
                ' select coalesce( max( '.$pks[0].' ), 0 ) + 1 from '.$oname.
                ') WHERE ROWID = NEW.ROWID;'.
                ' END;');
            // import all data
            $cols = $this->getCols();
            $fields = implode(', ', $cols);
            $this->db->exec('INSERT INTO '.$oname.'('.$fields.') '.
            'SELECT '.$fields.' FROM '.$oname.'_temp');
            // drop old table
            $this->dropTable($oname.'_temp');
            if (!$this->db->inTransaction())
                $this->db->commit();
            return true;

        } else {
            $cmd = array(
                'mssql|sybase|dblib|odbc' => array(
                    "CREATE INDEX ".$this->name."_pkey ON ".$this->name." ( $pk_string );"
                ),
                'mysql' => array(
                    "ALTER TABLE $this->name DROP PRIMARY KEY, ADD PRIMARY KEY ( $pk_string );"),
                'pgsql' => array(
                    "ALTER TABLE $this->name DROP CONSTRAINT ".$this->name.'_pkey;',
                    "ALTER TABLE $this->name ADD CONSTRAINT ".$this->name."_pkey PRIMARY KEY ( $pk_string );",
                ),
            );
            $query = $this->findQuery($cmd);
            return $this->execQuerys($query);
        }
    }

}

/**
 * defines a table column configuration
 * Class Column
 * @package DB\SQL
 */
class Column extends DB_Utils {

    public      $name, $type, $nullable, $default, $after, $index, $unique, $passThrough, $pkey;
    protected   $table, $schema;

    const
        TEXT_CurrentStampDataType = 'Current timestamp as column default is only possible for TIMESTAMP datatype';

    /**
     * @param string $name
     * @param TableBuilder $table
     */
    public function __construct($name, TableBuilder $table) {
        $this->name = $name;
        $this->nullable = true;
        $this->default = false;
        $this->after = false;
        $this->index = false;
        $this->unique = false;
        $this->passThrough = false;
        $this->pkey = false;

        $this->table = $table;
        $this->schema = $table->schema;
        parent::__construct($this->schema->db);
    }

    /**
     * @param string $datatype
     * @param bool $passThrough don't match datatype against DT array
     * @return $this
     */
    public function type($datatype, $passThrough = FALSE) {
        $this->type = $datatype;
        $this->passThrough = $passThrough;
        return $this;
    }

    public function passThrough($state = TRUE) {
        $this->passThrough = $state;
        return $this;
    }

    public function nullable($nullable) {
        $this->nullable = $nullable;
        return $this;
    }

    public function defaults($default) {
        $this->default = $default;
        return $this;
    }

    public function after($name) {
        $this->after = $name;
        return $this;
    }

    public function index($unique = FALSE) {
        $this->index = true;
        $this->unique = $unique;
        return $this;
    }

    public function primary() {
        $this->pkey = true;
        return $this;
    }

    /**
     * returns an array of this column configuration
     * @return array
     */
    public function getColumnArray()
    {
        $fields = array('name','type','passThrough','default','nullable',
                        'index','unique','after','pkey');
        $fields = array_flip($fields);
        foreach($fields as $key => &$val)
            $val = $this->{$key};
        unset($val);
        return $fields;
    }

    /**
     * generate SQL column definition query
     * @return bool|string
     */
    public function getColumnQuery()
    {
        if (!$this->type)
            trigger_error(sprintf('Cannot build a column query for `%s`: no column type set',$this->name));
        // prepare column types
        if ($this->passThrough)
            $type_val = $this->type;
        else {
            $type_val = $this->findQuery($this->schema->dataTypes[strtoupper($this->type)]);
            if (!$type_val) {
                trigger_error(sprintf(self::TEXT_NoDataType, strtoupper($this->type),
                    $this->db->driver()));
                return FALSE;
            }
        }
        // build query
        $query = $this->db->quotekey($this->name).' '.$type_val.' '.
            ($this->nullable ? 'NULL' : 'NOT NULL');
        // default value
        if ($this->default !== false) {
            $def_cmds = array(
                'sqlite2?|mysql|pgsql|mssql|sybase|dblib|odbc' => 'DEFAULT',
                'ibm' => 'WITH DEFAULT',
            );
            $def_cmd = $this->findQuery($def_cmds).' ';
            // timestamp default
            if ($this->default === Schema::DF_CURRENT_TIMESTAMP) {
                // check for right datatpye
                $stamp_type = $this->findQuery($this->schema->dataTypes['TIMESTAMP']);
                if ($this->type != 'TIMESTAMP' && // TODO: check that condition
                    ($this->passThrough && strtoupper($this->type) != strtoupper($stamp_type))
                )
                    trigger_error(self::TEXT_CurrentStampDataType);
                $def_cmd .= $this->findQuery($this->schema->defaultTypes[strtoupper($this->default)]);
            } else {
                // static defaults
                $pdo_type = preg_match('/int|bool/i', $type_val, $parts) ?
                    constant('\PDO::PARAM_'.strtoupper($parts[0])) : \PDO::PARAM_STR;
                $def_cmd .= ($this->default === NULL ? 'NULL' :
                    $this->db->quote(htmlspecialchars($this->default, ENT_QUOTES,
                        $this->f3->get('ENCODING')), $pdo_type));
            }
            $query .= ' '.$def_cmd;
        }
        if (!empty($this->after)) {
            // `after` feature only works for mysql
            if (preg_match('/mysql/', $this->db->driver())) {
                $after_cmd = 'AFTER '.$this->db->quotekey($this->after);
                $query .= ' '.$after_cmd;
            }
        }
        return $query;
    }
}


class DB_Utils {

    /** @var \DB\SQL */
    protected $db;

    /** @var \BASE */
    protected $f3;

    const
        TEXT_IllegalName = '%s is not a valid table or column name',
        TEXT_ENGINE_NOT_SUPPORTED = 'DB Engine `%s´ is not supported for this action.';

    /**
     * parse command array and return backend specific query
     * @param $cmd array
     * @return bool|string
     **/
    protected function findQuery($cmd)
    {
        $match = FALSE;
        foreach ($cmd as $backend => $val)
            if (preg_match('/'.$backend.'/', $this->db->driver())) {
                $match = TRUE;
                break;
            }
        if (!$match) {
            trigger_error(sprintf(self::TEXT_ENGINE_NOT_SUPPORTED, $this->db->driver()));
            return FALSE;
        }
        return $val;
    }

    /**
     * execute query stack
     * @param $query
     * @return bool
     * @deprecated
     */
    protected function execQuerys($query)
    {
        // TODO: i guess i do not need this anymore
        if ($this->db->exec($query) === false) return false;
        return true;
    }

    /**
     * check if valid table / column name
     * @param string $key
     * @return bool
     * @deprecated
     */
    protected function valid($key)
    {
        // TODO: superfluous, table and columns quotations works now
        if (preg_match('/^(\D\w+(?:\_\w+)*)$/', $key))
            return TRUE;
        // Invalid name
        trigger_error(sprintf(self::TEXT_IllegalName, var_export($key, TRUE)));
        return FALSE;
    }

    public function __construct(SQL $db) {
        $this->db = $db;
        $this->f3 = \Base::instance();
    }
}
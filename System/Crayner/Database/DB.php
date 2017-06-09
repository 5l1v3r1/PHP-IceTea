<?php

namespace System\Crayner\Database;

use System\Crayner\Hub\Singleton;
use System\Crayner\ConfigHandler\Configer;
use System\Crayner\Builder\Database\DatabaseFactory;

/**
* @author arbiyanto <arbiyantowijaya17@gmail.com>
*/

class DB extends DatabaseFactory
{
    use Singleton;

    /**
     *
     * @var bool
     */
    private $showErrorQuery;

    protected $optionWhere     = [], 
              $optionWhereData = [], 
              $optionJoin      = [],
              $optionOrder     = null,
              $optionLimit     = null,
              $optionSelect    = null,
              $table_name      = null;

    /**
     *
     * Override singleton
     *
     */
    public static function getInstance()
    {
        if (self::$instance === null || !((self::$instance)->pdo  instanceof \PDO)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     *
     * Constructor.
     *
     *
     *
     */
    public function __construct()
    {
        $this->showErrorQuery = Configer::errorQuery();
        try {
            $conf = Configer::database();
            $this->pdo = new \PDO($conf['driver'].":host=".$conf['host'].";port=".$conf['port'].";dbname=".$conf['dbname'], $conf['user'], $conf['pass'], array(
                    \PDO::ATTR_PERSISTENT => false
                ));
        } catch (\PDOException $e) {
            var_dump($e->getMessage());
        }
    }

    /**
     *
     * Execute Override
     *
     * @param   string  $statement
     * @param   array   $data
     * @return  \PDO
     */
    protected static function _execute(string $statement, array $data) 
    {
        $self      = self::getInstance();

        $statement = $self->makeStatement($statement);
        $make      = $self->pdo->prepare($statement);
        $data      = array_merge($data, $self->optionWhereData);

        $make->execute($data);
        $self->makeEmpty();

        $error  = $make->errorInfo();
        if ($error[1] and $self->showErrorQuery) {
            var_dump(array(
                    "Error" => $make->errorInfo()
                ));
        }

        return $make;
    }

    /**
     *
     * Make Query Statement
     *
     * @param   string  $statement
     * @return  string
     */
    protected static function makeStatement(string $statement)
    {
        $self  = self::getInstance();

        $optionWhere = (!empty($self->optionWhere)) ? " WHERE ". substr(implode("", $self->optionWhere), 4) : null;
        $optionJoin  = implode("", $self->optionJoin);
        $optionOrder = $self->optionOrder;
        $optionLimit = $self->optionLimit;

        $newStatement = $statement.$optionJoin.$optionWhere.$optionOrder.$optionLimit;

        return $newStatement;
    }

    /**
     *
     * Make Insert Parameter
     *
     * @param   array  $data
     * @return  array
     */
    protected static function makeInsertParameter(array $data) 
    {
        foreach($data as $field => $value) {
            $newData[":{$field}"] = $value;
        }

        return $newData;
    }

    /**
     *
     * Make Multiple Insert Parameter
     *
     * @param   string  $table
     * @param   array  $data
     * @return  array
     */
    protected static function makeMultipleInsert(string $table, array $data) 
    {
        $insert_values = array();

        foreach($data as $d) {
            $insert_values = array_merge($insert_values, array_values($d));

            $count = count($d);
            $array = array_fill(0, $count, '?');

            $placeholder[] = '('.implode(',', $array).')';
        }

        $column = implode(',', array_keys($data[0]));
        $values = implode(',', $placeholder);

        $query  = "INSERT INTO {$table} ({$column}) VALUES {$values}";

        return [$query, $insert_values];
    }

    /**
     *
     * Make Update Parameter
     *
     * @param   array  $data
     * @return  array
     */
    protected static function makeUpdateParameter(array $data) 
    {
        
        foreach($data as $field => $value) {
            $newData[] = "{$field}=:{$field}";
        }

        $newData = implode(",", $m); // override new data

        return $newData;
    }

    /**
     *
     * Make Select Query
     *
     * @return  string
     */
    protected static function makeSelect() 
    {
        $self   = self::getInstance();
        $select = (!empty($self->select)) ? $self->select : "*";
        $query  = "SELECT {$select} FROM {$self->table_name} ";

        return $query;
    }

    /**
     *
     * Empty All Option
     *
     * @return  Instance
     */
    protected static function makeEmpty() 
    {
        $self  = self::getInstance();

        $self->$optionWhere     = [];
        $self->$optionWhereData = []; 
        $self->$optionJoin      = [];
        $self->$optionLimit     = null;
        $self->$optionSelect    = null;
        $self->$table_name      = null;

        return $self;
    }

    /**
     *
     * Set Table
     *
     * @param   string   $table
     * @return  Instance
     */
    public static function table(string $table) 
    {
        $self             = self::getInstance();
        $self->table_name = $table;

        return $self;
    }

    /**
     *
     * Insert & Multiple Insert
     *
     * @param   array   $data
     * @return  boolean
     */
    public static function insert(array $data) 
    {
        $self  = self::getInstance();
        $table = $self->table_name;

        if(isset($data[0])) {

            $make      = $self->makeMultipleInsert($table, $data);
            $statement = $make[0];
            $value     = $make[1];

        }else {
            
            $newData    = $self->makeInsertParameter($data);
            $column     = implode(",", array_keys($data));
            $paramValue = implode(",", array_keys($newData));
            $statement  = "INSERT INTO {$table} ({$column}) VALUES({$paramValue});";
            $value      = $newData;
        }

        $execute   = $self->_execute($statement, $value);

        return $execute;
    }
   
    /**
     *
     * Update Record
     *
     * @param   array   $data
     * @return  boolean
     */
    public static function update(array $data) 
    {
        $self      = self::getInstance();

        $table     = $self->table_name;
        $param     = makeUpdateParameter($data);
        $value     = makeInsertParameter($data);

        $statement = "UPDATE {$table} SET {$param} ";
        $execute   = $self->_execute($statement, $value);

        return $execute;
    }

    /**
     *
     * Delete Record
     *
     * @return  boolean
     */
    public static function delete() {
        $self  = self::getInstance();
        $table = $self->table;

        $query = "DELETE FROM {$table} ";

        return $self->_execute($query);
    }

    /**
     *
     * Join Option
     *
     * @param   string   $table
     * @param   string   $foreignKey1
     * @param   string   $foreignKey2
     * @param   string   $relation
     * @return  boolean
     */
    public function join(string $table, string $foreignKey1, string $operator, string $foreignKey2, string $relation = "INNER") 
    {
        $self               = self::getInstance();
        $self->optionJoin[] = " {$relation} JOIN {$table} ON {$foreignKey1}{$operator}{$foreignKey2}";
        return $self;
    }

    public function rightJoin(string $table, string $foreignKey1, string $operator, string $foreignKey2, string $relation = "RIGHT") 
    {
        $self               = self::getInstance();
        $self->optionJoin[] = " {$relation} JOIN {$table} ON {$foreignKey1}{$operator}{$foreignKey2}";
        return $self;
    }

    public function leftJoin(string $table, string $foreignKey1, string $operator, string $foreignKey2, string $relation = "LEFT") 
    {
        $self               = self::getInstance();
        $self->optionJoin[] = " {$relation} JOIN {$table} ON {$foreignKey1}{$operator}{$foreignKey2}";
        return $self;
    }

    /**
     *
     * Where Option
     *
     * @param   string   $column
     * @param   string   $operator
     * @param   string   $value
     * @param   string   $type
     * @return  Instance
     */
    public static function where($column, $operator, $value = null, $type = " AND ") 
    {
        $self      = self::getInstance();

        $param     = str_replace(".", "_", $column); // remove table seperator for parameter
        $where     = (empty($value)) ? "{$column}=:where_{$param}" : "{$param} {$operator} :where_{$param}";
        $whereData = (empty($val)) ? $op : $val;

        array_push($self->optionWhere, $type.$where);
        array_merge($self->optionWhereData, [":where_{$param}" => $whereData]);

        return $self;
    }

    public static function orWhere($column, $operator, $value = null, $type = " OR ") 
    {
        $self      = self::getInstance();

        $param     = str_replace(".", "_", $column); // remove table seperator for parameter
        $where     = (empty($value)) ? "{$column}=:where_{$param}" : "{$param} {$operator} :where_{$param}";
        $whereData = (empty($val)) ? $op : $val;

        array_push($self->optionWhere, $type.$where);
        array_merge($self->optionWhereData, [":where_{$param}" => $whereData]);

        return $self;
    }

    /**
     *
     * Limit Option
     * @param   string || integer   $limit
     * @param   string || integer   $offset
     * @return  Instance
     */
    public function limit($limit, $offset = null) {
        $self              = self::getInstance();
        $offset            = (!empty($offset)) ? 'OFFSET '.$offset : null;
        $self->optionLimit = " LIMIT {$limit} ".$offset;

        return $self;
    }

    /**
     *
     * Order By Column Option
     * @param   string || integer   $column
     * @param   string || integer   $sort
     * @return  Instance
     */
    public function orderBy(string $column, string $sort) {
        $self              = self::getInstance();
        $self->optionOrder = " ORDER BY {$column} {$sort}";

        return $self;
    }

    /**
     *
     * Select Option
     * @return  Instance
     */
    public static function select()
    {
        $self         = self::getInstance();
        $self->optionSelect = implode(",", func_get_args());

        return $self;
    }

    /**
     *
     * Get All Record
     * @return  Array
     */
    public static function get() 
    {
        $self      = self::getInstance();

        $statement = $self->makeSelect();
        $execute   = $self->_execute($statement, []);

        return $execute->fetchAll(\PDO::FETCH_CLASS);
    }

    /**
     *
     * Get First Record
     * @return  Array
     */
    public static function first() 
    {
        $self      = self::getInstance();

        $statement = $self->makeSelect();
        $execute   = $self->_execute($statement, []);

        return $execute->fetchObject();
    }

    /**
     *
     * Count Record
     * @return  Array
     */
    public static function count() 
    {
        $self      = self::getInstance();

        $statement = $self->makeSelect();
        $execute   = $self->_execute($statement, []);

        return $execute->rowCount();
    }

    /**
     *
     * Get Last Insert ID
     * @return  Integer
     */
    public function lastId() {
        return $this->pdo->lastInsertId();
    }

    public function __destruct()
    {
        $this->pdo = null;
    }

    /**
     * @todo Close \PDO Connection.
     */
    public static function close()
    {
        $self   = self::getInstance();
        $self->pdo = null;
    }
}

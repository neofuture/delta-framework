<?php

namespace NeoFuture\Library;

use NeoFuture\Library\Database;
use NeoFuture\Library\Config;
use NeoFuture\Library\Pluraliser;

class Model
{

    public static $instance;

    private $originalData;
    private $originalTable;

    private $limit;
    private $orderBy;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function returnOperator($value)
    {

        $operators = Config::get("database.operators");

        $operator = null;
        if (in_array($value, $operators)) {
            $operator = $value;
        }

        return $operator;
    }

    public static function buildKeyQuery($key, $type, $values)
    {
        $instance = self::getInstance();

        $operator = self::returnOperator($values[0]);

        if ($operator === null) {
            $operator = "=";
            $value = self::cleanValue($values[0]);
        } else {
            if ($operator == "IN") {
                foreach ($values[1] as $itemKey => $item) {
                    $values[1][$itemKey] = self::cleanValue($item);
                }

                $value = "(" . implode(", ", $values[1]) . ")";
            } else {
                if (preg_match("/between/i", strtolower($operator))) {
                    $value = self::cleanValue($values[1][0]) . " AND " . self::cleanValue($values[1][1]);
                } else {
                    $value = self::cleanValue($values[1]);
                }
            }
        }

        $sql = "`" . $key . "` " . $operator . " " . $value . " ";

        if (!isset($instance->query)) {
            $table = self::getTableName();
            $instance->originalTable = $table;

            $instance->query = "SELECT * FROM " . $table . " WHERE " . ($type == "NOT" ? $type : '') . " " . $sql;
        } else {
            $instance->query .= " " . ($type == "NOT" ? "AND NOT" : $type) . " " . $sql;
        }

        return $instance;
    }

    public static function getTableName()
    {
        $class = get_called_class();
        $classObject = new $class;

        if (isset($classObject->table)) {
            $table = $table = $classObject->table;
        } else {
            $reflect = new \ReflectionClass(get_called_class());
            $table = Pluraliser::snakeCase($reflect->getShortName());
        }

        return $table;
    }

    public static function where($key, ...$values)
    {
        return self::buildKeyQuery($key, "AND", $values);
    }

    public static function andWhere($key, ...$values)
    {
        return self::buildKeyQuery($key, "AND", $values);
    }

    public static function orWhere($key, ...$values)
    {
        return self::buildKeyQuery($key, "OR", $values);
    }

    public static function notWhere($key, ...$values)
    {
        return self::buildKeyQuery($key, "NOT", $values);
    }

    public function all(){
        return self::getAll();
    }

    public function getAll()
    {
        $query = $this->query . (isset($this->orderBy) ? $this->orderBy : "") . (isset($this->limit) ? $this->limit : "");

        unset($this->query, $this->orderBy, $this->limit);

        $results = Database::raw($query);

//        if ($results == null) {
//            return false;
//        }


        $instance = new self();
        $instance->originalTable = $this->originalTable;
        $instance->originalData = new \stdClass();

        if($results != null){
            foreach ($results as $key => $obj) {
                $instance->{$key} = $obj;
                $instance->originalData->{$key} = $obj;
            }
        }


        return $instance;
    }

    public function first()
    {
        $this->limit = " LIMIT 1";
        $query = $this->query . (isset($this->orderBy) ? $this->orderBy : "") . (isset($this->limit) ? $this->limit : "");

        unset($this->query, $this->orderBy, $this->limit);

        $result = Database::raw($query);

//        if ($result == null) {
//            return false;
//        }

        $instance = new self();
        $instance->originalTable = $this->originalTable;
        $instance->originalData = new \stdClass();

        if($result != null) {
            foreach ($result as $key => $obj) {
                $instance->{$key} = $obj;
                $instance->originalData->{$key} = $obj;
            }
        }

        return $instance;
    }

    public function orderBy($field, $direction = "ASC")
    {
        $this->orderBy = " ORDER BY " . $field . " " . $direction;
        return $this;
    }


    public static function find($id)
    {
        $table = self::getTableName();

        $keyName = Database::raw("SHOW KEYS FROM " . $table . " WHERE Key_name = 'PRIMARY'");
        $index = $keyName->Column_name;
        $result = Database::raw("SELECT * FROM " . $table . " WHERE " . $index . " = '" . self::cleanValue($id) . "'");

//        if ($result == null) {
//            return false;
//        }

        $instance = new self();
        $instance->originalTable = $table;
        $instance->originalData = new \stdClass();

        if($result != null){
            foreach ($result as $key => $obj) {
                $instance->{$key} = $obj;
                $instance->originalData->{$key} = $obj;
            }
        }


        return $instance;
    }


    public function save()
    {

        if (isset($this->originalData)) {
            $sets = [];
            $wheres = [];
            foreach ($this->originalData as $key => $value) {
                if ($this->{$key} == $value) {
                    $wheres[] = $key . " = " . self::cleanValue($value);
                } else {
                    $sets[] = $key . " = " . self::cleanValue($this->{$key});
                }
            }
            Database::raw("UPDATE " . $this->originalTable . " SET " . join(", ", $sets) . " WHERE " . join(" AND ", $wheres));
            Database::raw("UPDATE IGNORE " . $this->originalTable . " SET last_updated = NOW() WHERE " . join(" AND ", $wheres));

        } else {

            $fields = [];
            $values = [];

            foreach ($this as $key => $value) {
                if (var_export($value, true) != "NULL") {
                    $fields[] = $key;
                    $values[] = $value;
                }
            }

            $table = self::getTableName();
            $id = Database::raw("INSERT INTO " . $table . " (" . join(", ", $fields) . ") VALUES ('" . join("', '", $values) . "')");

            $keyName = Database::raw("SHOW KEYS FROM " . $table . " WHERE Key_name = 'PRIMARY'");
            Database::raw("UPDATE IGNORE " . $table . " SET last_updated = NOW(), created = NOW() WHERE " . $keyName->Column_name . " = " . self::cleanValue($id));

        }

    }

    public function delete()
    {
        $ignoreKeys = ["id", "created", "last_updated"];

        if($this->query){
            return Database::raw(str_replace("SELECT *", "DELETE", $this->query));
        } else {
            if (isset($this->originalData)) {
                $wheres = [];
                foreach ($this->originalData as $key => $value) {
                    if (!in_array($key, $ignoreKeys)) {
                        $wheres[] = $key . " = " . self::cleanValue($value);
                    }

                }

                return Database::raw("DELETE FROM " . $this->originalTable . " WHERE " . join(" AND ", $wheres));

            }
        }
        return false;
    }

    public function destroy($id)
    {
        if (is_array($id)) {
            foreach ($id as $item) {
                $record = self::find($item);
                $record->delete();
            }
        } else {
            $record = self::find($id);
            $record->delete();
        }
    }

    public static function cleanValue($value)
    {
        $quote = '';
        if (is_string($value)) {
            $quote = "'";
        }
        $value = $quote . str_replace("'", "\\'", $value) . $quote;
        return $value;
    }

}
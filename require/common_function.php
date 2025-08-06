<?php

function insertData($table, $mysqli, $data)
{
    $columns = [];
    $values = [];
    foreach ($data as $key => $val) {
        $columns[] = "`" . $key . "`";
        $values[] = "'" . $val . "'";
    }
    $column = implode(', ', $columns);
    $value = implode(', ', $values);
    $sql = "INSERT INTO `$table` 
            ($column)
            VALUES 
            ($value)";
    return $mysqli->query($sql);
}

function selectData($table, $mysqli, $columns = "*", $where = [], $order = "")
{
    $whereClause = "";
    if (!empty($where)) {
        $conditions = [];
        foreach ($where as $key => $val) {
            $conditions[] = "`$key`='" . $mysqli->real_escape_string($val) . "'";
        }
        $whereClause = "WHERE " . implode(' AND ', $conditions);
    }
    $sql = "SELECT $columns FROM `$table` $whereClause $order";
    return $mysqli->query($sql);
}

function deleteData($table, $mysqli, $where)
{
    $whereClause = "";
    if (!empty($where)) {
        $conditions = [];
        foreach ($where as $key => $val) {
            $conditions[] = "`$key`='" . $mysqli->real_escape_string($val) . "'";
        }
        $whereClause = "WHERE " . implode(' AND ', $conditions);
    }
    $sql = "DELETE FROM `$table` $whereClause";
    return $mysqli->query($sql);
}

function updateData($table, $mysqli, $data, $where)
{
    $array = [];
    $array2 = [];
    foreach ($data as $key => $val) {
        $array[] = "`$key`='$val'";
    }
    foreach ($where as $key => $val) {
        $array2[] = "`$key`='$val'";
    }
    $values = implode(', ', $array);
    $condition = implode(' AND ', $array2);
    $sql = "UPDATE `$table` SET $values WHERE $condition";
    return $mysqli->query($sql);
}

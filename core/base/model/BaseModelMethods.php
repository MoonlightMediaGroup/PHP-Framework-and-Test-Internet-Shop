<?php

namespace core\base\model;

abstract class BaseModelMethods
{

    protected $sql_func = [
        'NOW()'
    ];

    protected function createFields($arr, $table = false){

        $arr['fields'] =
            (is_array($arr['fields']) && !empty($arr['fields']))
                ? $arr['fields']
                : ["*"];

        $table = $table ? $table . '.' : '';

        $fields = '';

        foreach ($arr['fields'] as $field) {
            $fields .= $table . $field . ',';
        }

        return $fields;

    }

    protected function createWhere($arr, $table = false, $instruction = 'WHERE') {

        $table = $table ? $table . '.' : '';

        $where = '';

        if(is_array($arr['where']) && !empty($arr['where'])) {

            $arr['operand'] = (is_array($arr['operand']) && !empty($arr['operand']))
                ? $arr['operand']
                : ['='];

            $arr['condition'] = (is_array($arr['condition']) && !empty($arr['condition']))
                ? $arr['condition']
                : ['AND'];

            $where = $instruction;
            $operand_count = 0;
            $condition_count = 0;

            foreach ($arr['where'] as $key => $value) {

                $where .= ' ';

                if($arr['operand'][$operand_count]) {
                    $operand = $arr['operand'][$operand_count];
                    $operand_count++;
                }else{
                    $operand = $arr['operand'][$operand_count - 1];
                }

                if($arr['condition'][$condition_count]) {
                    $condition = $arr['condition'][$condition_count];
                    $condition_count++;
                }else{
                    $condition = $arr['condition'][$condition_count - 1];
                }

                //Supported operands - '= <> IN NOT LIKE (SELECT * FROM table)'
                if($operand === 'IN' || $operand === 'NOT IN'){

                    if(is_string($value) && strpos($value, 'SELECT') === 0) {
                        $in_str = $value;
                    }else{
                        if(is_array($value)) $temp_value = $value;
                        else $temp_value = explode(',', $value);

                        $in_str = '';

                        foreach ($temp_value as $v) {
                            $in_str .= "'" . addslashes(trim($v)) . "',";
                        }
                    }

                    $where .= $table . $key . ' ' . $operand . ' (' . trim($in_str, ',') . ') ' . $condition;

                }elseif (strpos($operand, 'LIKE') !== false){

                    $like_template = explode('%', $operand);

                    foreach ($like_template as $lt_key => $lt) {
                        if(!$lt) {
                            if(!$lt_key) {
                                $value = '%' . $value;
                            }else{
                                $value .=  '%';
                            }
                        }
                    }

                    $where .= $table . $key . ' LIKE ' . "'" . addslashes($value) . "' $condition";

                }else{

                    if(strpos($value, 'SELECT') === 0) {
                        $where .= $table . $key . $operand . '(' . $value . ") $condition";
                    }else{
                        $where .= $table . $key . $operand . "'" . addslashes($value) . "' $condition";
                    }

                }

            }

            $where = substr($where, 0, strrpos($where, $condition));

        }

        return $where;
    }

    protected function createJoin($arr, $table, $new_where = false){

        $fields = '';
        $join = '';
        $where = '';
        $tables = '';

        if($arr['join']){

            $join_table = $table;

            foreach ($arr['join'] as $key => $item) {

                if(is_int($key)){
                    if(!$item['table']) continue;
                    else $key = $item['table'];
                }

                if($join) $join .= ' ';

                if($item['on']){

                    $join_fields = [];

                    switch (2) {
                        //Warning: count(): Parameter must be an array or an object that implements Countable
                        case @count($item['on']['fields']):
                            $join_fields = $item['on']['fields'];
                            break;
                        case @count($item['on']):
                            $join_fields = $item['on'];
                            break;
                        default:
                            continue 2;
                            break;
                    }

                    if(!$item['type']) $join = 'LEFT JOIN ';
                    else $join .= trim(strtoupper($item['type'])) . ' JOIN ';

                    $join .= $key . ' ON ';

                    if($item['on']['table']) $join .= $item['on']['table'];
                    else $join .= $join_table;

                    $join .= '.' . $join_fields[0] . '=' . $key . '.' . $join_fields[1];

                    $join_table = $key;

                    $tables .= ', ' . trim($join_table);

                    if($new_where ) {
                        if($item['where']) $new_where = false;
                        $group_condition = 'WHERE';
                    }else{
                        $group_condition = $item['group_condition']
                            ? strtoupper($item['group_condition'])
                            : 'AND';
                    }

                    $fields .= $this->createFields($item, $key);
                    $where .= $this->createWhere($item, $key, $group_condition);

                }

            }

        }

        return compact('fields', 'join', 'where', 'tables');

    }

    protected function createOrder($arr, $table = false){

        $table = $table ? $table . '.' : '';

        $order_by = '';

        if(is_array($arr['order']) && !empty($arr['order'])) {

            $arr['order_direction'] =
                (is_array($arr['order_direction']) && !empty($arr['order_direction']))
                    ? $arr['order_direction']
                    : ["ASC"];

            $order_by = 'ORDER BY ';
            $direct_count = 0;

            foreach ($arr['order'] as $order) {
                if($arr['order_direction'][$direct_count]){
                    $order_direction = strtoupper($arr['order_direction'][$direct_count]);
                    $direct_count++;
                }else{
                    $order_direction = strtoupper($arr['order_direction'][$direct_count - 1]);
                }

                if(is_int($order)) $order_by .= $order . ' ' . $order_direction . ',';
                else $order_by .= $table . $order . ' ' . $order_direction . ',';
            }

            $order_by = rtrim($order_by, ',');

        }

        return $order_by;
    }

    protected function createInsert($fields, $files, $except) {

        $insert_arr = [];

        if($fields) {

            foreach ($fields as $row => $value) {

                if($except && in_array($row, $except)) continue;

                $insert_arr['fields'] .= $row . ',';

                if(in_array($value, $this->sql_func)) {
                    $insert_arr['values'] .= $value . ',';
                }else{
                    $insert_arr['values'] .= "'" . addslashes($value) . "',";
                }

            }

        }

        if($files) {

            foreach ($files as $row => $file) {

                $insert_arr['fields'] .= $row . ',';

                if(is_array($file)) $insert_arr['values'] .= "'" . json_encode($file) . "',";
                    else $insert_arr['values'] .= "'" . $file . "',";

            }

        }

        foreach ($insert_arr as $key => $arr) $insert_arr[$key] = rtrim($arr, ',');

        return $insert_arr;

    }

    protected function createUpdate($fields, $files, $except) {

        $update = '';

        if($fields) {

            foreach ($fields as $row => $value) {

                if($except && in_array($row, $except)) continue;

                $update .= $row . '=';

                if(in_array($value, $this->sql_func)) {
                    $update .= $value . ',';
                }elseif ($value === NULL) {
                    $update .= "NULL" . ',';
                } else{
                    $update .= "'" . addslashes($value) . "',";
                }
            }

        }

        if($files) {

            foreach ($files as $row => $file) {

                $update .= $row . '=';

                if(is_array($file)) $update .= "'" . json_encode($file) . "',";
                else $update .= "'" . addslashes($file) . "',";

            }

        }

        return rtrim($update, ',');

    }

}
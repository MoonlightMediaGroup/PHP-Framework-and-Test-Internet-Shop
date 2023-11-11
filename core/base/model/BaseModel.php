<?php
//23 video playlist 3:50
namespace core\base\model;

use core\base\controller\Singleton;
use core\base\exceptions\DataBaseExceptions;

class BaseModel extends BaseModelMethods
{

    use Singleton;

    protected $db;

    private function __construct() {
        $this->db = @new \mysqli(HOST, USER, PASS, DB_NAME);

        if($this->db->connect_error) {

            throw new DataBaseExceptions('Data Base not connection: ' . $this->db->connect_errno . ' ' . $this->db->connect_error);

        }

        $this->db->query("SET NAMES UTF8");
    }

    final public function showColumns($table) {

        $query = "SHOW COLUMNS FROM $table";

        $res = $this->query($query);
        $columns = [];

        if($res) {
            foreach ($res as $row ) {
                $columns[$row['Field']] = $row;
                if($row['Key'] === 'PRI') $columns['id_row'] = $row['Field'];
            }
        }

        return $columns;
    }

    /**
     * @param $query
     * @param $method = r - SELECT, c - INSERT, u - UPADATE, d - DELETE
     * @param $return_id
     * @return array|bool
     * @throws DataBaseExceptions
     */
    final public function query($query, $method = 'r', $return_id = false) {

        $result = $this->db->query($query);

        if($this->db->affected_rows === -1) {
            throw new DataBaseExceptions('Error in SQL query: '
                . $query . ' - ' . $this->db->errno . ' ' . $this->db->error
            );
        }

        switch($method) {
            case 'r':
                if ($result->num_rows) {
                    $res = [];
                    for ($i = 0; $i < $result->num_rows; $i++) {
                        $res[] = $result->fetch_assoc();
                    }
                    return $res;
                }
                return false;

                break;
            case 'c':
                if($return_id) return $this->db->insert_id;
                return true;

                break;
            default:
                return true;

                break;
        }

    }

    /**
     * @param string $table
     * @param array $arr
     * 'fields' => ['id', 'name'],
     * 'where' => [
     *      'field_1' => 'value',
     *      'field_2' => 'value',
     *      '...' => 'value'
     *      ],
     * 'operand' => ['=', '<>'],
     * 'condition' => ['AND'],
     * 'order' => ['field_1', 'field_2'],
     * 'order_direction' => ['ASC', 'DESC'],
     * 'limit' => '1'
     * 'join' => [
     *      [
     *          'table' => 'join_table1',
     *          'fields' => ['id as j_id', 'name as j_name'],
     *          'type' => 'left',
     *          'where' => ['name' => 'sasha'],
     *          'operand' => ['='],
     *          'condition' => ['OR'],
     *          'on' => ['id', 'parent_id'],
     *          'group_condition' => 'AND'
     *      ],
     *      'join_table1' => [
     *          'table' => 'join_table2',
     *          'fields' => ['id as j2_id', 'name as j2_name'],
     *          'type' => 'left',
     *          'where' => ['name' => 'sasha'],
     *          'operand' => ['<>'],
     *          'condition' => ['AND'],
     *          'group_condition' => 'AND'
     *          'on' => [
     *              'table' => 'teachers',
     *              'fields' => ['id', 'parent_id']
     *          ]
     *      ]
     * ]
     * @throws DataBaseExceptions
     */
    final public function get($table, $arr = []) {

        $fields = $this->createFields($arr, $table);

        $order = $this->createOrder($arr, $table);

        $where = $this->createWhere($arr, $table);

        if(!$where) $new_where = true;
            else $new_where = false;

        $join_arr = $this->createJoin($arr, $table, $new_where);

        $fields .= $join_arr['fields'];
        $join = $join_arr['join'];
        $where .= $join_arr['where'];

        $fields = rtrim($fields, ',');

        $limit = $arr['limit'] ? 'LIMIT ' . $arr['limit'] : '';

        if($join) $query = "SELECT $fields FROM $table $join $where $order $limit";
        else $query = "SELECT $fields FROM $table $where $order $limit";

        return $this->query($query);

    }

    /**
     * @param $table - table for insert data
     * @param array $arr - array parameters
     * fields => [field => value]; if not, use $_POST[field => value]
     *      it is allowed to pass, example - NOW() as a MySQL function with a regular string
     * files => [field => value]; it is use array type [field => [array value]]
     * except => ['exception 1', 'exception 2'] - Excludes these array elements from being added to the request
     * return_id => true|false - return or no return, id insert article
     * @return mixed
     */
    final public function add($table, $arr = []) {

        $arr['fields'] = (is_array($arr['fields']) && !empty($arr['fields']))
            ? $arr['fields']
            : $_POST;
        $arr['files'] = (is_array($arr['files']) && !empty($arr['files']))
            ? $arr['files']
            : false;

        if(!$arr['fields'] && !$arr['files']) return false;

        $arr['return_id'] = $arr['return_id'] ? true : false;
        $arr['except'] = (is_array($arr['except']) && !empty($arr['except']))
            ? $arr['except']
            : false;

        $insert_arr = $this->createInsert($arr['fields'], $arr['files'], $arr['except']);

        if($insert_arr) {
            $query = "INSERT INTO $table ({$insert_arr['fields']}) VALUES ({$insert_arr['values']})";

            return $this->query($query, 'c', $arr['return_id']);
        }

        return false;
    }

    final public function edit($table, $arr = []) {
        $arr['fields'] = (is_array($arr['fields']) && !empty($arr['fields']))
            ? $arr['fields']
            : $_POST;
        $arr['files'] = (is_array($arr['files']) && !empty($arr['files']))
            ? $arr['files']
            : false;

        if(!$arr['fields'] && !$arr['files']) return false;

        $arr['except'] = (is_array($arr['except']) && !empty($arr['except']))
            ? $arr['except']
            : false;

        if(!$arr['all_rows']) {

            if($arr['where']) {
                $where = $where = $this->createWhere($arr);
            }else{

                $columns = $this->showColumns($table);

                if(!$columns) return false;

                if($columns['id_row'] && $arr['fields'][$columns['id_row']]) {
                    $where = 'WHERE ' . $columns['id_row'] . '=' . $arr['fields'][$columns['id_row']];
                    unset($arr['fields'][$columns['id_row']]);
                }

            }

        }

        $update = $this->createUpdate($arr['fields'], $arr['files'], $arr['except']);

        $query = "UPDATE $table SET $update $where";

        return $this->query($query, 'u');
    }

    /**
     * @param string $table
     * @param array $arr
     * 'fields' => ['id', 'name'],
     * 'where' => [
     *      'field_1' => 'value',
     *      'field_2' => 'value',
     *      '...' => 'value'
     *      ],
     * 'operand' => ['=', '<>'],
     * 'condition' => ['AND'],
     * 'join' => [
     *      [
     *          'table' => 'join_table1',
     *          'fields' => ['id as j_id', 'name as j_name'],
     *          'type' => 'left',
     *          'where' => ['name' => 'sasha'],
     *          'operand' => ['='],
     *          'condition' => ['OR'],
     *          'on' => ['id', 'parent_id'],
     *          'group_condition' => 'AND'
     *      ],
     *      'join_table1' => [
     *          'table' => 'join_table2',
     *          'fields' => ['id as j2_id', 'name as j2_name'],
     *          'type' => 'left',
     *          'where' => ['name' => 'sasha'],
     *          'operand' => ['<>'],
     *          'condition' => ['AND'],
     *          'group_condition' => 'AND'
     *          'on' => [
     *              'table' => 'teachers',
     *              'fields' => ['id', 'parent_id']
     *          ]
     *      ]
     * ]
     */
    final public function delete($table, $arr) {

        $table = trim($table);

        $where = $this->createWhere($arr, $table);

        $columns = $this->showColumns($table);
        if(!$columns) return false;

        if(is_array($arr['fields']) && !empty($arr['fields'])) {

            if($columns['id_row']) {
                $key = array_search($columns['id_row'], $arr['fields']);
                if($key !== false) unset($arr['fields'][$key]);
            }

            $fields = [];

            foreach ($arr['fields'] as $field) {
                $fields[$field] = $columns[$field]['Default'];
            }

            $update = $this->createUpdate($fields, false, false);

            $query = "UPDATE $table SET $update $where";

        }else{

            $join_arr = $this->createJoin($arr, $table);
            $join = $join_arr['join'];
            $join_tables = $join_arr['tables'];

            $query = 'DELETE ' . $table . $join_tables . ' FROM ' . $table . ' ' . $join . ' ' . $where;

        }

        return $this->query($query, 'u');

    }

}

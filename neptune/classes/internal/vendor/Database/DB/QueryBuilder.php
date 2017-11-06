<?php

namespace Database\DB;

class QueryBuilder
{
  protected $allowedActions = [
    'SELECT',
    'INSERT',
    'UPDATE',
    'DELETE',
  ];
  protected $query;

  public function generate(...$data)
  {
    $type = $data[0];

    if ($type === 'SELECT') {
      $columns = $data[1];
      $table = $data[2];

      if (isset($type) && in_array($type, $this->allowedActions)) {
        $this->query = $type;

        if (count($columns)) {
          $x = 1;

          foreach($columns as $column) {
            $this->query .= ' '.$column;

            if ($x < count($columns)) {
              $this->query .= ', ';
            }

            $x++;
          }
        }
      }

      $this->query .= ' FROM '.$table;

      if (isset($data[3])) {
        if ( ! is_array($data[3]) ) {
          die('Third parameters must be array.');
        }

        $adjustments = $data[3];
        $where = $adjustments['WHERE'][0];
        // $join = $adjustments['JOIN'][0];
        
        if ( ! empty($where) ) {
          $this->query .= ' WHERE '.$where;
        }
      }

      return $this->query;
    }
  }
  public function get()
  {

  }
}

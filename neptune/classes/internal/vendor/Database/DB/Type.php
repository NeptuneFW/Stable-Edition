<?php

namespace Database\DB;

class Type
{
  public static function getSQLType($sql)
  {
    $four_operations = preg_match('/(SELECT|INSERT|UPDATE|DELETE)/', $sql, $operations);

    if ($four_operations) {
      return $operations[0];
    }
  }
}

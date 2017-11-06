<?php

namespace Sup;

class Arr
{
	public static function get($array, $key, $default = null)
	{
		if ( ! is_array($array) and ! $array instanceof ArrayAccess ) {
			die('First parameter must be an array or ArrayAccess object.');
		}
		if (is_null($key)) {
			return $array;
		}
		if (is_array($key)) {
			$return = [];

			foreach ($key as $k) {
				$return[$k] = self::get($array, $k, $default);
			}

			return $return;
		}
		if (is_object($key)) {
			$key = (string) $key;
		}
		if (array_key_exists($key, $array)) {
			return $array[$key];
		}
		foreach (explode('.', $key) as $key_part) {
			if (($array instanceof ArrayAccess and isset($array[$key_part])) === false) {
				if ( ! is_array($array) or ! array_key_exists($key_part, $array) ) {
					return result($default);
				}
			}

			$array = $array[$key_part];
		}

		return $array;
	}
	public static function set(&$array, $key, $value = null)
	{
		if (is_null($key)) {
			$array = $value;

			return;
		}
		if (is_array($key)) {
			foreach ($key as $k => $v) {
				self::set($array, $k, $v);
			}
		}else {
			$keys = explode('.', $key);

			while (count($keys) > 1) {
				$key = array_shift($keys);

				if ( ! isset($array[$key]) or ! is_array($array[$key]) ) {
					$array[$key] = [];
				}

				$array =& $array[$key];
			}

			$array[array_shift($keys)] = $value;
		}
	}
	public static function pluck($array, $key, $index = null)
	{
		$return = [];
		$get_deep = strpos($key, '.') !== false;

		if ( ! $index ) {
			foreach ($array as $i => $a) {
				$return[] = (is_object($a) and ! ($a instanceof ArrayAccess)) ? $a->{$key} : ($get_deep ? static::get($a, $key) : $a[$key]);
			}
		}else {
			foreach ($array as $i => $a) {
				$index !== true and $i = (is_object($a) and ! ($a instanceof ArrayAccess)) ? $a->{$index} : $a[$index];
				$return[$i] = (is_object($a) and ! ($a instanceof ArrayAccess)) ? $a->{$key} : ($get_deep ? self::get($a, $key) : $a[$key]);
			}
		}

		return $return;
	}
	public static function has($array, $key)
	{
		foreach (explode('.', $key) as $key_part) {
			if ( ! is_array($array) or ! array_key_exists($key_part, $array) ) {
				return false;
			}

			$array = $array[$key_part];
		}

		return true;
	}
	public static function delete(&$array, $key)
	{
		if (is_null($key)) {
			return false;
		}
		if (is_array($key)) {
			$return = [];

			foreach ($key as $k) {
				$return[$k] = self::delete($array, $k);
			}

      return $return;
		}

    $key_parts = explode('.', $key);

		if ( ! is_array($array) or ! array_key_exists($key_parts[0], $array) )
		{
			return false;
		}

		$this_key = array_shift($key_parts);

		if ( ! empty($key_parts) ) {
			$key = implode('.', $key_parts);

			return self::delete($array[$this_key], $key);
		}else {
			unset($array[$this_key]);
		}

		return true;
	}
	public static function assocToKeyval($assoc, $key_field, $val_field)
	{
		if ( ! is_array($assoc) and ! $assoc instanceof Iterator ){
			die('The first parameter must be an array.');
		}

		$output = [];

    foreach ($assoc as $row) {
			if (isset($row[$key_field]) and isset($row[$val_field])) {
				$output[$row[$key_field]] = $row[$val_field];
			}
		}

		return $output;
	}
	public static function toAssoc($arr)
	{
		if (($count = count($arr)) % 2 > 0) {
			die('Number of values in to_assoc must be even.');
		}

		$keys = $vals = [];

		for ($i = 0; $i < $count - 1; $i += 2) {
			$keys[] = array_shift($arr);
			$vals[] = array_shift($arr);
		}

		return array_combine($keys, $vals);
	}
	public static function isAssoc($arr)
	{
		if ( ! is_array($arr) ) {
			die('The parameter must be an array.');
		}

		$counter = 0;

		foreach ($arr as $key => $unused) {
			if ( ! is_int($key) or $key !== $counter ++ ) {
				return true;
			}
		}

		return false;
	}
	public static function flatten($array, $glue = ':', $reset = true, $indexed = true)
	{
		static $return = [];
		static $curr_key = [];

		if ($reset) {
			$return = [];
			$curr_key = [];
		}
		foreach ($array as $key => $val) {
			$curr_key[] = $key;

			if (is_array($val) and ($indexed or array_values($val) !== $val)) {
				self::flattenAssoc($val, $glue, false);
			}else {
				$return[implode($glue, $curr_key)] = $val;
			}

			array_pop($curr_key);
		}

		return $return;
	}
	public static function flattenAssoc($array, $glue = ':', $reset = true)
	{
		return self::flatten($array, $glue, $reset, false);
	}
	public static function reverseFlatten($array, $glue = ':')
	{
		$return = [];

		foreach ($array as $key => $value) {
			if (stripos($key, $glue) !== false) {
				$keys = explode($glue, $key);
				$temp =& $return;

				while (count($keys) > 1) {
					$key = array_shift($keys);
					$key = is_numeric($key) ? (int) $key : $key;

					if ( ! isset($temp[$key]) or ! is_array($temp[$key]) ) {
						$temp[$key] = [];
					}

					$temp =& $temp[$key];
				}

				$key = array_shift($keys);
				$key = is_numeric($key) ? (int) $key : $key;
        $temp[$key] = $value;
			}else {
				$key = is_numeric($key) ? (int) $key : $key;
				$return[$key] = $value;
			}
		}

		return $return;
	}
	public static function filterPrefixed($array, $prefix, $removePrefix = true)
	{
		$return = [];

		foreach ($array as $key => $val) {
			if (preg_match('/^'.$prefix.'/', $key)) {
				if ($removePrefix === true) {
					$key = preg_replace('/^'.$prefix.'/', '', $key);
				}

				$return[$key] = $val;
			}
		}

		return $return;
	}
	public static function filterRecursive($array, $callback = null)
	{
		foreach ($array as &$value) {
			if (is_array($value)) {
        $filterRecursiveValue = self::filterRecursive($value);
        $filterRecursiveValueAndCallback = self::filterRecursive($value, $callback);

				$value = $callback === null ? $filterRecursiveValue : $filterRecursiveValueAndCallback;
			}
		}

		return $callback === null ? array_filter($array) : array_filter($array, $callback);
	}
	public static function removePrefixed($array, $prefix)
	{
		foreach ($array as $key => $val) {
			if (preg_match('/^'.$prefix.'/', $key)) {
				unset($array[$key]);
			}
		}

		return $array;
	}
	public static function filterSuffixed($array, $suffix, $removeSuffix = true)
	{
		$return = [];

		foreach ($array as $key => $val) {
			if (preg_match('/'.$suffix.'$/', $key)) {
				if ($removeSuffix === true) {
					$key = preg_replace('/'.$suffix.'$/', '', $key);
				}

				$return[$key] = $val;
			}
		}

		return $return;
	}
	public static function removeSuffixed($array, $suffix)
	{
		foreach ($array as $key => $val) {
			if (preg_match('/'.$suffix.'$/', $key)) {
				unset($array[$key]);
			}
		}

		return $array;
	}
	public static function filterKeys($array, $keys, $remove = false)
	{
		$return = [];

		foreach ($keys as $key) {
			if (array_key_exists($key, $array)) {
				$remove or $return[$key] = $array[$key];

				if ($remove) {
					unset($array[$key]);
				}
			}
		}

		return $remove ? $array : $return;
	}
	public static function insert(array &$original, $value, $pos)
	{
		if (count($original) < abs($pos)) {
			die('Position larger than number of elements in array in which to insert.');
		}

		array_splice($original, $pos, 0, $value);

		return true;
	}
	public static function insertAssoc(array &$original, array $values, $pos)
	{
		if (count($original) < abs($pos)) {
			return false;
		}

		$original = array_slice($original, 0, $pos, true) + $values + array_slice($original, $pos, null, true);

    return true;
	}
	public static function insertBeforeKey(array &$original, $value, $key, $isAssoc = false)
	{
		$pos = array_search(
      $key,
      array_keys($original)
    );

		if ($pos === false) {
			die('Key value not found. So the addition isn\'t successful.');
		}

    $insertAssoc = self::insertAssoc($original, $value, $pos);
    $insert = self::insert($original, $value, $pos);

		return $isAssoc ? $insertAssoc : $insert;
	}
	public static function insertAfterKey(array &$original, $value, $key, $isAssoc = false)
	{
		$pos = array_search(
      $key,
      array_keys($original)
    );

		if ($pos === false) {
			die('Key value not found. So the addition isn\'t successful.');
		}

    $insertAssoc = self::insertAssoc($original, $value, $pos + 1);
    $insert = self::insert($original, $value, $pos + 1);

		return $isAssoc ? $insertAssoc : $insert;
	}
	public static function insertAfterValue(array &$original, $value, $search, $isAssoc = false)
	{
		$key = array_search($search, $original);

    if ($key === false) {
			die('Key value not found. So the addition isn\'t successful.');
		}

		return self::insertAfterKey($original, $value, $key, $isAssoc);
	}
	public static function insertBeforeValue(array &$original, $value, $search, $isAssoc = false)
	{
		$key = array_search($search, $original);

		if ($key === false) {
			die('Key value not found. So the addition isn\'t successful.');
		}

		return self::insertBeforeKey($original, $value, $key, $isAssoc);
	}
	public static function sort($array, $key, $order = 'asc', $sortFlags = SORT_REGULAR)
	{
		if ( ! is_array($array) ) {
			die('Arr::sort() - $array must be an array.');
		}
		if (empty($array)) {
			return $array;
		}
		foreach ($array as $k => $v) {
			$b[$k] = static::get($v, $key);
		}
		switch ($order) {
			case 'asc':
				asort($b, $sortFlags);
			break;
			case 'desc':
				arsort($b, $sortFlags);
			break;
			default:
				die('Arr::sort() - $order must be asc or desc.');
			break;
		}

		$c = [];

    foreach ($b as $key => $val) {
			$c[] = $array[$key];
		}
		return $c;
	}
	public static function multisort($array, $conditions, $ignore_case = false)
	{
		$temp = [];
		$keys = array_keys($conditions);

		foreach ($keys as $key) {
			$temp[$key] = static::pluck($array, $key, true);

			is_array($conditions[$key]) or $conditions[$key] = [$conditions[$key]];
		}

		$args = [];

    foreach ($keys as $key) {
			$args[] = $ignore_case ? array_map('strtolower', $temp[$key]) : $temp[$key];

			foreach ($conditions[$key] as $flag) {
				$args[] = $flag;
			}
		}

		$args[] = & $array;

		call_user_func_array('array_multisort', $args);

		return $array;
	}
	public static function average($array)
	{
		if ( ! ($count = count($array)) > 0 ) {
			return 0;
		}

		return (array_sum($array) / $count);
	}
	public static function replaceKey($source, $replace, $new_key = null)
	{
		if (is_string($replace)) {
			$replace = [
        $replace => $new_key,
      ];
		}
		if ( ! is_array($source) or ! is_array($replace) ) {
			die('Arr::replaceKey() - $source must an array. $replace must be an array or string.');
		}

		$result = [];

		foreach ($source as $key => $value) {
			if (array_key_exists($key, $replace)) {
				$result[$replace[$key]] = $value;
			}else {
				$result[$key] = $value;
			}
		}

		return $result;
	}
	public static function merge()
	{
		$array = func_get_arg(0);
		$arrays = array_slice(func_get_args(), 1);

		if ( ! is_array($array) ){
			die('Arr::merge() - all arguments must be arrays.');
		}
		foreach ($arrays as $arr) {
			if ( ! is_array($arr) ) {
				die('Arr::merge() - all arguments must be arrays.');
			}
			foreach ($arr as $k => $v) {
				if (is_int($k)) {
					array_key_exists($k, $array) ? $array[] = $v : $array[$k] = $v;
				}else if (is_array($v) and array_key_exists($k, $array) and is_array($array[$k])) {
					$array[$k] = static::merge($array[$k], $v);
				}else {
					$array[$k] = $v;
				}
			}
		}

		return $array;
	}
	public static function mergeAssoc()
	{
		$array = func_get_arg(0);
		$arrays = array_slice(func_get_args(), 1);

		if ( ! is_array($array) ) {
			die('Arr::mergeAssoc() - all arguments must be arrays.');
		}
		foreach ($arrays as $arr) {
			if ( ! is_array($arr) ) {
				die('Arr::mergeAssoc() - all arguments must be arrays.');
			}
			foreach ($arr as $k => $v) {
				if (is_array($v) and array_key_exists($k, $array) and is_array($array[$k])) {
					$array[$k] = self::mergeAssoc($array[$k], $v);
				}else {
					$array[$k] = $v;
				}
			}
		}

		return $array;
	}
	public static function prepend(&$arr, $key, $value = null)
	{
		$arr = (is_array($key) ? $key : [$key => $value]) + $arr;
	}
	public static function inArrayRecursive($needle, $haystack, $strict = false)
	{
		foreach ($haystack as $value) {
			if ( ! $strict and $needle == $value ) {
				return true;
			}else if ($needle === $value) {
				return true;
			}else if (is_array($value) and self::inArrayRecursive($needle, $value, $strict)) {
				return true;
			}
		}

		return false;
	}
	public static function isMulti($arr, $all_keys = false)
	{
		$values = array_filter($arr, 'is_array');

		return $all_keys ? count($arr) === count($values) : count($values) > 0;
	}
	public static function search($array, $value, $default = null, $recursive = true, $delimiter = '.', $strict = false)
	{
		if ( ! is_array($array) and ! $array instanceof ArrayAccess ) {
			die('First parameter must be an array or ArrayAccess object.');
		}
		if ( ! is_null($default) and ! is_int($default) and ! is_string($default) ) {
			die('Third parameter must be null or integer or string.');
		}
		if ( ! is_string($delimiter) ) {
			die('Fifth parameter should be string.');
		}

		$key = array_search($value, $array, $strict);

		if ($recursive and $key === false) {
			$keys = [];

			foreach ($array as $k => $v) {
				if (is_array($v)) {
					$rk = self::search($v, $value, $default, true, $delimiter, $strict);

					if ($rk !== $default) {
						$keys = [
              $k,
              $rk,
            ];

						break;
					}
				}
			}

			$key = count($keys) ? implode($delimiter, $keys) : false;
		}

		return $key === false ? $default : $key;
	}
	public static function unique($arr)
	{
		return array_filter($arr, function ($item) {
      // TODO bunu self olarak da dene. Eğer ki işe yaramıyorsa tekrar static yap.
			static $vars = [];

			if (in_array($item, $vars, true)) {
				return false;
			}else {
				$vars[] = $item;

				return true;
			}
		});
	}
	public static function sum($array, $key)
	{
		if ( ! is_array($array) and ! $array instanceof ArrayAccess ) {
			die('First parameter must be an array or ArrayAccess object.');
		}

		return array_sum(self::pluck($array, $key));
	}
	public static function reIndex($arr)
	{
		$arr = array_merge($arr);

		foreach ($arr as &$v) {
			is_array($v) and $v = self::reIndex($v);
		}

		return $arr;
	}
	public static function previousByKey($array, $key, $getValue = false, $strict = false)
	{
		if ( ! is_array($array) and ! $array instanceof ArrayAccess ) {
			die('First parameter must be an array or ArrayAccess object.');
		}

		$keys = array_keys($array);

		if (($index = array_search($key, $keys, $strict)) === false) {
			return false;
		}else if ( ! isset($keys[$index - 1]) ) {
			return null;
		}

		return $getValue ? $array[$keys[$index - 1]] : $keys[$index - 1];
	}
	public static function nextByKey($array, $key, $getValue = false, $strict = false)
	{
		if ( ! is_array($array) and ! $array instanceof ArrayAccess ) {
			die('First parameter must be an array or ArrayAccess object.');
		}

		$keys = array_keys($array);

		if (($index = array_search($key, $keys, $strict)) === false) {
			return false;
		}else if ( ! isset($keys[$index + 1]) ) {
			return null;
		}

		return $getValue ? $array[$keys[$index + 1]] : $keys[$index + 1];
	}
	public static function previousByValue($array, $value, $getValue = true, $strict = false)
	{
		if ( ! is_array($array) and ! $array instanceof ArrayAccess ){
			die('First parameter must be an array or ArrayAccess object.');
		}
		if (($key = array_search($value, $array, $strict)) === false) {
			return false;
		}

		$keys = array_keys($array);
		$index = array_search($key, $keys);

		if ( ! isset($keys[$index - 1]) ) {
			return null;
		}

		return $getValue ? $array[$keys[$index - 1]] : $keys[$index - 1];
	}
	public static function nextByValue($array, $value, $getValue = true, $strict = false)
	{
		if ( ! is_array($array) and ! $array instanceof ArrayAccess ){
			die('First parameter must be an array or ArrayAccess object.');
		}
		if (($key = array_search($value, $array, $strict)) === false) {
			return false;
		}

		$keys = array_keys($array);
		$index = array_search($key, $keys);

		if ( ! isset($keys[$index + 1]) ) {
			return null;
		}

		return $getValue ? $array[$keys[$index + 1]] : $keys[$index + 1];
	}
  public static function subset(array $array, array $keys, $default = null)
  {
  	$result = [];

  	foreach ($keys as $key) {
  		self::set($result, $key, self::get($array, $key, $default));
  	}

  	return $result;
  }
}

<?php

namespace Sup;

class Inflector
{
	protected static $uncountable_words = [
		'equipment',
		'information',
		'rice',
		'money',
		'species',
		'series',
		'fish',
		'meta',
	];
	protected static $plural_rules = [
		'/^(ox)$/i' => '\1\2en',
		'/([m|l])ouse$/i' => '\1ice',
		'/(matr|vert|ind)ix|ex$/i' => '\1ices',
		'/(x|ch|ss|sh)$/i' => '\1es',
		'/([^aeiouy]|qu)y$/i' => '\1ies',
		'/(hive)$/i' => '\1s',
		'/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
		'/sis$/i' => 'ses',
		'/([ti])um$/i' => '\1a',
		'/(p)erson$/i' => '\1eople',
		'/(m)an$/i' => '\1en',
		'/(c)hild$/i' => '\1hildren',
		'/(buffal|tomat)o$/i' => '\1\2oes',
		'/(bu|campu)s$/i' => '\1\2ses',
		'/(alias|status|virus)$/i' => '\1es',
		'/(octop)us$/i' => '\1i',
		'/(ax|cris|test)is$/i' => '\1es',
		'/s$/' => 's',
		'/$/' => 's',
	];
	private static $singular_rules = [
		'/(matr)ices$/i' => '\1ix',
		'/(vert|ind)ices$/i' => '\1ex',
		'/^(ox)en/i' => '\1',
		'/(alias)es$/i' => '\1',
		'/([octop|vir])i$/i' => '\1us',
		'/(cris|ax|test)es$/i' => '\1is',
		'/(shoe)s$/i' => '\1',
		'/(o)es$/i' => '\1',
		'/(bus|campus)es$/i' => '\1',
		'/([m|l])ice$/i' => '\1ouse',
		'/(x|ch|ss|sh)es$/i' => '\1',
		'/(m)ovies$/i' => '\1\2ovie',
		'/(s)eries$/i' => '\1\2eries',
		'/([^aeiouy]|qu)ies$/i' => '\1y',
		'/([lr])ves$/i' => '\1f',
		'/(tive)s$/i' => '\1',
		'/(hive)s$/i' => '\1',
		'/([^f])ves$/i' => '\1fe',
		'/(^analy)ses$/i' => '\1sis',
		'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
		'/([ti])a$/i' => '\1um',
		'/(p)eople$/i' => '\1\2erson',
		'/(m)en$/i' => '\1an',
		'/(s)tatuses$/i' => '\1\2tatus',
		'/(c)hildren$/i' => '\1\2hild',
		'/(n)ews$/i' => '\1\2ews',
		'/([^us])s$/i' => '\1',
	];

	public static function ordinalize($number)
	{
		if ( ! is_numeric($number) ) {
			return $number;
		}
		if (in_array(($number % 100), range(11, 13))) {
			return $number.'th';
		}else {
			switch ($number % 10) {
				case 1:
					return $number.'st';
				break;
				case 2:
					return $number.'nd';
				break;
				case 3:
					return $number.'rd';
				break;
				default:
					return $number.'th';
				break;
			}
		}
	}
	public static function pluralize($word, $count = 0)
	{
		$result = strval($word);

		if ($count === 1) {
			return $result;
		}
		if ( ! self::is_countable($result) ) {
			return $result;
		}
		foreach (static::$plural_rules as $rule => $replacement) {
			if (preg_match($rule, $result)) {
				$result = preg_replace($rule, $replacement, $result);

				break;
			}
		}

		return $result;
	}
	public static function singularize($word)
	{
		$result = strval($word);

		if ( ! self::is_countable($result) ) {
			return $result;
		}
		foreach (static::$singular_rules as $rule => $replacement) {
			if (preg_match($rule, $result)) {
				$result = preg_replace($rule, $replacement, $result);

				break;
			}
		}

		return $result;
	}
	public static function camelize($underscored_word)
	{
		return preg_replace_callback('/(^|_)(.)/', function ($parm) {
			return strtoupper($parm[2]);
		}, strval($underscored_word));
	}
	public static function underscore($camel_cased_word)
	{
		return Str::lower(preg_replace('/([A-Z]+)([A-Z])/', '\1_\2', preg_replace('/([a-z\d])([A-Z])/', '\1_\2', strval($camel_cased_word))));
	}
	public static function friendly_title($str, $sep = '-', $lowercase = false, $allow_non_ascii = false)
	{
		$str = strip_tags($str);
		$str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
		$str = preg_replace("#[\’]#", '-', $str);
		$str = preg_replace("#[\"\']#", '', $str);
		$str = self::ascii($str, $allow_non_ascii);

		if ($allow_non_ascii) {
			$str = preg_replace("#[\.;:'\"\]\}\[\{\+\)\(\*&\^\$\#@\!±`%~']#iu", '', $str);
		}else {
			$str = preg_replace('#[^a-z0-9]#i', $sep, $str);
		}

		$str = preg_replace('#[/_|+ -]+#u', $sep, $str);
		$str = trim($str, $sep);

		if ($lowercase === true) {
			$str = Str::lower($str);
		}

		return $str;
	}
	public static function humanize($str, $sep = '_', $lowercase = true)
	{
		$sep = $sep != '-' ? '_' : $sep;

		if ($lowercase === true) {
			$str = Str::ucfirst($str);
		}

		return str_replace($sep, ' ', strval($str));
	}
	public static function demodulize($class_name_in_module)
	{
		return preg_replace('/^.*::/', '', strval($class_name_in_module));
	}
	public static function denamespace($class_name)
	{
		$class_name = trim($class_name, '\\');

		if ($last_separator = strrpos($class_name, '\\')) {
			$class_name = substr($class_name, $last_separator + 1);
		}

		return $class_name;
	}
	public static function get_namespace($class_name)
	{
		$class_name = trim($class_name, '\\');

		if ($last_separator = strrpos($class_name, '\\')) {
			return substr($class_name, 0, $last_separator + 1);
		}

		return '';
	}
	public static function tableize($class_name)
	{
		$class_name = self::denamespace($class_name);

		if (strncasecmp($class_name, 'Model_', 6) === 0) {
			$class_name = substr($class_name, 6);
		}

		return Str::lower(self::pluralize(self::underscore($class_name)));
	}
	public static function words_to_upper($class, $sep = '_')
	{
		return str_replace(' ', $sep, ucwords(str_replace($sep, ' ', $class)));
	}
	public static function classify($name, $force_singular = true)
	{
		$class = ($force_singular) ? self::singularize($name) : $name;

		return self::words_to_upper($class);
	}
	public static function foreign_key($class_name, $use_underscore = true)
	{
		$class_name = self::denamespace(Str::lower($class_name));

		if (strncasecmp($class_name, 'Model_', 6) === 0) {
			$class_name = substr($class_name, 6);
		}

		return self::underscore(self::demodulize($class_name)).($use_underscore ? '_id' : 'id');
	}
	public static function is_countable($word)
	{
		return ! (in_array(Str::lower(strval($word)), self::$uncountable_words));
	}
}

<?php

namespace Sup;

class Str
{
	private static $encoding = 'UTF-8';

	public static function truncate($string, $limit, $continuation = '...', $isHtml = false)
	{
		$offset = 0;
		$tags = [];

		if ($isHtml)
		{
			preg_match_all('/&[a-z]+;/i', strip_tags($string), $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

			if (strlen($string !== mb_strlen($string))) {
				$correction = 0;
				foreach ($matches as $index => $match)
				{
					$matches[$index][0][1] -= $correction;
					$correction += (strlen($match[0][0]) - mb_strlen($match[0][0]));
				}
			}
			foreach ($matches as $match) {
				if ($match[0][1] >= $limit) {
					break;
				}

				$limit += (self::length($match[0][0]) - 1);
			}

			preg_match_all('/<[^>]+>([^<]*)/', $string, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

			if (strlen($string !== mb_strlen($string))) {
				$correction = 0;
				foreach ($matches as $index => $match) {
					$matches[$index][0][1] -= $correction;
					$matches[$index][1][1] -= $correction;
					$correction += (strlen($match[0][0]) - mb_strlen($match[0][0]));
				}
			}
			foreach ($matches as $match) {
				if($match[0][1] - $offset >= $limit) {
					break;
				}

				$tag = self::sub(strtok($match[0][0], " \t\n\r\0\x0B>"), 1);

				if($tag[0] != '/') {
					$tags[] = $tag;
				}else if (end($tags) == self::sub($tag, 1)) {
					array_pop($tags);
				}

				$offset += $match[1][1] - $match[0][1];
			}
		}

		$new_string = static::sub($string, 0, $limit = min(static::length($string),  $limit + $offset));
		$new_string .= (static::length($string) > $limit ? $continuation : '');
		$new_string .= (count($tags = array_reverse($tags)) ? '</'.implode('></', $tags).'>' : '');

		return $new_string;
	}
	public static function increment($str, $first = 1, $separator = '_')
	{
		preg_match('/(.+)'.$separator.'([0-9]+)$/', $str, $match);

		return isset($match[2]) ? $match[1].$separator.($match[2] + 1) : $str.$separator.$first;
	}
	public static function startsWith($str, $start, $ignore_case = false)
	{
		return (bool) preg_match('/^'.preg_quote($start, '/').'/m'.($ignore_case ? 'i' : ''), $str);
	}
	public static function endsWith($str, $end, $ignore_case = false)
	{
		return (bool) preg_match('/'.preg_quote($end, '/').'$/m'.($ignore_case ? 'i' : ''), $str);
	}
	public static function sub($str, $start, $length = null, $encoding = null)
	{
		$encoding or $encoding = self::$encoding;

		$length = is_null($length) ? (function_exists('mb_substr') ? mb_strlen($str, $encoding) : strlen($str)) - $start : $length;

		return function_exists('mb_substr') ? mb_substr($str, $start, $length, $encoding) : substr($str, $start, $length);
	}
	public static function length($str, $encoding = null)
	{
		$encoding or $encoding = self::$encoding;

		return function_exists('mb_strlen') ? mb_strlen($str, $encoding) : strlen($str);
	}
	public static function lower($str, $encoding = null)
	{
		$encoding or $encoding = self::$encoding;

		return function_exists('mb_strtolower') ? mb_strtolower($str, $encoding) : strtolower($str);
	}
	public static function upper($str, $encoding = null)
	{
		$encoding or $encoding = self::$encoding;

		return function_exists('mb_strtoupper') ? mb_strtoupper($str, $encoding) : strtoupper($str);
	}
	public static function lcfirst($str, $encoding = null)
	{
		$encoding or $encoding = self::$encoding;

		return function_exists('mb_strtolower') ? mb_strtolower(mb_substr($str, 0, 1, $encoding), $encoding).mb_substr($str, 1, mb_strlen($str, $encoding), $encoding) : lcfirst($str);
	}
	public static function ucfirst($str, $encoding = null)
	{
		$encoding or $encoding = self::$encoding;

		return function_exists('mb_strtoupper') ? mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding).mb_substr($str, 1, mb_strlen($str, $encoding), $encoding) : ucfirst($str);
	}
	public static function ucwords($str, $encoding = null)
	{
		$encoding or $encoding = self::$encoding;

		return function_exists('mb_convert_case') ? mb_convert_case($str, MB_CASE_TITLE, $encoding) : ucwords(strtolower($str));
	}
	public static function random($type = 'alnum', $length = 16)
	{
		switch($type)
		{
			case 'basic':
				return mt_rand();
				break;
			default:
			case 'alnum':
			case 'numeric':
			case 'nozero':
			case 'alpha':
			case 'distinct':
			case 'hexdec':
				switch ($type)
				{
					case 'alpha':
						$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
					break;
					default:
					case 'alnum':
						$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
					break;
					case 'numeric':
						$pool = '0123456789';
					break;
					case 'nozero':
						$pool = '123456789';
					break;
					case 'distinct':
						$pool = '2345679ACDEFHJKLMNPRSTUVWXYZ';
					break;
					case 'hexdec':
						$pool = '0123456789abcdef';
					break;
				}

				$str = '';

				for ($i = 0; $i < $length; $i++) {
					$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
				}

				return $str;
			break;
			case 'unique':
				return md5(uniqid(mt_rand()));
			break;
			case 'sha1' :
				return sha1(uniqid(mt_rand(), true));
			break;
			case 'uuid':
		    $pool = ['8', '9', 'a', 'b'];
				return sprintf('%s-%s-4%s-%s%s-%s',
				self::random('hexdec', 8),
				self::random('hexdec', 4),
				self::random('hexdec', 3),
				$pool[array_rand($pool)],
				self::random('hexdec', 3),
				self::random('hexdec', 12));
			break;
		}
	}
	public static function alternator()
	{
		$args = func_get_args();

		return function ($next = true) use ($args) {
			static $i = 0;

			return $args[($next ? $i++ : $i) % count($args)];
		};
	}
	public static function tr($string, $array = [])
	{
		if (is_string($string)) {
			$tr_arr = [];

			foreach ($array as $from => $to) {
				substr($from, 0, 1) !== ':' and $from = ':'.$from;
				$tr_arr[$from] = $to;
			}

			unset($array);

			return strtr($string, $tr_arr);
		}
		else
		{
			return $string;
		}
	}
	public static function isJson($string)
	{
		json_decode($string);

		return json_last_error() === JSON_ERROR_NONE;
	}
	public static function isXml($string)
	{
		if ( ! defined('LIBXML_COMPACT')) {
			die('To use Str::isXml () you need to have LIBXML_COMPACT.');
		}

		$internal_errors = libxml_use_internal_errors();
		libxml_use_internal_errors(true);
		$result = simplexml_load_string($string) !== false;
		libxml_use_internal_errors($internal_errors);

		return $result;
	}
	public static function isSerialized($string)
	{
		$array = @unserialize($string);

		return ! ($array === false and $string !== 'b:0;');
	}
	public static function isHtml($string)
	{
		return strlen(strip_tags($string)) < strlen($string);
	}
}

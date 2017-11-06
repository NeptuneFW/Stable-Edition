<?php

namespace Sup;

class Converter
{
  public $accentChars = [
    'ä|æ|ǽ' => 'ae',
    'œ' => 'oe',
    'Ä' => 'Ae',
    'À|Á|Â|Ã|Ä|Å|Ǻ|Ā|Ă|Ą|Ǎ|Α|Ά|Ả|Ạ|Ầ|Ẫ|Ẩ|Ậ|Ằ|Ắ|Ẵ|Ẳ|Ặ|А' => 'A',
    'à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª|α|ά|ả|ạ|ầ|ấ|ẫ|ẩ|ậ|ằ|ắ|ẵ|ẳ|ặ|а' => 'a',
    'Б' => 'B',
    'б' => 'b',
    'Ç|Ć|Ĉ|Ċ|Č' => 'C',
    'ç|ć|ĉ|ċ|č' => 'c',
    'Д' => 'D',
    'д' => 'd',
    'Ð|Ď|Đ|Δ' => 'Dj',
    'ð|ď|đ|δ' => 'dj',
    'È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě|Ε|Έ|Ẽ|Ẻ|Ẹ|Ề|Ế|Ễ|Ể|Ệ|Е|Э' => 'E',
    'è|é|ê|ë|ē|ĕ|ė|ę|ě|έ|ε|ẽ|ẻ|ẹ|ề|ế|ễ|ể|ệ|е|э'  => 'e',
    'Ф' => 'F',
    'ф' => 'f',
    'Ĝ|Ğ|Ġ|Ģ|Γ|Г|Ґ' => 'G',
    'ĝ|ğ|ġ|ģ|γ|г|ґ' => 'g',
    'Ĥ|Ħ' => 'H',
    'ĥ|ħ' => 'h',
    'Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ|Η|Ή|Ί|Ι|Ϊ|Ỉ|Ị|И|Ы' => 'I',
    'ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı|η|ή|ί|ι|ϊ|ỉ|ị|и|ы|ї' => 'i',
    'Ĵ' => 'J',
    'ĵ' => 'j',
    'Ķ|Κ|К' => 'K',
    'ķ|κ|к' => 'k',
    'Ĺ|Ļ|Ľ|Ŀ|Ł|Λ|Л' => 'L',
    'ĺ|ļ|ľ|ŀ|ł|λ|л' => 'l',
    'М' => 'M',
    'м' => 'm',
    'Ñ|Ń|Ņ|Ň|Ν|Н' => 'N',
    'ñ|ń|ņ|ň|ŉ|ν|н' => 'n',
    'Ö|Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ|Ο|Ό|Ω|Ώ|Ỏ|Ọ|Ồ|Ố|Ỗ|Ổ|Ộ|Ờ|Ớ|Ỡ|Ở|Ợ|О' => 'O',
    'ö|ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º|ο|ό|ω|ώ|ỏ|ọ|ồ|ố|ỗ|ổ|ộ|ờ|ớ|ỡ|ở|ợ|о' => 'o',
    'П' => 'P',
    'п' => 'p',
    'Ŕ|Ŗ|Ř|Ρ|Р' => 'R',
    'ŕ|ŗ|ř|ρ|р' => 'r',
    'Ś|Ŝ|Ş|Ș|Š|Σ|С' => 'S',
    'ś|ŝ|ş|ș|š|ſ|σ|ς|с' => 's',
    'Ț|Ţ|Ť|Ŧ|τ|Т' => 'T',
    'ț|ţ|ť|ŧ|т' => 't',
    'Ü|Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ|Ũ|Ủ|Ụ|Ừ|Ứ|Ữ|Ử|Ự|У' => 'U',
    'ü|ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ|υ|ύ|ϋ|ủ|ụ|ừ|ứ|ữ|ử|ự|у' => 'u',
    'Ý|Ÿ|Ŷ|Υ|Ύ|Ϋ|Ỳ|Ỹ|Ỷ|Ỵ|Й' => 'Y',
    'ý|ÿ|ŷ|ỳ|ỹ|ỷ|ỵ|й' => 'y',
    'В' => 'V',
    'в' => 'v',
    'Ŵ' => 'W',
    'ŵ' => 'w',
    'Ź|Ż|Ž|Ζ|З' => 'Z',
    'ź|ż|ž|ζ|з' => 'z',
    'Æ|Ǽ' => 'AE',
    'ß' => 'ss',
    'Ĳ' => 'IJ',
    'ĳ' => 'ij',
    'Œ' => 'OE',
    'ƒ' => 'f',
    'ξ' => 'ks',
    'π' => 'p',
    'β' => 'v',
    'μ' => 'm',
    'ψ' => 'ps',
    'Ё' => 'Yo',
    'ё' => 'yo',
    'Є' => 'Ye',
    'є' => 'ye',
    'Ї' => 'Yi',
    'Ж' => 'Zh',
    'ж' => 'zh',
    'Х' => 'Kh',
    'х' => 'kh',
    'Ц' => 'Ts',
    'ц' => 'ts',
    'Ч' => 'Ch',
    'ч' => 'ch',
    'Ш' => 'Sh',
    'ш' => 'sh',
    'Щ' => 'Shch',
    'щ' => 'shch',
    'Ъ|ъ|Ь|ь' => '',
    'Ю' => 'Yu',
    'ю' => 'yu',
    'Я' => 'Ya',
    'я' => 'ya'
  ];

  public static function byte(float $bytes, int $precision = 1, bool $unit = true)
  {
    $byte = 1024;
    $kb = 1024 * $byte;
    $mb = 1024 * $kb;
    $gb = 1024 * $mb;
    $tb = 1024 * $gb;
    $pb = 1024 * $tb;
    $eb = 1024 * $pb;

    if($bytes <= $byte && $bytes > -1) {
      $un = ( ! empty($unit) ) ? ' Bytes' : '';
      $return = $bytes.$un;
    }else if ($bytes <= $kb && $bytes > $byte) {
      $un = ( ! empty($unit) ) ? ' KB' : '';
      $return = round(($bytes / $byte), $precision).$un;
    }else if ($bytes <= $mb && $bytes > $kb) {
      $un = ( ! empty($unit) ) ? ' MB' : '';
      $return = round(($bytes / $kb),$precision).$un;
    }else if ($bytes <= $gb && $bytes > $mb) {
      $un = ( ! empty($unit) ) ? ' GB' : '';
      $return = round(($bytes / $mb),$precision).$un;
    }else if ($bytes <= $tb && $bytes > $gb) {
      $un = ( ! empty($unit) ) ? ' TB' : '';
      $return = round(($bytes / $gb),$precision).$un;
    }else if ($bytes <= $pb && $bytes > $tb) {
      $un = ( ! empty($unit) ) ? ' PB' : '';
      $return = round(($bytes / $tb),$precision).$un;
    }else if ($bytes <= $eb && $bytes > $pb) {
      $un = ( ! empty($unit) ) ? ' EB' : '';
      $return = round(($bytes / $pb),$precision).$un;
    }else {
      $un = ( ! empty($unit) ) ? ' Bytes' : '';
      $return = str_replace(",", ".", number_format($bytes)).$un;
    }

    return $return;
  }
  public static function time(int $count, string $type = 'second', string $output = 'day')
  {
    if($output === 'second') {
      $out = 1;
    }
    if($output === 'minute') {
      $out = 60;
    }
    if($output === 'hour') {
      $out = 60 * 60;
    }
    if($output === 'day') {
      $out = 60 * 60 * 24;
    }
    if($output === 'month') {
      $out = 60 * 60 * 24 * 30;
    }
    if($output === 'year') {
      $out = 60 * 60 * 24 * 30 * 12;
    }
    if($type === 'second') {
      $time = $count;
    }
    if($type === 'minute') {
      $time = 60 * $count;
    }
    if($type === 'hour') {
      $time = 60 * 60 * $count;
    }
    if($type === 'day') {
      $time = 60 * 60 * 24 * $count;
    }
    if($type === 'month') {
      $time = 60 * 60 * 24 * 30 * $count;
    }
    if($type === 'year') {
      $time = 60 * 60 * 24 * 30 * 12 * $count;
    }

    return $time / $out;
  }
  public static function word(string $string, $badWords = null, $changeChar = '[badwords]')
  {
    return str_ireplace($badWords, $changeChar, $string);
  }
  public static function char(string $string, string $type = 'char', string $changeType = 'html')
  {
    $options = ['char', 'html', 'hex', 'dec'];

    if( ! in_array($type, $options) || ! in_array($changeType, $options) ) {
      die ('The "type" or "changeType" entered doesn\'t match any option.');
    }
    if($type === $changeType) {
      die('The "type" value doesn\'t match "changeType".');
    }

    $string = $this->accent($string);

    if( ! is_string($type) ) {
      $type = 'char';
    }
    if( ! is_string($changeType) ) {
      $changeType = 'html';
    }
    for($i = 32; $i <= 255; $i++) {
      $hexRemaining = ($i % 16);
      $hexRemaining = str_replace([10, 11, 12, 13, 14, 15], ['A', 'B', 'C', 'D', 'E', 'F'], $hexRemaining);
      $hex = (floor( $i / 16)).$hexRemaining;

      if($hex[0] == '0') {
        $hex = $hex[1];
      }
      if(chr($i) !== ' ') {
        $chars['char'][] = chr($i);
        $chars['dec'][] = $i.' ';
        $chars['hex'][] = $hex.' ';
        $chars['html'][] = "&#{$i};";
      }
    }

    return str_replace($chars[strtolower($type)], $chars[strtolower($changeType)], $string);
  }
  public static function charset(string $str, string $fromCharset, string $toCharset = 'utf-8')
  {
    return mb_convert_encoding($str, $fromCharset, $toCharset);
  }
  public static function toInt($var)
  {
    return (int) $var;
  }
  public static function toInteger($var)
  {
    return (int) $var;
  }
  public static function toBool($var)
  {
    return (bool) $var;
  }
  public static function toBoolean($var)
  {
    return (bool) $var;
  }
  public static function toString($var)
  {
    if(is_array($var) || is_object($var)) {
      return implode(' ', (array) $var);
    }

    return (string) $var;
  }
  public static function toFloat($var)
  {
    return (float) $var;
  }
  public static function toReal($var)
  {
    return (real) $var;
  }
  public static function toDouble($var)
  {
    return (real) $var;
  }
  public static function toObject($var)
  {
    return (object) $var;
  }
  public static function toObjectRecursive($var)
  {
    $object = new stdClass;

    return self::$objectRecursive((array) $var, $object);
  }
  private static function objectRecursive(array $array, stdClass &$std)
  {
    foreach($array as $key => $value) {
      if( is_array($value) ) {
        $std->$key = new stdClass;
        $this->objectRecursive($value, $std->$key);
      }else {
        $std->$key = $value;
      }
    }

    return $std;
  }
  public static function toArray($var)
  {
    return (array) $var;
  }
  public static function toConstant(string $var, string $prefix = null, string $suffix = null)
  {
    $variable = str_replace('i', 'I', strtoupper($prefix.$var.$suffix));

    if(defined($variable)) {
      return constant($variable);
    }else if(defined($var)) {
      return constant($var);
    }else {
      if(is_numeric($var)) {
        return (int) $var;
      }

      return $var;
    }
  }
}

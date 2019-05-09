<?php

use EasyCSV\Reader;
use Doctrine\ORM\EntityManager;
use Fa\Bundle\AdBundle\Solr\AdSolrIndex;
use Symfony\Component\VarDumper\VarDumper;
use Doctrine\Common\Collections\Collection;
use Fa\Bundle\CoreBundle\Solr\BootStrapSolr;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\lib\String\Pluralizer;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

if(!function_exists('dd')) {
    /**
     * Var dump the values with dumper and Die.
     *
     * @param array ...$args
     */
    function dd (...$args) {
        foreach ($args as $var) {
            VarDumper::dump($var);
        }
        die();
    }
}

if(!function_exists('ddp')) {
    /**
     * Var dump with print_r and Die.
     *
     * @param array ...$args
     */
    function ddp (...$args) {
        echo "<pre>";
        print_r($args);
        echo "</pre>";
        die();
    }
}

if(!function_exists('p')) {
    /**
     * Var dump with print_r and Die.
     *
     * @param array ...$args
     */
    function p (...$args) {
        foreach ($args as $var) {
            VarDumper::dump($var);
        }
    }
}

if (! function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (! function_exists('explode_pluck_parameters')) {
    /**
     * @param $value
     * @param $key
     * @return array
     */
    function explode_pluck_parameters($value, $key)
    {
        $value = is_string($value) ? explode('.', $value) : $value;
        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);
        return [$value, $key];
    }
}

if (! function_exists('array_pluck')) {
    /**
     * Pluck an array of values from an array.
     *
     * @param  array  $array
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    function array_pluck($array, $value, $key = null)
    {
        $results = [];
        list($value, $key) = explode_pluck_parameters($value, $key);
        foreach ($array as $item) {
            $itemValue = data_get($item, $value);
            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = data_get($item, $key);
                if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                    $itemKey = (string) $itemKey;
                }
                $results[$itemKey] = $itemValue;
            }
        }
        return $results;
    }
}

if (! function_exists('array_pluck_with_index')) {
    /**
     * Pluck an array of values from an array.
     *
     * @param  array  $array
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    function array_pluck_with_index($array, $value, $key = null)
    {
        $results = [];
        list($value, $key) = explode_pluck_parameters($value, $key);
        foreach ($array as $index => $item) {
            $itemValue = data_get($item, $value);
            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[$index] = $itemValue;
            } else {
                $itemKey = data_get($item, $key);
                if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                    $itemKey = (string) $itemKey;
                }
                $results[$itemKey] = $itemValue;
            }
        }
        return $results;
    }
}

if (! function_exists('array_pluck_flat')) {
    /**
     * Pluck an array of values from an array and flatten it.
     *
     * @param  array  $array
     * @param  string|array  $value
     * @return array
     */
    function array_pluck_flat($array, $value)
    {
        $results = [];
        list($value, $key) = explode_pluck_parameters($value, null);
        foreach ($array as $item) {
            if (empty($itemValue = data_get($item, $value))) {
                continue;
            }

            if (is_array($itemValue)) {
                $results = array_merge($results, $itemValue);
            } else {
                $results[] = $itemValue;
            }
        }
        return $results;
    }
}

if (! function_exists('array_collapse')) {
    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  array  $array
     * @return array
     */
    function array_collapse($array)
    {
        $results = [];
        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            } elseif (! is_array($values)) {
                continue;
            }
            $results = array_merge($results, $values);
        }
        return $results;
    }
}

if (! function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed   $target
     * @param  string|array  $key
     * @param  mixed   $default
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }
        $key = is_array($key) ? $key : explode('.', $key);
        while (! is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->getValues();
                } elseif (! is_array($target)) {
                    return value($default);
                }
                $result = array_pluck($target, $key);
                return in_array('*', $key) ? array_collapse($result) : $result;
            }
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }
        return $target;
    }
}

if (! function_exists('array_wrap')) {

    /**
     * Wraps the given value in array, if not already an array.
     *
     * @param $value
     * @return array
     */
    function array_wrap($value)
    {
        return is_array($value) ? $value : [$value];
    }
}

if (! function_exists('blank')) {
    /**
     * Determine if the given value is "blank".
     *
     * @param  mixed  $value
     * @return bool
     */
    function blank($value)
    {
        if (is_null($value)) {
            return true;
        }
        if (is_string($value)) {
            return trim($value) === '';
        }
        if (is_numeric($value) || is_bool($value)) {
            return false;
        }
        if ($value instanceof Countable) {
            return count($value) === 0;
        }
        return empty($value);
    }
}

if (! function_exists('filled')) {
    /**
     * Determine if a value is "filled".
     *
     * @param  mixed  $value
     * @return bool
     */
    function filled($value)
    {
        return ! blank($value);
    }
}

if (! function_exists('transform')) {
    /**
     * Transform the given value if it is present.
     *
     * @param  mixed  $value
     * @param  callable  $callback
     * @param  mixed  $default
     * @return mixed|null
     */
    function transform($value, callable $callback, $default = null)
    {
        if (filled($value)) {
            return $callback($value);
        }
        if (is_callable($default)) {
            return $default($value);
        }
        return $default;
    }
}

if (! function_exists('slug')) {
    /**
     * Generate a URL friendly "slug" from a given string.
     *
     * @param  string  $title
     * @param  string  $separator
     * @param  string  $language
     * @return string
     */
    function slug($title, $separator = '-', $language = 'en')
    {
        $title = ascii($title, $language);
        // Convert all dashes/underscores into separator
        $flip = $separator == '-' ? '_' : '-';
        $title = preg_replace('!['.preg_quote($flip).']+!u', $separator, $title);
        // Replace @ with the word 'at'
        $title = str_replace('@', $separator.'at'.$separator, $title);
        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($title));
        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);
        return trim($title, $separator);
    }
}

if (! function_exists('ascii')) {
    /**
     * Transliterate a UTF-8 value to ASCII.
     *
     * @param  string  $value
     * @param  string  $language
     * @return string
     */
    function ascii($value, $language = 'en')
    {
        $languageSpecific = languageSpecificCharsArray($language);
        if (! is_null($languageSpecific)) {
            $value = str_replace($languageSpecific[0], $languageSpecific[1], $value);
        }
        foreach (charsArray() as $key => $val) {
            $value = str_replace($val, $key, $value);
        }
        return preg_replace('/[^\x20-\x7E]/u', '', $value);
    }
}

if (! function_exists('charsArray')) {
    /**
     * Returns the replacements for the ascii method.
     *
     * Note: Adapted from Stringy\Stringy.
     *
     * @see https://github.com/danielstjules/Stringy/blob/3.1.0/LICENSE.txt
     *
     * @return array
     */
    function charsArray()
    {
        static $charsArray;
        if (isset($charsArray)) {
            return $charsArray;
        }
        return $charsArray = [
            '0'    => ['°', '₀', '۰', '０'],
            '1'    => ['¹', '₁', '۱', '１'],
            '2'    => ['²', '₂', '۲', '２'],
            '3'    => ['³', '₃', '۳', '３'],
            '4'    => ['⁴', '₄', '۴', '٤', '４'],
            '5'    => ['⁵', '₅', '۵', '٥', '５'],
            '6'    => ['⁶', '₆', '۶', '٦', '６'],
            '7'    => ['⁷', '₇', '۷', '７'],
            '8'    => ['⁸', '₈', '۸', '８'],
            '9'    => ['⁹', '₉', '۹', '９'],
            'a'    => ['à', 'á', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'ā', 'ą', 'å', 'α', 'ά', 'ἀ', 'ἁ', 'ἂ', 'ἃ', 'ἄ', 'ἅ', 'ἆ', 'ἇ', 'ᾀ', 'ᾁ', 'ᾂ', 'ᾃ', 'ᾄ', 'ᾅ', 'ᾆ', 'ᾇ', 'ὰ', 'ά', 'ᾰ', 'ᾱ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'а', 'أ', 'အ', 'ာ', 'ါ', 'ǻ', 'ǎ', 'ª', 'ა', 'अ', 'ا', 'ａ', 'ä'],
            'b'    => ['б', 'β', 'ب', 'ဗ', 'ბ', 'ｂ'],
            'c'    => ['ç', 'ć', 'č', 'ĉ', 'ċ', 'ｃ'],
            'd'    => ['ď', 'ð', 'đ', 'ƌ', 'ȡ', 'ɖ', 'ɗ', 'ᵭ', 'ᶁ', 'ᶑ', 'д', 'δ', 'د', 'ض', 'ဍ', 'ဒ', 'დ', 'ｄ'],
            'e'    => ['é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'ë', 'ē', 'ę', 'ě', 'ĕ', 'ė', 'ε', 'έ', 'ἐ', 'ἑ', 'ἒ', 'ἓ', 'ἔ', 'ἕ', 'ὲ', 'έ', 'е', 'ё', 'э', 'є', 'ə', 'ဧ', 'ေ', 'ဲ', 'ე', 'ए', 'إ', 'ئ', 'ｅ'],
            'f'    => ['ф', 'φ', 'ف', 'ƒ', 'ფ', 'ｆ'],
            'g'    => ['ĝ', 'ğ', 'ġ', 'ģ', 'г', 'ґ', 'γ', 'ဂ', 'გ', 'گ', 'ｇ'],
            'h'    => ['ĥ', 'ħ', 'η', 'ή', 'ح', 'ه', 'ဟ', 'ှ', 'ჰ', 'ｈ'],
            'i'    => ['í', 'ì', 'ỉ', 'ĩ', 'ị', 'î', 'ï', 'ī', 'ĭ', 'į', 'ı', 'ι', 'ί', 'ϊ', 'ΐ', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ', 'ἵ', 'ἶ', 'ἷ', 'ὶ', 'ί', 'ῐ', 'ῑ', 'ῒ', 'ΐ', 'ῖ', 'ῗ', 'і', 'ї', 'и', 'ဣ', 'ိ', 'ီ', 'ည်', 'ǐ', 'ი', 'इ', 'ی', 'ｉ'],
            'j'    => ['ĵ', 'ј', 'Ј', 'ჯ', 'ج', 'ｊ'],
            'k'    => ['ķ', 'ĸ', 'к', 'κ', 'Ķ', 'ق', 'ك', 'က', 'კ', 'ქ', 'ک', 'ｋ'],
            'l'    => ['ł', 'ľ', 'ĺ', 'ļ', 'ŀ', 'л', 'λ', 'ل', 'လ', 'ლ', 'ｌ'],
            'm'    => ['м', 'μ', 'م', 'မ', 'მ', 'ｍ'],
            'n'    => ['ñ', 'ń', 'ň', 'ņ', 'ŉ', 'ŋ', 'ν', 'н', 'ن', 'န', 'ნ', 'ｎ'],
            'o'    => ['ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ø', 'ō', 'ő', 'ŏ', 'ο', 'ὀ', 'ὁ', 'ὂ', 'ὃ', 'ὄ', 'ὅ', 'ὸ', 'ό', 'о', 'و', 'θ', 'ို', 'ǒ', 'ǿ', 'º', 'ო', 'ओ', 'ｏ', 'ö'],
            'p'    => ['п', 'π', 'ပ', 'პ', 'پ', 'ｐ'],
            'q'    => ['ყ', 'ｑ'],
            'r'    => ['ŕ', 'ř', 'ŗ', 'р', 'ρ', 'ر', 'რ', 'ｒ'],
            's'    => ['ś', 'š', 'ş', 'с', 'σ', 'ș', 'ς', 'س', 'ص', 'စ', 'ſ', 'ს', 'ｓ'],
            't'    => ['ť', 'ţ', 'т', 'τ', 'ț', 'ت', 'ط', 'ဋ', 'တ', 'ŧ', 'თ', 'ტ', 'ｔ'],
            'u'    => ['ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'û', 'ū', 'ů', 'ű', 'ŭ', 'ų', 'µ', 'у', 'ဉ', 'ု', 'ူ', 'ǔ', 'ǖ', 'ǘ', 'ǚ', 'ǜ', 'უ', 'उ', 'ｕ', 'ў', 'ü'],
            'v'    => ['в', 'ვ', 'ϐ', 'ｖ'],
            'w'    => ['ŵ', 'ω', 'ώ', 'ဝ', 'ွ', 'ｗ'],
            'x'    => ['χ', 'ξ', 'ｘ'],
            'y'    => ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'ÿ', 'ŷ', 'й', 'ы', 'υ', 'ϋ', 'ύ', 'ΰ', 'ي', 'ယ', 'ｙ'],
            'z'    => ['ź', 'ž', 'ż', 'з', 'ζ', 'ز', 'ဇ', 'ზ', 'ｚ'],
            'aa'   => ['ع', 'आ', 'آ'],
            'ae'   => ['æ', 'ǽ'],
            'ai'   => ['ऐ'],
            'ch'   => ['ч', 'ჩ', 'ჭ', 'چ'],
            'dj'   => ['ђ', 'đ'],
            'dz'   => ['џ', 'ძ'],
            'ei'   => ['ऍ'],
            'gh'   => ['غ', 'ღ'],
            'ii'   => ['ई'],
            'ij'   => ['ĳ'],
            'kh'   => ['х', 'خ', 'ხ'],
            'lj'   => ['љ'],
            'nj'   => ['њ'],
            'oe'   => ['ö', 'œ', 'ؤ'],
            'oi'   => ['ऑ'],
            'oii'  => ['ऒ'],
            'ps'   => ['ψ'],
            'sh'   => ['ш', 'შ', 'ش'],
            'shch' => ['щ'],
            'ss'   => ['ß'],
            'sx'   => ['ŝ'],
            'th'   => ['þ', 'ϑ', 'ث', 'ذ', 'ظ'],
            'ts'   => ['ц', 'ც', 'წ'],
            'ue'   => ['ü'],
            'uu'   => ['ऊ'],
            'ya'   => ['я'],
            'yu'   => ['ю'],
            'zh'   => ['ж', 'ჟ', 'ژ'],
            '(c)'  => ['©'],
            'A'    => ['Á', 'À', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Å', 'Ā', 'Ą', 'Α', 'Ά', 'Ἀ', 'Ἁ', 'Ἂ', 'Ἃ', 'Ἄ', 'Ἅ', 'Ἆ', 'Ἇ', 'ᾈ', 'ᾉ', 'ᾊ', 'ᾋ', 'ᾌ', 'ᾍ', 'ᾎ', 'ᾏ', 'Ᾰ', 'Ᾱ', 'Ὰ', 'Ά', 'ᾼ', 'А', 'Ǻ', 'Ǎ', 'Ａ', 'Ä'],
            'B'    => ['Б', 'Β', 'ब', 'Ｂ'],
            'C'    => ['Ç', 'Ć', 'Č', 'Ĉ', 'Ċ', 'Ｃ'],
            'D'    => ['Ď', 'Ð', 'Đ', 'Ɖ', 'Ɗ', 'Ƌ', 'ᴅ', 'ᴆ', 'Д', 'Δ', 'Ｄ'],
            'E'    => ['É', 'È', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ', 'Ë', 'Ē', 'Ę', 'Ě', 'Ĕ', 'Ė', 'Ε', 'Έ', 'Ἐ', 'Ἑ', 'Ἒ', 'Ἓ', 'Ἔ', 'Ἕ', 'Έ', 'Ὲ', 'Е', 'Ё', 'Э', 'Є', 'Ə', 'Ｅ'],
            'F'    => ['Ф', 'Φ', 'Ｆ'],
            'G'    => ['Ğ', 'Ġ', 'Ģ', 'Г', 'Ґ', 'Γ', 'Ｇ'],
            'H'    => ['Η', 'Ή', 'Ħ', 'Ｈ'],
            'I'    => ['Í', 'Ì', 'Ỉ', 'Ĩ', 'Ị', 'Î', 'Ï', 'Ī', 'Ĭ', 'Į', 'İ', 'Ι', 'Ί', 'Ϊ', 'Ἰ', 'Ἱ', 'Ἳ', 'Ἴ', 'Ἵ', 'Ἶ', 'Ἷ', 'Ῐ', 'Ῑ', 'Ὶ', 'Ί', 'И', 'І', 'Ї', 'Ǐ', 'ϒ', 'Ｉ'],
            'J'    => ['Ｊ'],
            'K'    => ['К', 'Κ', 'Ｋ'],
            'L'    => ['Ĺ', 'Ł', 'Л', 'Λ', 'Ļ', 'Ľ', 'Ŀ', 'ल', 'Ｌ'],
            'M'    => ['М', 'Μ', 'Ｍ'],
            'N'    => ['Ń', 'Ñ', 'Ň', 'Ņ', 'Ŋ', 'Н', 'Ν', 'Ｎ'],
            'O'    => ['Ó', 'Ò', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ', 'Ø', 'Ō', 'Ő', 'Ŏ', 'Ο', 'Ό', 'Ὀ', 'Ὁ', 'Ὂ', 'Ὃ', 'Ὄ', 'Ὅ', 'Ὸ', 'Ό', 'О', 'Θ', 'Ө', 'Ǒ', 'Ǿ', 'Ｏ', 'Ö'],
            'P'    => ['П', 'Π', 'Ｐ'],
            'Q'    => ['Ｑ'],
            'R'    => ['Ř', 'Ŕ', 'Р', 'Ρ', 'Ŗ', 'Ｒ'],
            'S'    => ['Ş', 'Ŝ', 'Ș', 'Š', 'Ś', 'С', 'Σ', 'Ｓ'],
            'T'    => ['Ť', 'Ţ', 'Ŧ', 'Ț', 'Т', 'Τ', 'Ｔ'],
            'U'    => ['Ú', 'Ù', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự', 'Û', 'Ū', 'Ů', 'Ű', 'Ŭ', 'Ų', 'У', 'Ǔ', 'Ǖ', 'Ǘ', 'Ǚ', 'Ǜ', 'Ｕ', 'Ў', 'Ü'],
            'V'    => ['В', 'Ｖ'],
            'W'    => ['Ω', 'Ώ', 'Ŵ', 'Ｗ'],
            'X'    => ['Χ', 'Ξ', 'Ｘ'],
            'Y'    => ['Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ', 'Ÿ', 'Ῠ', 'Ῡ', 'Ὺ', 'Ύ', 'Ы', 'Й', 'Υ', 'Ϋ', 'Ŷ', 'Ｙ'],
            'Z'    => ['Ź', 'Ž', 'Ż', 'З', 'Ζ', 'Ｚ'],
            'AE'   => ['Æ', 'Ǽ'],
            'Ch'   => ['Ч'],
            'Dj'   => ['Ђ'],
            'Dz'   => ['Џ'],
            'Gx'   => ['Ĝ'],
            'Hx'   => ['Ĥ'],
            'Ij'   => ['Ĳ'],
            'Jx'   => ['Ĵ'],
            'Kh'   => ['Х'],
            'Lj'   => ['Љ'],
            'Nj'   => ['Њ'],
            'Oe'   => ['Œ'],
            'Ps'   => ['Ψ'],
            'Sh'   => ['Ш'],
            'Shch' => ['Щ'],
            'Ss'   => ['ẞ'],
            'Th'   => ['Þ'],
            'Ts'   => ['Ц'],
            'Ya'   => ['Я'],
            'Yu'   => ['Ю'],
            'Zh'   => ['Ж'],
            ' '    => ["\xC2\xA0", "\xE2\x80\x80", "\xE2\x80\x81", "\xE2\x80\x82", "\xE2\x80\x83", "\xE2\x80\x84", "\xE2\x80\x85", "\xE2\x80\x86", "\xE2\x80\x87", "\xE2\x80\x88", "\xE2\x80\x89", "\xE2\x80\x8A", "\xE2\x80\xAF", "\xE2\x81\x9F", "\xE3\x80\x80", "\xEF\xBE\xA0"],
        ];
    }
}

if (! function_exists('languageSpecificCharsArray')) {
    /**
     * Returns the language specific replacements for the ascii method.
     *
     * Note: Adapted from Stringy\Stringy.
     *
     * @see https://github.com/danielstjules/Stringy/blob/3.1.0/LICENSE.txt
     *
     * @param  string  $language
     * @return array|null
     */
    function languageSpecificCharsArray($language)
    {
        static $languageSpecific;
        if (! isset($languageSpecific)) {
            $languageSpecific = [
                'bg' => [
                    ['х', 'Х', 'щ', 'Щ', 'ъ', 'Ъ', 'ь', 'Ь'],
                    ['h', 'H', 'sht', 'SHT', 'a', 'А', 'y', 'Y'],
                ],
                'de' => [
                    ['ä',  'ö',  'ü',  'Ä',  'Ö',  'Ü'],
                    ['ae', 'oe', 'ue', 'AE', 'OE', 'UE'],
                ],
            ];
        }
        return isset($languageSpecific[$language]) ? $languageSpecific[$language] : null;
    }
}

if (! function_exists('array_first')) {
    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param  array  $array
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    function array_first($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return value($default);
            }
            foreach ($array as $item) {
                return $item;
            }
        }
        foreach ($array as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return $value;
            }
        }
        return value($default);
    }
}

if (! function_exists('is_ip')) {
    /**
     * Validate that an attribute is a valid IP.
     *
     * @param  mixed   $value
     * @return bool
     */
    function is_ip($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
}

if (! function_exists('is_ipv4')) {
    /**
     * Validate that an attribute is a valid IPv4.
     *
     * @param  mixed   $value
     * @return bool
     */
    function is_ipv4($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }
}

if (! function_exists('is_ipv6')) {
    /**
     * Validate that an attribute is a valid IPv6.
     *
     * @param  mixed   $value
     * @return bool
     */
    function is_ipv6($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }
}

if (! function_exists('revert_slug')) {
    /**
     * Revert a slug to non slug format.
     *
     * @param string $slug
     * @param string $delimiter
     * @param string $replacer
     * @return bool
     */
    function revert_slug($slug, $delimiter = '-', $replacer = ' ')
    {
        if (empty($slug)) {
            return '';
        }

        return ucwords(str_replace($delimiter, $replacer, $slug));
    }
}

if (! function_exists('substr_exist')) {
    /**
     * Check if a given substring exists in the given string - Case sensitive.
     *
     * @param string $haystack
     * @param string $needle
     * @param bool $caseSensitive
     * @return bool
     */
    function substr_exist($haystack, $needle, $caseSensitive = false)
    {
        if (!$caseSensitive) {
            $haystack = strtolower($haystack);
            $needle = strtolower($needle);
        }

        return !is_bool(strpos($haystack, $needle));
    }
}

if (! function_exists('client_ip')) {
    /**
     * Get the client Ip from the given request.
     *
     * @param Request $request
     * @return string
     */
    function client_ip(Request $request = null)
    {
        if (is_null($request)) {
            return '';
        }

        // Check for direct forward header
        if (!empty($xForwardedFor = $request->server->get('X_FORWARDED_FOR'))) {
            // Take first Ip as original client router IP.
            $clientIp = array_first(explode(',', $xForwardedFor));

            if (is_ip($clientIp)) {
                return $clientIp;
            }
        }

        // Check for HTTP X Forward Header
        if (!empty($httpXForwardedFor = $request->server->get('HTTP_X_FORWARDED_FOR'))) {
            // Take first Ip as original client router IP.
            $clientIp = array_first(explode(',', $httpXForwardedFor));

            if (is_ip($clientIp)) {
                return $clientIp;
            }
        }

        $ip = $request->getClientIp();

        if (!is_ip($ip)) {
            return $request->server->get('REMOTE_ADDR');
        }

        return $ip;
    }
}

if (! function_exists('is_associative_array')) {
    /**
     * Check if the given array is an associative array.
     *
     * @param $array
     * @return bool
     */
    function is_associative_array($array)
    {
        return is_array($array)
            ? (array_keys($array) !== range(0, count($array) - 1))
            : false;
    }
}

if (! function_exists('debug_exception')) {
    /**
     * Print the Debug information for exception.
     *
     * @param Exception $e
     * @param bool $devOnly
     * @param bool $throw
     * @throws Exception
     */
    function debug_exception(Exception $e, $devOnly = true, $throw = false)
    {
        $scriptName = data_get($_SERVER, 'SCRIPT_NAME', '');
        $isDevEnv = ($scriptName === "app_dev.php") || ($scriptName === 'app/console');

        if (($devOnly && $isDevEnv) || !$devOnly) {
            p($e->getMessage(), $e->getTraceAsString());
        }

        if ($throw) {
            throw $e;
        }
    }
}

if (! function_exists('command')) {
    /**
     * Run a given command.
     *
     * @param $command
     * @param bool $background
     * @param array $parameters
     * @param array $arguments
     * @return mixed
     */
    function command($command, $background = true, $parameters = [], $arguments = [])
    {
        $parameterString = '';
        foreach ($parameters as $name => $value) {
            $parameterString .= " --{$name}={$value}";
        }

        $argumentString = '';
        foreach ($arguments as $name => $value) {
            $argumentString .= " {$value}";
        }

        $command .= " {$parameterString} {$argumentString}";

        if ($background && !substr_exist($command, '/dev/null')) {
            $command .= " &> /dev/null";
        }

        passthru($command, $returnVar);

        return $returnVar;
    }
}

if (! function_exists('backtrace')) {
    /**
     * Print the Debug Backtrace information to the given depth.
     *
     * @param int $depth
     * @param bool $withArgs
     */
    function backtrace($depth = 5, $withArgs = false)
    {
        $scriptName = data_get($_SERVER, 'SCRIPT_NAME', '');
        $isDevEnv = ($scriptName === "app_dev.php") || ($scriptName === 'app/console');
        $depth = ($depth > 0 ? $depth : 5) + 1;

        $backtraceTree = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $depth);
        array_shift($backtraceTree);
        $trace = [];

        if ($isDevEnv) {
            foreach ($backtraceTree as $backtrace) {
                $trace[] = [
                    'class' => data_get($backtrace, 'class'),
                    'function' => data_get($backtrace, 'function'),
                    'line' => data_get($backtrace, 'line'),
                    'file' => data_get($backtrace, 'file'),
                    'args' => $withArgs ? data_get($backtrace, 'args') : "Total Args: " . count(data_get($backtrace, 'args')),
                ];
            }
        }

        p($trace);
    }
}

if (! function_exists('service')) {
    /**
     * Get a given service instance.
     *
     * @param ContainerInterface $container
     * @param $serviceIdentifier
     * @param bool $fresh
     * @return null|object
     */
    function service(ContainerInterface $container, $serviceIdentifier, $fresh = false)
    {
        // If fresh is enabled, then a new instance will be returned.
        if ($fresh) {
            $container->set($serviceIdentifier, null);
        }

        return $container->has($serviceIdentifier)
            ? $container->get($serviceIdentifier)
            : null;
    }
}

if (! function_exists('env')) {
    /**
     * Get a given env value from loaded parameter file.
     *
     * @param $key
     * @param null $default
     * @return null|array|string|mixed
     */
    function env($key, $default = null)
    {
        $container = container();

        if (empty($container)) {
            return $default;
        }

        return $container->hasParameter($key)
            ? (!is_null($parameter = $container->getParameter($key)) ? $parameter : $default)
            : $default;
    }
}

if (! function_exists('container')) {
    /**
     * Get a given env value from loaded parameter file.
     *
     * @return null|ContainerInterface|Container
     */
    function container()
    {
        global $kernel;

        if (!empty($kernel)) {
            return $kernel->getContainer();
        }

        return null;
    }
}

/*************************** Service Facades ***********************************/

if (! function_exists('solr_search_manager')) {
    /**
     * Get solr search manager service instance.
     *
     * @param ContainerInterface $container
     * @return null|\Fa\Bundle\CoreBundle\Manager\SolrSearchManager
     */
    function solr_search_manager(ContainerInterface $container)
    {
        return service($container, 'fa.solrsearch.manager', true);
    }
}

if (! function_exists('feed_reader')) {
    /**
     * Get AdFeedReaderManager service instance.
     *
     * @param ContainerInterface $container
     * @return null|\Fa\Bundle\AdFeedBundle\Manager\AdFeedReaderManager
     */
    function feed_reader(ContainerInterface $container)
    {
        return service($container, 'fa_ad.manager.ad_feed_reader');
    }
}

if (! function_exists('request')) {
    /**
     * Get current Request instance.
     *
     * @param ContainerInterface $container
     * @return null|Request
     */
    function request(ContainerInterface $container)
    {
        return service($container, 'request');
    }
}

if (! function_exists('solr_ad_index')) {
    /**
     * Get AdSolrIndex service instance.
     *
     * @param ContainerInterface $container
     * @return null|AdSolrIndex
     */
    function solr_ad_index(ContainerInterface $container)
    {
        return service($container, 'fa.ad.solrindex');
    }
}

if (! function_exists('solr_ad_client')) {
    /**
     * Get BootStrapSolr service instance.
     *
     * @param ContainerInterface $container
     * @return null|BootStrapSolr
     */
    function solr_ad_client(ContainerInterface $container)
    {
        return service($container, 'fa.solr.client.ad');
    }
}

if (! function_exists('search_filter')) {
    /**
     * Get SearchFiltersManager service instance.
     *
     * @param ContainerInterface $container
     * @return null|Fa\Bundle\CoreBundle\Manager\SearchFiltersManager
     */
    function search_filter(ContainerInterface $container)
    {
        return service($container, 'fa.searchfilters.manager');
    }
}

if (! function_exists('sql_searcher')) {
    /**
     * Get SqlSearchManager service instance.
     *
     * @param ContainerInterface $container
     * @return null|Fa\Bundle\CoreBundle\Manager\SqlSearchManager
     */
    function sql_searcher(ContainerInterface $container)
    {
        return service($container, 'fa.sqlsearch.manager');
    }
}

if (! function_exists('paginator')) {
    /**
     * Get PaginationManager service instance.
     *
     * @param ContainerInterface $container
     * @return null|Fa\Bundle\CoreBundle\Manager\PaginationManager
     */
    function paginator(ContainerInterface $container)
    {
        return service($container, 'fa.pagination.manager');
    }
}

if (! function_exists('router')) {
    /**
     * Get Router instance.
     *
     * @param ContainerInterface $container
     * @return \Symfony\Component\Routing\Router
     */
    function router(ContainerInterface $container)
    {
        return service($container, 'router');
    }
}

if (! function_exists('paypal_manager')) {
    /**
     * Get PaypalManager instance.
     *
     * @param ContainerInterface $container
     * @return \Fa\Bundle\PaymentBundle\Manager\PaypalManager
     */
    function paypal_manager(ContainerInterface $container)
    {
        return service($container, 'fa.paypal.manager');
    }
}

if (! function_exists('paypal_subscription_manager')) {
    /**
     * Get PaypalSubscriptionManager instance.
     *
     * @param ContainerInterface $container
     * @return \Fa\Bundle\PaymentBundle\Manager\PaypalSubscriptionManager
     */
    function paypal_subscription_manager(ContainerInterface $container)
    {
        return service($container, 'fa.paypal.subscription.manager');
    }
}

if (! function_exists('mailer')) {
    /**
     * Get MailManager instance.
     *
     * @param ContainerInterface $container
     * @return \Fa\Bundle\EmailBundle\Manager\MailManager
     */
    function mailer(ContainerInterface $container)
    {
        return service($container, 'fa.mail.manager');
    }
}

if (! function_exists('session')) {
    /**
     * Get Session instance.
     *
     * @param ContainerInterface $container
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    function session(ContainerInterface $container)
    {
        return service($container, 'session');
    }
}

if (! function_exists('coupon_manager')) {
    /**
     * Get Session instance.
     *
     * @param ContainerInterface $container
     * @return \Fa\Bundle\PaymentBundle\Manager\CouponCodeManager
     */
    function coupon_manager(ContainerInterface $container)
    {
        return service($container, 'fa.coupon_code_manager');
    }
}

if (! function_exists('flush_db')) {
    /**
     * Flush the given entity to DB.
     *
     * @param EntityManager $entityManager
     * @param $entity
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    function flush_db(EntityManager $entityManager, $entity = null)
    {
        if (empty($entity)) {
            $entityManager->flush();
            return true;
        }

        $entityManager->persist($entity);
        $entityManager->flush($entity);
    }
}

if (! function_exists('ad_request_listener')) {
    /**
     * Get AdRequestListener instance.
     *
     * @param ContainerInterface $container
     * @return null|\Fa\Bundle\AdBundle\EventListener\AdRequestListener
     */
    function ad_request_listener(ContainerInterface $container)
    {
        return service($container, 'fa_ad_kernel.request.listener');
    }
}

if (! function_exists('ad_routing_manager')) {
    /**
     * Get AdRequestListener instance.
     *
     * @return null|\Fa\Bundle\AdBundle\Manager\AdRoutingManager
     */
    function ad_routing_manager()
    {
        return service(container(), 'fa_ad.manager.ad_routing');
    }
}

/*************************** Other Helpers ***********************************/

if (! function_exists('secure_keyword')) {
    /**
     * Secure keyword from basic injections.
     *
     * @param $keyword
     * @return string
     */
    function secure_keyword($keyword)
    {
        if (empty($keyword)) {
            return '';
        }

        $keyword = trim(addslashes(str_ireplace(
            [' and ', ' or ', ' not ', ' & ', '-', '&', "\"", '\'', ';', '[', '{', ']', '}', '=', '*', '\\', '/', '%', '^', '`', '+', '!', '?', '&&', '||', '|'],
            ['', ' ', ' ', ' ', ' ', ' '],
            trim($keyword))));

        return $keyword;
    }
}
if (! function_exists('csv_reader')) {
    /**
     * Get the CSV Reader for the given file & offset.
     *
     * @param $file
     * @param int $offset
     * @param string $delimiter
     * @return Reader
     */
    function csv_reader($file, $offset = 0, $delimiter = ',')
    {
        if (!file_exists($file)) {
            echo "\n\nUnable to find csv file: {$file}\n\n";
            exit;
        }

        $reader = new Reader($file);
        $reader->setDelimiter($delimiter);
        $reader->setHeaderLine(0);

        // This is very much necessary for header & line seeker
        $row = $reader->getRow();

        if ($offset > 0)
            $reader->advanceTo($offset-1);
        else
            $reader->advanceTo(1);

        return $reader;
    }
}

if (! function_exists('array_only')) {
    /**
     * Returns the array with only given keys.
     *
     * @param array $array
     * @param array $keys
     * @param bool $preserveKeys
     * @return array
     */
    function array_only($array = [], $keys = [], $preserveKeys = false)
    {
        if (empty($keys) || empty($array) || empty(array_intersect(array_keys($array), $keys))) {
            return [];
        }

        $filtered = [];
        foreach ($array as $key => $value) {

            if (!in_array($key, $keys)) {
                continue;
            }

            if ($preserveKeys) {
                $filtered[$key] = $value;
            } else {
                $filtered[] = $value;
            }
        }

        return $filtered;
    }
}

if (! function_exists('array_last')) {
    /**
     * Get Last element in the array.
     *
     * @param array $data
     * @return mixed
     */
    function array_last($data = [])
    {
        return array_first(array_reverse($data));
    }
}

if (! function_exists('site_business_category_enabled')) {
    /**
     * Check if business Category is enabled for site.
     *
     * @param ContainerInterface $container
     * @return bool
     */
    function site_business_category_enabled(ContainerInterface $container)
    {
        return $container->hasParameter('business_category_flag') && filter_var($container->getParameter('business_category_flag'), FILTER_VALIDATE_BOOLEAN);
    }
}

if (! function_exists('str_singular')) {
    /**
     * Get the singular form of an English word.
     *
     * @param $string
     * @return string
     */
    function str_singular($string)
    {
        return Pluralizer::singular($string);
    }
}

if (! function_exists('str_plural')) {
    /**
     * Get the plural form of an English word.
     *
     * @param $string
     * @param int $count
     * @return string
     */
    function str_plural($string, $count = 2)
    {
        return Pluralizer::plural($string, $count);
    }
}

if (! function_exists('now')) {
    /**
     * Get the plural form of an English word.
     *
     * @param $format
     * @return string
     */
    function now($format = 'Y/m/d H:i:s')
    {
        return date($format, time());
    }
}

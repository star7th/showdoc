<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * @psalm-immutable
 */
final class ASCII
{
    //
    // INFO: https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
    //

    const UZBEK_LANGUAGE_CODE = 'uz';

    const TURKMEN_LANGUAGE_CODE = 'tk';

    const THAI_LANGUAGE_CODE = 'th';

    const PASHTO_LANGUAGE_CODE = 'ps';

    const ORIYA_LANGUAGE_CODE = 'or';

    const MONGOLIAN_LANGUAGE_CODE = 'mn';

    const KOREAN_LANGUAGE_CODE = 'ko';

    const KIRGHIZ_LANGUAGE_CODE = 'ky';

    const ARMENIAN_LANGUAGE_CODE = 'hy';

    const BENGALI_LANGUAGE_CODE = 'bn';

    const BELARUSIAN_LANGUAGE_CODE = 'be';

    const AMHARIC_LANGUAGE_CODE = 'am';

    const JAPANESE_LANGUAGE_CODE = 'ja';

    const CHINESE_LANGUAGE_CODE = 'zh';

    const DUTCH_LANGUAGE_CODE = 'nl';

    const ITALIAN_LANGUAGE_CODE = 'it';

    const MACEDONIAN_LANGUAGE_CODE = 'mk';

    const PORTUGUESE_LANGUAGE_CODE = 'pt';

    const GREEKLISH_LANGUAGE_CODE = 'el__greeklish';

    const GREEK_LANGUAGE_CODE = 'el';

    const HINDI_LANGUAGE_CODE = 'hi';

    const SWEDISH_LANGUAGE_CODE = 'sv';

    const TURKISH_LANGUAGE_CODE = 'tr';

    const BULGARIAN_LANGUAGE_CODE = 'bg';

    const HUNGARIAN_LANGUAGE_CODE = 'hu';

    const MYANMAR_LANGUAGE_CODE = 'my';

    const CROATIAN_LANGUAGE_CODE = 'hr';

    const FINNISH_LANGUAGE_CODE = 'fi';

    const GEORGIAN_LANGUAGE_CODE = 'ka';

    const RUSSIAN_LANGUAGE_CODE = 'ru';

    const RUSSIAN_PASSPORT_2013_LANGUAGE_CODE = 'ru__passport_2013';

    const RUSSIAN_GOST_2000_B_LANGUAGE_CODE = 'ru__gost_2000_b';

    const UKRAINIAN_LANGUAGE_CODE = 'uk';

    const KAZAKH_LANGUAGE_CODE = 'kk';

    const CZECH_LANGUAGE_CODE = 'cs';

    const DANISH_LANGUAGE_CODE = 'da';

    const POLISH_LANGUAGE_CODE = 'pl';

    const ROMANIAN_LANGUAGE_CODE = 'ro';

    const ESPERANTO_LANGUAGE_CODE = 'eo';

    const ESTONIAN_LANGUAGE_CODE = 'et';

    const LATVIAN_LANGUAGE_CODE = 'lv';

    const LITHUANIAN_LANGUAGE_CODE = 'lt';

    const NORWEGIAN_LANGUAGE_CODE = 'no';

    const VIETNAMESE_LANGUAGE_CODE = 'vi';

    const ARABIC_LANGUAGE_CODE = 'ar';

    const PERSIAN_LANGUAGE_CODE = 'fa';

    const SERBIAN_LANGUAGE_CODE = 'sr';

    const SERBIAN_CYRILLIC_LANGUAGE_CODE = 'sr__cyr';

    const SERBIAN_LATIN_LANGUAGE_CODE = 'sr__lat';

    const AZERBAIJANI_LANGUAGE_CODE = 'az';

    const SLOVAK_LANGUAGE_CODE = 'sk';

    const FRENCH_LANGUAGE_CODE = 'fr';

    const FRENCH_AUSTRIAN_LANGUAGE_CODE = 'fr_at';

    const FRENCH_SWITZERLAND_LANGUAGE_CODE = 'fr_ch';

    const GERMAN_LANGUAGE_CODE = 'de';

    const GERMAN_AUSTRIAN_LANGUAGE_CODE = 'de_at';

    const GERMAN_SWITZERLAND_LANGUAGE_CODE = 'de_ch';

    const ENGLISH_LANGUAGE_CODE = 'en';

    const EXTRA_LATIN_CHARS_LANGUAGE_CODE = 'latin';

    const EXTRA_WHITESPACE_CHARS_LANGUAGE_CODE = ' ';

    const EXTRA_MSWORD_CHARS_LANGUAGE_CODE = 'msword';

    /**
     * @var array<string, array<string, string>>|null
     */
    private static $ASCII_MAPS;

    /**
     * @var array<string, array<string, string>>|null
     */
    private static $ASCII_MAPS_AND_EXTRAS;

    /**
     * @var array<string, array<string, string>>|null
     */
    private static $ASCII_EXTRAS;

    /**
     * @var array<string, int>|null
     */
    private static $ORD;

    /**
     * @var array<string, int>|null
     */
    private static $LANGUAGE_MAX_KEY;

    /**
     * url: https://en.wikipedia.org/wiki/Wikipedia:ASCII#ASCII_printable_characters
     *
     * @var string
     */
    private static $REGEX_ASCII = "[^\x09\x10\x13\x0A\x0D\x20-\x7E]";

    /**
     * bidirectional text chars
     *
     * url: https://www.w3.org/International/questions/qa-bidi-unicode-controls
     *
     * @var array<int, string>
     */
    private static $BIDI_UNI_CODE_CONTROLS_TABLE = [
        // LEFT-TO-RIGHT EMBEDDING (use -> dir = "ltr")
        8234 => "\xE2\x80\xAA",
        // RIGHT-TO-LEFT EMBEDDING (use -> dir = "rtl")
        8235 => "\xE2\x80\xAB",
        // POP DIRECTIONAL FORMATTING // (use -> </bdo>)
        8236 => "\xE2\x80\xAC",
        // LEFT-TO-RIGHT OVERRIDE // (use -> <bdo dir = "ltr">)
        8237 => "\xE2\x80\xAD",
        // RIGHT-TO-LEFT OVERRIDE // (use -> <bdo dir = "rtl">)
        8238 => "\xE2\x80\xAE",
        // LEFT-TO-RIGHT ISOLATE // (use -> dir = "ltr")
        8294 => "\xE2\x81\xA6",
        // RIGHT-TO-LEFT ISOLATE // (use -> dir = "rtl")
        8295 => "\xE2\x81\xA7",
        // FIRST STRONG ISOLATE // (use -> dir = "auto")
        8296 => "\xE2\x81\xA8",
        // POP DIRECTIONAL ISOLATE
        8297 => "\xE2\x81\xA9",
    ];

    /**
     * Get all languages from the constants "ASCII::.*LANGUAGE_CODE".
     *
     * @return string[]
     *
     * @phpstan-return array<string, string>
     */
    public static function getAllLanguages(): array
    {
        // init
        static $LANGUAGES = [];

        if ($LANGUAGES !== []) {
            return $LANGUAGES;
        }

        foreach ((new \ReflectionClass(__CLASS__))->getConstants() as $constant => $lang) {
            if (\strpos($constant, 'EXTRA') !== false) {
                $LANGUAGES[\strtolower($constant)] = $lang;
            } else {
                $LANGUAGES[\strtolower(\str_replace('_LANGUAGE_CODE', '', $constant))] = $lang;
            }
        }

        return $LANGUAGES;
    }

    /**
     * Returns an replacement array for ASCII methods.
     *
     * EXAMPLE: <code>
     * $array = ASCII::charsArray();
     * var_dump($array['ru']['б']); // 'b'
     * </code>
     *
     * @psalm-suppress InvalidNullableReturnType - we use the prepare* methods here, so we don't get NULL here
     *
     * @param bool $replace_extra_symbols [optional] <p>Add some more replacements e.g. "£" with " pound ".</p>
     *
     * @psalm-pure
     *
     * @return array
     *
     * @phpstan-return array<string, array<string , string>>
     */
    public static function charsArray(bool $replace_extra_symbols = false): array
    {
        if ($replace_extra_symbols) {
            self::prepareAsciiAndExtrasMaps();

            return self::$ASCII_MAPS_AND_EXTRAS ?? [];
        }

        self::prepareAsciiMaps();

        return self::$ASCII_MAPS ?? [];
    }

    /**
     * Returns an replacement array for ASCII methods with a mix of multiple languages.
     *
     * EXAMPLE: <code>
     * $array = ASCII::charsArrayWithMultiLanguageValues();
     * var_dump($array['b']); // ['β', 'б', 'ဗ', 'ბ', 'ب']
     * </code>
     *
     * @param bool $replace_extra_symbols [optional] <p>Add some more replacements e.g. "£" with " pound ".</p>
     *
     * @psalm-pure
     *
     * @return array
     *               <p>An array of replacements.</p>
     *
     * @phpstan-return array<string, array<int, string>>
     */
    public static function charsArrayWithMultiLanguageValues(bool $replace_extra_symbols = false): array
    {
        /**
         * @var array<string, array>
         */
        static $CHARS_ARRAY = [];
        $cacheKey = '' . $replace_extra_symbols;

        if (isset($CHARS_ARRAY[$cacheKey])) {
            return $CHARS_ARRAY[$cacheKey];
        }

        // init
        $return = [];
        $language_all_chars = self::charsArrayWithSingleLanguageValues(
            $replace_extra_symbols,
            false
        );

        /** @noinspection PhpSillyAssignmentInspection - hack for phpstan */
        /** @var array<string, string> $language_all_chars */
        $language_all_chars = $language_all_chars;

        /** @noinspection AlterInForeachInspection */
        foreach ($language_all_chars as $key => &$value) {
            $return[$value][] = $key;
        }

        $CHARS_ARRAY[$cacheKey] = $return;

        /** @var array<string, array<int, string>> $return - hack for phpstan */
        return $return;
    }

    /**
     * Returns an replacement array for ASCII methods with one language.
     *
     * For example, German will map 'ä' to 'ae', while other languages
     * will simply return e.g. 'a'.
     *
     * EXAMPLE: <code>
     * $array = ASCII::charsArrayWithOneLanguage('ru');
     * $tmpKey = \array_search('yo', $array['replace']);
     * echo $array['orig'][$tmpKey]; // 'ё'
     * </code>
     *
     * @psalm-suppress InvalidNullableReturnType - we use the prepare* methods here, so we don't get NULL here
     *
     * @param string $language              [optional] <p>Language of the source string e.g.: en, de_at, or de-ch.
     *                                      (default is 'en') | ASCII::*_LANGUAGE_CODE</p>
     * @param bool   $replace_extra_symbols [optional] <p>Add some more replacements e.g. "£" with " pound ".</p>
     * @param bool   $asOrigReplaceArray    [optional] <p>TRUE === return {orig: string[], replace: string[]}
     *                                      array</p>
     *
     * @psalm-pure
     *
     * @return array
     *               <p>An array of replacements.</p>
     *
     * @phpstan-return array{orig: string[], replace: string[]}|array<string, string>
     */
    public static function charsArrayWithOneLanguage(
        string $language = self::ENGLISH_LANGUAGE_CODE,
        bool $replace_extra_symbols = false,
        bool $asOrigReplaceArray = true
    ): array {
        $language = self::get_language($language);

        // init
        /**
         * @var array<string, array>
         */
        static $CHARS_ARRAY = [];
        $cacheKey = '' . $replace_extra_symbols . '-' . $asOrigReplaceArray;

        // check static cache
        if (isset($CHARS_ARRAY[$cacheKey][$language])) {
            return $CHARS_ARRAY[$cacheKey][$language];
        }

        if ($replace_extra_symbols) {
            self::prepareAsciiAndExtrasMaps();

            /** @noinspection DuplicatedCode */
            if (isset(self::$ASCII_MAPS_AND_EXTRAS[$language])) {
                $tmpArray = self::$ASCII_MAPS_AND_EXTRAS[$language];

                if ($asOrigReplaceArray) {
                    $CHARS_ARRAY[$cacheKey][$language] = [
                        'orig'    => \array_keys($tmpArray),
                        'replace' => \array_values($tmpArray),
                    ];
                } else {
                    $CHARS_ARRAY[$cacheKey][$language] = $tmpArray;
                }
            } else {
                /** @noinspection NestedPositiveIfStatementsInspection */
                if ($asOrigReplaceArray) {
                    $CHARS_ARRAY[$cacheKey][$language] = [
                        'orig'    => [],
                        'replace' => [],
                    ];
                } else {
                    $CHARS_ARRAY[$cacheKey][$language] = [];
                }
            }
        } else {
            self::prepareAsciiMaps();

            /** @noinspection DuplicatedCode */
            if (isset(self::$ASCII_MAPS[$language])) {
                $tmpArray = self::$ASCII_MAPS[$language];

                if ($asOrigReplaceArray) {
                    $CHARS_ARRAY[$cacheKey][$language] = [
                        'orig'    => \array_keys($tmpArray),
                        'replace' => \array_values($tmpArray),
                    ];
                } else {
                    $CHARS_ARRAY[$cacheKey][$language] = $tmpArray;
                }
            } else {
                /** @noinspection NestedPositiveIfStatementsInspection */
                if ($asOrigReplaceArray) {
                    $CHARS_ARRAY[$cacheKey][$language] = [
                        'orig'    => [],
                        'replace' => [],
                    ];
                } else {
                    $CHARS_ARRAY[$cacheKey][$language] = [];
                }
            }
        }

        return $CHARS_ARRAY[$cacheKey][$language] ?? ['orig' => [], 'replace' => []];
    }

    /**
     * Returns an replacement array for ASCII methods with multiple languages.
     *
     * EXAMPLE: <code>
     * $array = ASCII::charsArrayWithSingleLanguageValues();
     * $tmpKey = \array_search('hnaik', $array['replace']);
     * echo $array['orig'][$tmpKey]; // '၌'
     * </code>
     *
     * @param bool $replace_extra_symbols [optional] <p>Add some more replacements e.g. "£" with " pound ".</p>
     * @param bool $asOrigReplaceArray    [optional] <p>TRUE === return {orig: string[], replace: string[]}
     *                                    array</p>
     *
     * @psalm-pure
     *
     * @return array
     *               <p>An array of replacements.</p>
     *
     * @phpstan-return array{orig: string[], replace: string[]}|array<string, string>
     */
    public static function charsArrayWithSingleLanguageValues(
        bool $replace_extra_symbols = false,
        bool $asOrigReplaceArray = true
    ): array {
        // init
        /**
         * @var array<string,array>
         */
        static $CHARS_ARRAY = [];
        $cacheKey = '' . $replace_extra_symbols . '-' . $asOrigReplaceArray;

        if (isset($CHARS_ARRAY[$cacheKey])) {
            return $CHARS_ARRAY[$cacheKey];
        }

        if ($replace_extra_symbols) {
            self::prepareAsciiAndExtrasMaps();

            /** @noinspection AlterInForeachInspection */
            /** @psalm-suppress PossiblyNullIterator - we use the prepare* methods here, so we don't get NULL here */
            foreach (self::$ASCII_MAPS_AND_EXTRAS ?? [] as &$map) {
                $CHARS_ARRAY[$cacheKey][] = $map;
            }
        } else {
            self::prepareAsciiMaps();

            /** @noinspection AlterInForeachInspection */
            /** @psalm-suppress PossiblyNullIterator - we use the prepare* methods here, so we don't get NULL here */
            foreach (self::$ASCII_MAPS ?? [] as &$map) {
                $CHARS_ARRAY[$cacheKey][] = $map;
            }
        }

        $CHARS_ARRAY[$cacheKey] = \array_merge([], ...$CHARS_ARRAY[$cacheKey]);

        if ($asOrigReplaceArray) {
            $CHARS_ARRAY[$cacheKey] = [
                'orig'    => \array_keys($CHARS_ARRAY[$cacheKey]),
                'replace' => \array_values($CHARS_ARRAY[$cacheKey]),
            ];
        }

        return $CHARS_ARRAY[$cacheKey];
    }

    /**
     * Accepts a string and removes all non-UTF-8 characters from it + extras if needed.
     *
     * @param string $str                         <p>The string to be sanitized.</p>
     * @param bool   $normalize_whitespace        [optional] <p>Set to true, if you need to normalize the
     *                                            whitespace.</p>
     * @param bool   $normalize_msword            [optional] <p>Set to true, if you need to normalize MS Word chars
     *                                            e.g.: "…"
     *                                            => "..."</p>
     * @param bool   $keep_non_breaking_space     [optional] <p>Set to true, to keep non-breaking-spaces, in
     *                                            combination with
     *                                            $normalize_whitespace</p>
     * @param bool   $remove_invisible_characters [optional] <p>Set to false, if you not want to remove invisible
     *                                            characters e.g.: "\0"</p>
     *
     * @psalm-pure
     *
     * @return string
     *                <p>A clean UTF-8 string.</p>
     */
    public static function clean(
        string $str,
        bool $normalize_whitespace = true,
        bool $keep_non_breaking_space = false,
        bool $normalize_msword = true,
        bool $remove_invisible_characters = true
    ): string {
        // http://stackoverflow.com/questions/1401317/remove-non-utf8-characters-from-string
        // caused connection reset problem on larger strings

        $regex = '/
          (
            (?: [\x00-\x7F]               # single-byte sequences   0xxxxxxx
            |   [\xC0-\xDF][\x80-\xBF]    # double-byte sequences   110xxxxx 10xxxxxx
            |   [\xE0-\xEF][\x80-\xBF]{2} # triple-byte sequences   1110xxxx 10xxxxxx * 2
            |   [\xF0-\xF7][\x80-\xBF]{3} # quadruple-byte sequence 11110xxx 10xxxxxx * 3
            ){1,100}                      # ...one or more times
          )
        | ( [\x80-\xBF] )                 # invalid byte in range 10000000 - 10111111
        | ( [\xC0-\xFF] )                 # invalid byte in range 11000000 - 11111111
        /x';
        $str = (string) \preg_replace($regex, '$1', $str);

        if ($normalize_whitespace) {
            $str = self::normalize_whitespace($str, $keep_non_breaking_space);
        }

        if ($normalize_msword) {
            $str = self::normalize_msword($str);
        }

        if ($remove_invisible_characters) {
            $str = self::remove_invisible_characters($str);
        }

        return $str;
    }

    /**
     * Checks if a string is 7 bit ASCII.
     *
     * EXAMPLE: <code>
     * ASCII::is_ascii('白'); // false
     * </code>
     *
     * @param string $str <p>The string to check.</p>
     *
     * @psalm-pure
     *
     * @return bool
     *              <p>
     *              <strong>true</strong> if it is ASCII<br>
     *              <strong>false</strong> otherwise
     *              </p>
     */
    public static function is_ascii(string $str): bool
    {
        if ($str === '') {
            return true;
        }

        return !\preg_match('/' . self::$REGEX_ASCII . '/', $str);
    }

    /**
     * Returns a string with smart quotes, ellipsis characters, and dashes from
     * Windows-1252 (commonly used in Word documents) replaced by their ASCII
     * equivalents.
     *
     * EXAMPLE: <code>
     * ASCII::normalize_msword('„Abcdef…”'); // '"Abcdef..."'
     * </code>
     *
     * @param string $str <p>The string to be normalized.</p>
     *
     * @psalm-pure
     *
     * @return string
     *                <p>A string with normalized characters for commonly used chars in Word documents.</p>
     */
    public static function normalize_msword(string $str): string
    {
        if ($str === '') {
            return '';
        }

        /**
         * @var array{orig: string[], replace: string[]}
         */
        static $MSWORD_CACHE = ['orig' => [], 'replace' => []];

        if (empty($MSWORD_CACHE['orig'])) {
            self::prepareAsciiMaps();

            /**
             * @psalm-suppress PossiblyNullArrayAccess - we use the prepare* methods here, so we don't get NULL here
             *
             * @var array<string, string>
             */
            $map = self::$ASCII_MAPS[self::EXTRA_MSWORD_CHARS_LANGUAGE_CODE] ?? [];

            $MSWORD_CACHE = [
                'orig'    => \array_keys($map),
                'replace' => \array_values($map),
            ];
        }

        return \str_replace($MSWORD_CACHE['orig'], $MSWORD_CACHE['replace'], $str);
    }

    /**
     * Normalize the whitespace.
     *
     * EXAMPLE: <code>
     * ASCII::normalize_whitespace("abc-\xc2\xa0-öäü-\xe2\x80\xaf-\xE2\x80\xAC", true); // "abc-\xc2\xa0-öäü- -"
     * </code>
     *
     * @param string $str                          <p>The string to be normalized.</p>
     * @param bool   $keepNonBreakingSpace         [optional] <p>Set to true, to keep non-breaking-spaces.</p>
     * @param bool   $keepBidiUnicodeControls      [optional] <p>Set to true, to keep non-printable (for the web)
     *                                             bidirectional text chars.</p>
     * @param bool   $normalize_control_characters [optional] <p>Set to true, to convert e.g. LINE-, PARAGRAPH-SEPARATOR with "\n" and LINE TABULATION with "\t".</p>
     *
     * @psalm-pure
     *
     * @return string
     *                <p>A string with normalized whitespace.</p>
     */
    public static function normalize_whitespace(
        string $str,
        bool $keepNonBreakingSpace = false,
        bool $keepBidiUnicodeControls = false,
        bool $normalize_control_characters = false
    ): string {
        if ($str === '') {
            return '';
        }

        /**
         * @var array<int,array<string,string>>
         */
        static $WHITESPACE_CACHE = [];
        $cacheKey = (int) $keepNonBreakingSpace;

        if ($normalize_control_characters) {
            $str = \str_replace(
                [
                    "\x0d\x0c",     // 'END OF LINE'
                    "\xe2\x80\xa8", // 'LINE SEPARATOR'
                    "\xe2\x80\xa9", // 'PARAGRAPH SEPARATOR'
                    "\x0c",         // 'FORM FEED' // "\f"
                    "\x0b",         // 'VERTICAL TAB' // "\v"
                ],
                [
                    "\n",
                    "\n",
                    "\n",
                    "\n",
                    "\t",
                ],
                $str
            );
        }

        if (!isset($WHITESPACE_CACHE[$cacheKey])) {
            self::prepareAsciiMaps();

            $WHITESPACE_CACHE[$cacheKey] = self::$ASCII_MAPS[self::EXTRA_WHITESPACE_CHARS_LANGUAGE_CODE] ?? [];

            if ($keepNonBreakingSpace) {
                unset($WHITESPACE_CACHE[$cacheKey]["\xc2\xa0"]);
            }

            $WHITESPACE_CACHE[$cacheKey] = \array_keys($WHITESPACE_CACHE[$cacheKey]);
        }

        if (!$keepBidiUnicodeControls) {
            /**
             * @var array<int,string>|null
             */
            static $BIDI_UNICODE_CONTROLS_CACHE = null;

            if ($BIDI_UNICODE_CONTROLS_CACHE === null) {
                $BIDI_UNICODE_CONTROLS_CACHE = self::$BIDI_UNI_CODE_CONTROLS_TABLE;
            }

            $str = \str_replace($BIDI_UNICODE_CONTROLS_CACHE, '', $str);
        }

        return \str_replace($WHITESPACE_CACHE[$cacheKey], ' ', $str);
    }

    /**
     * Remove invisible characters from a string.
     *
     * e.g.: This prevents sandwiching null characters between ascii characters, like Java\0script.
     *
     * copy&past from https://github.com/bcit-ci/CodeIgniter/blob/develop/system/core/Common.php
     *
     * @param string $str
     * @param bool   $url_encoded
     * @param string $replacement
     * @param bool   $keep_basic_control_characters
     *
     * @psalm-pure
     *
     * @return string
     */
    public static function remove_invisible_characters(
        string $str,
        bool $url_encoded = false,
        string $replacement = '',
        bool $keep_basic_control_characters = true
    ): string {
        // init
        $non_displayables = [];

        // every control character except:
        // - newline (dec 10),
        // - carriage return (dec 13),
        // - horizontal tab (dec 09)
        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcefBCEF]/'; // url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-fA-F]/'; // url encoded 16-31
        }

        if ($keep_basic_control_characters) {
            $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127
        } else {
            $str = self::normalize_whitespace($str, false, false, true);
            $non_displayables[] = '/[^\P{C}\s]/u';
        }

        do {
            $str = (string) \preg_replace($non_displayables, $replacement, $str, -1, $count);
        } while ($count !== 0);

        return $str;
    }

    /**
     *  WARNING: This method will return broken characters and is only for special cases.
     *
     * Convert two UTF-8 encoded string to a single-byte strings suitable for
     * functions that need the same string length after the conversion.
     *
     * The function simply uses (and updates) a tailored dynamic encoding
     * (in/out map parameter) where non-ascii characters are remapped to
     * the range [128-255] in order of appearance.
     *
     * @param string $str1
     * @param string $str2
     *
     * @return string[]
     *
     * @phpstan-return array{0: string, 1: string}
     */
    public static function to_ascii_remap(string $str1, string $str2): array
    {
        $charMap = [];
        $str1 = self::to_ascii_remap_intern($str1, $charMap);
        $str2 = self::to_ascii_remap_intern($str2, $charMap);

        return [$str1, $str2];
    }

    /**
     * WARNING: This method will return broken characters and is only for special cases.
     *
     * Convert a UTF-8 encoded string to a single-byte string suitable for
     * functions that need the same string length after the conversion.
     *
     * The function simply uses (and updates) a tailored dynamic encoding
     * (in/out map parameter) where non-ascii characters are remapped to
     * the range [128-255] in order of appearance.
     *
     * Thus, it supports up to 128 different multibyte code points max over
     * the whole set of strings sharing this encoding.
     *
     * Source: https://github.com/KEINOS/mb_levenshtein
     *
     * @param  string $str UTF-8 string to be converted to extended ASCII.
     * @return string Mapped borken string.
     */
    private static function to_ascii_remap_intern(string $str, array &$map): string
    {
        // find all utf-8 characters
        $matches = [];
        if (!\preg_match_all('/[\xC0-\xF7][\x80-\xBF]+/', $str, $matches)) {
            return $str; // plain ascii string
        }

        // update the encoding map with the characters not already met
        $mapCount = \count($map);
        foreach ($matches[0] as $mbc) {
            if (!isset($map[$mbc])) {
                $map[$mbc] = \chr(128 + $mapCount);
                $mapCount++;
            }
        }

        // finally remap non-ascii characters
        return \strtr($str, $map);
    }

    /**
     * Returns an ASCII version of the string. A set of non-ASCII characters are
     * replaced with their closest ASCII counterparts, and the rest are removed
     * by default. The language or locale of the source string can be supplied
     * for language-specific transliteration in any of the following formats:
     * en, en_GB, or en-GB. For example, passing "de" results in "äöü" mapping
     * to "aeoeue" rather than "aou" as in other languages.
     *
     * EXAMPLE: <code>
     * ASCII::to_ascii('�Düsseldorf�', 'en'); // Dusseldorf
     * </code>
     *
     * @param string    $str                       <p>The input string.</p>
     * @param string    $language                  [optional] <p>Language of the source string.
     *                                             (default is 'en') | ASCII::*_LANGUAGE_CODE</p>
     * @param bool      $remove_unsupported_chars  [optional] <p>Whether or not to remove the
     *                                             unsupported characters.</p>
     * @param bool      $replace_extra_symbols     [optional]  <p>Add some more replacements e.g. "£" with " pound
     *                                             ".</p>
     * @param bool      $use_transliterate         [optional]  <p>Use ASCII::to_transliterate() for unknown chars.</p>
     * @param bool|null $replace_single_chars_only [optional]  <p>Single char replacement is better for the
     *                                             performance, but some languages need to replace more then one char
     *                                             at the same time. | NULL === auto-setting, depended on the
     *                                             language</p>
     *
     * @psalm-pure
     *
     * @return string
     *                <p>A string that contains only ASCII characters.</p>
     */
    public static function to_ascii(
        string $str,
        string $language = self::ENGLISH_LANGUAGE_CODE,
        bool $remove_unsupported_chars = true,
        bool $replace_extra_symbols = false,
        bool $use_transliterate = false,
        bool $replace_single_chars_only = null
    ): string {
        if ($str === '') {
            return '';
        }

        $language = self::get_language($language);

        static $EXTRA_SYMBOLS_CACHE = null;

        /**
         * @var array<string,array<string,string>>
         */
        static $REPLACE_HELPER_CACHE = [];
        $cacheKey = $language . '-' . $replace_extra_symbols;

        if (!isset($REPLACE_HELPER_CACHE[$cacheKey])) {
            $langAll = self::charsArrayWithSingleLanguageValues($replace_extra_symbols, false);

            $langSpecific = self::charsArrayWithOneLanguage($language, $replace_extra_symbols, false);

            if ($langSpecific === []) {
                $REPLACE_HELPER_CACHE[$cacheKey] = $langAll;
            } else {
                $REPLACE_HELPER_CACHE[$cacheKey] = \array_merge([], $langAll, $langSpecific);
            }
        }

        if (
            $replace_extra_symbols
            &&
            $EXTRA_SYMBOLS_CACHE === null
        ) {
            $EXTRA_SYMBOLS_CACHE = [];
            foreach (self::$ASCII_EXTRAS ?? [] as $extrasDataTmp) {
                foreach ($extrasDataTmp as $extrasDataKeyTmp => $extrasDataValueTmp) {
                    $EXTRA_SYMBOLS_CACHE[$extrasDataKeyTmp] = $extrasDataKeyTmp;
                }
            }
            $EXTRA_SYMBOLS_CACHE = \implode('', $EXTRA_SYMBOLS_CACHE);
        }

        $charDone = [];
        if (\preg_match_all('/' . self::$REGEX_ASCII . ($replace_extra_symbols ? '|[' . $EXTRA_SYMBOLS_CACHE . ']' : '') . '/u', $str, $matches)) {
            if (!$replace_single_chars_only) {
                if (self::$LANGUAGE_MAX_KEY === null) {
                    self::$LANGUAGE_MAX_KEY = self::getData('ascii_language_max_key');
                }

                $maxKeyLength = self::$LANGUAGE_MAX_KEY[$language] ?? 0;

                if ($maxKeyLength >= 5) {
                    foreach ($matches[0] as $keyTmp => $char) {
                        if (isset($matches[0][$keyTmp + 4])) {
                            $fiveChars = $matches[0][$keyTmp + 0] . $matches[0][$keyTmp + 1] . $matches[0][$keyTmp + 2] . $matches[0][$keyTmp + 3] . $matches[0][$keyTmp + 4];
                        } else {
                            $fiveChars = null;
                        }
                        if (
                            $fiveChars
                            &&
                            !isset($charDone[$fiveChars])
                            &&
                            isset($REPLACE_HELPER_CACHE[$cacheKey][$fiveChars])
                            &&
                            \strpos($str, $fiveChars) !== false
                        ) {
                            // DEBUG
                            //\var_dump($str, $fiveChars, $REPLACE_HELPER_CACHE[$cacheKey][$fiveChars]);

                            $charDone[$fiveChars] = true;
                            $str = \str_replace($fiveChars, $REPLACE_HELPER_CACHE[$cacheKey][$fiveChars], $str);

                            // DEBUG
                            //\var_dump($str, "\n");
                        }
                    }
                }

                if ($maxKeyLength >= 4) {
                    foreach ($matches[0] as $keyTmp => $char) {
                        if (isset($matches[0][$keyTmp + 3])) {
                            $fourChars = $matches[0][$keyTmp + 0] . $matches[0][$keyTmp + 1] . $matches[0][$keyTmp + 2] . $matches[0][$keyTmp + 3];
                        } else {
                            $fourChars = null;
                        }
                        if (
                            $fourChars
                            &&
                            !isset($charDone[$fourChars])
                            &&
                            isset($REPLACE_HELPER_CACHE[$cacheKey][$fourChars])
                            &&
                            \strpos($str, $fourChars) !== false
                        ) {
                            // DEBUG
                            //\var_dump($str, $fourChars, $REPLACE_HELPER_CACHE[$cacheKey][$fourChars]);

                            $charDone[$fourChars] = true;
                            $str = \str_replace($fourChars, $REPLACE_HELPER_CACHE[$cacheKey][$fourChars], $str);

                            // DEBUG
                            //\var_dump($str, "\n");
                        }
                    }
                }

                foreach ($matches[0] as $keyTmp => $char) {
                    if (isset($matches[0][$keyTmp + 2])) {
                        $threeChars = $matches[0][$keyTmp + 0] . $matches[0][$keyTmp + 1] . $matches[0][$keyTmp + 2];
                    } else {
                        $threeChars = null;
                    }
                    if (
                        $threeChars
                        &&
                        !isset($charDone[$threeChars])
                        &&
                        isset($REPLACE_HELPER_CACHE[$cacheKey][$threeChars])
                        &&
                        \strpos($str, $threeChars) !== false
                    ) {
                        // DEBUG
                        //\var_dump($str, $threeChars, $REPLACE_HELPER_CACHE[$cacheKey][$threeChars]);

                        $charDone[$threeChars] = true;
                        $str = \str_replace($threeChars, $REPLACE_HELPER_CACHE[$cacheKey][$threeChars], $str);

                        // DEBUG
                        //\var_dump($str, "\n");
                    }
                }

                foreach ($matches[0] as $keyTmp => $char) {
                    if (isset($matches[0][$keyTmp + 1])) {
                        $twoChars = $matches[0][$keyTmp + 0] . $matches[0][$keyTmp + 1];
                    } else {
                        $twoChars = null;
                    }
                    if (
                        $twoChars
                        &&
                        !isset($charDone[$twoChars])
                        &&
                        isset($REPLACE_HELPER_CACHE[$cacheKey][$twoChars])
                        &&
                        \strpos($str, $twoChars) !== false
                    ) {
                        // DEBUG
                        //\var_dump($str, $twoChars, $REPLACE_HELPER_CACHE[$cacheKey][$twoChars]);

                        $charDone[$twoChars] = true;
                        $str = \str_replace($twoChars, $REPLACE_HELPER_CACHE[$cacheKey][$twoChars], $str);

                        // DEBUG
                        //\var_dump($str, "\n");
                    }
                }
            }

            foreach ($matches[0] as $char) {
                if (
                    !isset($charDone[$char])
                    &&
                    isset($REPLACE_HELPER_CACHE[$cacheKey][$char])
                    &&
                    \strpos($str, $char) !== false
                ) {
                    // DEBUG
                    //\var_dump($str, $char, $REPLACE_HELPER_CACHE[$cacheKey][$char]);

                    $charDone[$char] = true;
                    $str = \str_replace($char, $REPLACE_HELPER_CACHE[$cacheKey][$char], $str);

                    // DEBUG
                    //\var_dump($str, "\n");
                }
            }
        }

        /** @psalm-suppress PossiblyNullOperand - we use the prepare* methods here, so we don't get NULL here */
        if (!isset(self::$ASCII_MAPS[$language])) {
            $use_transliterate = true;
        }

        if ($use_transliterate) {
            /** @noinspection ArgumentEqualsDefaultValueInspection */
            $str = self::to_transliterate($str, null, false);
        }

        if ($remove_unsupported_chars) {
            $str = (string) \str_replace(["\n\r", "\n", "\r", "\t"], ' ', $str);
            $str = (string) \preg_replace('/' . self::$REGEX_ASCII . '/', '', $str);
        }

        return $str;
    }

    /**
     * Convert given string to safe filename (and keep string case).
     *
     * EXAMPLE: <code>
     * ASCII::to_filename('שדגשדג.png', true)); // 'shdgshdg.png'
     * </code>
     *
     * @param string $str
     * @param bool   $use_transliterate <p>ASCII::to_transliterate() is used by default - unsafe characters are
     *                                  simply replaced with hyphen otherwise.</p>
     * @param string $fallback_char
     *
     * @psalm-pure
     *
     * @return string
     *                <p>A string that contains only safe characters for a filename.</p>
     */
    public static function to_filename(
        string $str,
        bool $use_transliterate = true,
        string $fallback_char = '-'
    ): string {
        if ($use_transliterate) {
            $str = self::to_transliterate($str, $fallback_char);
        }

        $fallback_char_escaped = \preg_quote($fallback_char, '/');

        $str = (string) \preg_replace(
            [
                '/[^' . $fallback_char_escaped . '.\\-a-zA-Z0-9\\s]/', // 1) remove un-needed chars
                '/[\\s]+/u',                                           // 2) convert spaces to $fallback_char
                '/[' . $fallback_char_escaped . ']+/u',                // 3) remove double $fallback_char's
            ],
            [
                '',
                $fallback_char,
                $fallback_char,
            ],
            $str
        );

        return \trim($str, $fallback_char);
    }

    /**
     * Converts the string into an URL slug. This includes replacing non-ASCII
     * characters with their closest ASCII equivalents, removing remaining
     * non-ASCII and non-alphanumeric characters, and replacing whitespace with
     * $separator. The separator defaults to a single dash, and the string
     * is also converted to lowercase. The language of the source string can
     * also be supplied for language-specific transliteration.
     *
     * @param string                $str
     * @param string                $separator             [optional] <p>The string used to replace whitespace.</p>
     * @param string                $language              [optional] <p>Language of the source string.
     *                                                     (default is 'en') | ASCII::*_LANGUAGE_CODE</p>
     * @param array<string, string> $replacements          [optional] <p>A map of replaceable strings.</p>
     * @param bool                  $replace_extra_symbols [optional]  <p>Add some more replacements e.g. "£" with "
     *                                                     pound ".</p>
     * @param bool                  $use_str_to_lower      [optional] <p>Use "string to lower" for the input.</p>
     * @param bool                  $use_transliterate     [optional]  <p>Use ASCII::to_transliterate() for unknown
     *                                                     chars.</p>
     * @psalm-pure
     *
     * @return string
     *                <p>A string that has been converted to an URL slug.</p>
     */
    public static function to_slugify(
        string $str,
        string $separator = '-',
        string $language = self::ENGLISH_LANGUAGE_CODE,
        array $replacements = [],
        bool $replace_extra_symbols = false,
        bool $use_str_to_lower = true,
        bool $use_transliterate = false
    ): string {
        if ($str === '') {
            return '';
        }

        foreach ($replacements as $from => $to) {
            $str = \str_replace($from, $to, $str);
        }

        $str = self::to_ascii(
            $str,
            $language,
            false,
            $replace_extra_symbols,
            $use_transliterate
        );

        $str = \str_replace('@', $separator, $str);

        $str = (string) \preg_replace(
            '/[^a-zA-Z\\d\\s\\-_' . \preg_quote($separator, '/') . ']/',
            '',
            $str
        );

        if ($use_str_to_lower) {
            $str = \strtolower($str);
        }

        $str = (string) \preg_replace('/^[\'\\s]+|[\'\\s]+$/', '', $str);
        $str = (string) \preg_replace('/\\B([A-Z])/', '-\1', $str);
        $str = (string) \preg_replace('/[\\-_\\s]+/', $separator, $str);

        $l = \strlen($separator);
        if ($l && \strpos($str, $separator) === 0) {
            $str = (string) \substr($str, $l);
        }

        if (\substr($str, -$l) === $separator) {
            $str = (string) \substr($str, 0, \strlen($str) - $l);
        }

        return $str;
    }

    /**
     * Returns an ASCII version of the string. A set of non-ASCII characters are
     * replaced with their closest ASCII counterparts, and the rest are removed
     * unless instructed otherwise.
     *
     * EXAMPLE: <code>
     * ASCII::to_transliterate('déjà σσς iıii'); // 'deja sss iiii'
     * </code>
     *
     * @param string      $str     <p>The input string.</p>
     * @param string|null $unknown [optional] <p>Character use if character unknown. (default is '?')
     *                             But you can also use NULL to keep the unknown chars.</p>
     * @param bool        $strict  [optional] <p>Use "transliterator_transliterate()" from PHP-Intl
     *
     * @psalm-pure
     *
     * @return string
     *                <p>A String that contains only ASCII characters.</p>
     *
     * @noinspection ParameterDefaultValueIsNotNullInspection
     */
    public static function to_transliterate(
        string $str,
        $unknown = '?',
        bool $strict = false
    ): string {
        /**
         * @var array<int,string>|null
         */
        static $UTF8_TO_TRANSLIT = null;

        /**
         * null|\Transliterator
         */
        static $TRANSLITERATOR = null;

        /**
         * @var bool|null
         */
        static $SUPPORT_INTL = null;

        if ($str === '') {
            return '';
        }

        if ($SUPPORT_INTL === null) {
            $SUPPORT_INTL = \extension_loaded('intl');
        }

        // check if we only have ASCII, first (better performance)
        $str_tmp = $str;
        if (self::is_ascii($str)) {
            return $str;
        }

        $str = self::clean($str);

        // check again, if we only have ASCII, now ...
        if (
            $str_tmp !== $str
            &&
            self::is_ascii($str)
        ) {
            return $str;
        }

        if (
            $strict
            &&
            $SUPPORT_INTL === true
        ) {
            if (!isset($TRANSLITERATOR)) {
                // INFO: see "*-Latin" rules via "transliterator_list_ids()"
                /**
                 * @var \Transliterator
                 */
                $TRANSLITERATOR = \transliterator_create('NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII;');
            }

            // INFO: https://unicode.org/cldr/utility/character.jsp
            $str_tmp = \transliterator_transliterate($TRANSLITERATOR, $str);

            if ($str_tmp !== false) {

                // check again, if we only have ASCII, now ...
                if (
                    $str_tmp !== $str
                    &&
                    self::is_ascii($str_tmp)
                ) {
                    return $str_tmp;
                }

                $str = $str_tmp;
            }
        }

        if (self::$ORD === null) {
            self::$ORD = self::getData('ascii_ord');
        }

        \preg_match_all('/.|[^\x00]$/us', $str, $array_tmp);
        $chars = $array_tmp[0];
        $ord = null;
        $str_tmp = '';
        foreach ($chars as &$c) {
            $ordC0 = self::$ORD[$c[0]];

            if ($ordC0 >= 0 && $ordC0 <= 127) {
                $str_tmp .= $c;

                continue;
            }

            $ordC1 = self::$ORD[$c[1]];

            // ASCII - next please
            if ($ordC0 >= 192 && $ordC0 <= 223) {
                $ord = ($ordC0 - 192) * 64 + ($ordC1 - 128);
            }

            if ($ordC0 >= 224) {
                $ordC2 = self::$ORD[$c[2]];

                if ($ordC0 <= 239) {
                    $ord = ($ordC0 - 224) * 4096 + ($ordC1 - 128) * 64 + ($ordC2 - 128);
                }

                if ($ordC0 >= 240) {
                    $ordC3 = self::$ORD[$c[3]];

                    if ($ordC0 <= 247) {
                        $ord = ($ordC0 - 240) * 262144 + ($ordC1 - 128) * 4096 + ($ordC2 - 128) * 64 + ($ordC3 - 128);
                    }

                    // We only process valid UTF-8 chars (<= 4 byte), so we don't need this code here ...
                    /*
                    if ($ordC0 >= 248) {
                        $ordC4 = self::$ORD[$c[4]];

                        if ($ordC0 <= 251) {
                            $ord = ($ordC0 - 248) * 16777216 + ($ordC1 - 128) * 262144 + ($ordC2 - 128) * 4096 + ($ordC3 - 128) * 64 + ($ordC4 - 128);
                        }

                        if ($ordC0 >= 252) {
                            $ordC5 = self::$ORD[$c[5]];

                            if ($ordC0 <= 253) {
                                $ord = ($ordC0 - 252) * 1073741824 + ($ordC1 - 128) * 16777216 + ($ordC2 - 128) * 262144 + ($ordC3 - 128) * 4096 + ($ordC4 - 128) * 64 + ($ordC5 - 128);
                            }
                        }
                    }
                     */
                }
            }

            if (
                $ordC0 === 254
                ||
                $ordC0 === 255
                ||
                $ord === null
            ) {
                $str_tmp .= $unknown ?? $c;

                continue;
            }

            $bank = $ord >> 8;
            if (!isset($UTF8_TO_TRANSLIT[$bank])) {
                $UTF8_TO_TRANSLIT[$bank] = self::getDataIfExists(\sprintf('x%03x', $bank));
            }

            $new_char = $ord & 255;

            if (isset($UTF8_TO_TRANSLIT[$bank][$new_char])) {

                // keep for debugging
                /*
                echo "file: " . sprintf('x%02x', $bank) . "\n";
                echo "char: " . $c . "\n";
                echo "ord: " . $ord . "\n";
                echo "new_char: " . $new_char . "\n";
                echo "new_char: " . mb_chr($new_char) . "\n";
                echo "ascii: " . $UTF8_TO_TRANSLIT[$bank][$new_char] . "\n";
                echo "bank:" . $bank . "\n\n";
                 */

                $new_char = $UTF8_TO_TRANSLIT[$bank][$new_char];

                /** @noinspection MissingOrEmptyGroupStatementInspection */
                /** @noinspection PhpStatementHasEmptyBodyInspection */
                if ($unknown === null && $new_char === '') {
                    // nothing
                } elseif (
                    $new_char === '[?]'
                    ||
                    $new_char === '[?] '
                ) {
                    $c = $unknown ?? $c;
                } else {
                    $c = $new_char;
                }
            } else {

                // keep for debugging missing chars
                /*
                echo "file: " . sprintf('x%02x', $bank) . "\n";
                echo "char: " . $c . "\n";
                echo "ord: " . $ord . "\n";
                echo "new_char: " . $new_char . "\n";
                echo "new_char: " . mb_chr($new_char) . "\n";
                echo "bank:" . $bank . "\n\n";
                 */

                $c = $unknown ?? $c;
            }

            $str_tmp .= $c;
        }

        return $str_tmp;
    }

    /**
     * Get the language from a string.
     *
     * e.g.: de_at -> de_at
     *       de_DE -> de
     *       DE_DE -> de
     *       de-de -> de
     *
     * @noinspection ReturnTypeCanBeDeclaredInspection
     *
     * @param string $language
     *
     * @psalm-pure
     *
     * @return string
     */
    private static function get_language(string $language)
    {
        if ($language === '') {
            return '';
        }

        if (
            \strpos($language, '_') === false
            &&
            \strpos($language, '-') === false
        ) {
            return \strtolower($language);
        }

        $language = \str_replace('-', '_', \strtolower($language));

        $regex = '/(?<first>[a-z]+)_\g{first}/';

        return (string) \preg_replace($regex, '$1', $language);
    }

    /**
     * Get data from "/data/*.php".
     *
     * @noinspection ReturnTypeCanBeDeclaredInspection
     *
     * @param string $file
     *
     * @psalm-pure
     *
     * @return array<mixed>
     */
    private static function getData(string $file)
    {
        /** @noinspection PhpIncludeInspection */
        /** @noinspection UsingInclusionReturnValueInspection */
        /** @psalm-suppress UnresolvableInclude */
        return include __DIR__ . '/data/' . $file . '.php';
    }

    /**
     * Get data from "/data/*.php".
     *
     * @param string $file
     *
     * @psalm-pure
     *
     * @return array<mixed>
     */
    private static function getDataIfExists(string $file): array
    {
        $file = __DIR__ . '/data/' . $file . '.php';
        /** @psalm-suppress ImpureFunctionCall */
        if (\is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            /** @noinspection UsingInclusionReturnValueInspection */
            /** @psalm-suppress UnresolvableInclude */
            return include $file;
        }

        return [];
    }

    /**
     * @psalm-pure
     *
     * @return void
     */
    private static function prepareAsciiAndExtrasMaps()
    {
        if (self::$ASCII_MAPS_AND_EXTRAS === null) {
            self::prepareAsciiMaps();
            self::prepareAsciiExtras();

            /** @psalm-suppress PossiblyNullArgument - we use the prepare* methods here, so we don't get NULL here */
            self::$ASCII_MAPS_AND_EXTRAS = \array_merge_recursive(
                self::$ASCII_MAPS ?? [],
                self::$ASCII_EXTRAS ?? []
            );
        }
    }

    /**
     * @psalm-pure
     *
     * @return void
     */
    private static function prepareAsciiMaps()
    {
        if (self::$ASCII_MAPS === null) {
            self::$ASCII_MAPS = self::getData('ascii_by_languages');
        }
    }

    /**
     * @psalm-pure
     *
     * @return void
     */
    private static function prepareAsciiExtras()
    {
        if (self::$ASCII_EXTRAS === null) {
            self::$ASCII_EXTRAS = self::getData('ascii_extras_by_languages');
        }
    }
}

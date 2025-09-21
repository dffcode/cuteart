<?php
/**
 * @license BSD-3-Clause
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\Dotenv\Util;

use KadenceWP\KadenceStarterTemplates\GrahamCampbell\ResultType\Error;
use KadenceWP\KadenceStarterTemplates\GrahamCampbell\ResultType\Success;
use KadenceWP\KadenceStarterTemplates\PhpOption\Option;

/**
 * @internal
 */
final class Str
{
    /**
     * This class is a singleton.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Convert a string to UTF-8 from the given encoding.
     *
     * @param string      $input
     * @param string|null $encoding
     *
     * @return \KadenceWP\KadenceStarterTemplates\GrahamCampbell\ResultType\Result<string,string>
     */
    public static function utf8(string $input, ?string $encoding = null)
    {
        if ($encoding !== null && !\in_array($encoding, \mb_list_encodings(), true)) {
            /** @var \KadenceWP\KadenceStarterTemplates\GrahamCampbell\ResultType\Result<string,string> */
            return Error::create(
                \sprintf('Illegal character encoding [%s] specified.', $encoding)
            );
        }
        $converted = $encoding === null ?
            @\mb_convert_encoding($input, 'UTF-8') :
            @\mb_convert_encoding($input, 'UTF-8', $encoding);
        /**
         * this is for support UTF-8 with BOM encoding
         * @see https://en.wikipedia.org/wiki/Byte_order_mark
         * @see https://github.com/vlucas/phpdotenv/issues/500
         */
        if (\substr($converted, 0, 3) == "\xEF\xBB\xBF") {
            $converted = \substr($converted, 3);
        }
        /** @var \KadenceWP\KadenceStarterTemplates\GrahamCampbell\ResultType\Result<string,string> */
        return Success::create($converted);
    }

    /**
     * Search for a given substring of the input.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return \KadenceWP\KadenceStarterTemplates\PhpOption\Option<int>
     */
    public static function pos(string $haystack, string $needle)
    {
        /** @var \KadenceWP\KadenceStarterTemplates\PhpOption\Option<int> */
        return Option::fromValue(\mb_strpos($haystack, $needle, 0, 'UTF-8'), false);
    }

    /**
     * Grab the specified substring of the input.
     *
     * @param string   $input
     * @param int      $start
     * @param int|null $length
     *
     * @return string
     */
    public static function substr(string $input, int $start, ?int $length = null)
    {
        return \mb_substr($input, $start, $length, 'UTF-8');
    }

    /**
     * Compute the length of the given string.
     *
     * @param string $input
     *
     * @return int
     */
    public static function len(string $input)
    {
        return \mb_strlen($input, 'UTF-8');
    }
}

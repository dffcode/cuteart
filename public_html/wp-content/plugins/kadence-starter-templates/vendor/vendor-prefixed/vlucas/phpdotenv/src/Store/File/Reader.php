<?php
/**
 * @license BSD-3-Clause
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\Dotenv\Store\File;

use KadenceWP\KadenceStarterTemplates\Dotenv\Exception\InvalidEncodingException;
use KadenceWP\KadenceStarterTemplates\Dotenv\Util\Str;
use KadenceWP\KadenceStarterTemplates\PhpOption\Option;

/**
 * @internal
 */
final class Reader
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
     * Read the file(s), and return their raw content.
     *
     * We provide the file path as the key, and its content as the value. If
     * short circuit mode is enabled, then the returned array with have length
     * at most one. File paths that couldn't be read are omitted entirely.
     *
     * @param string[]    $filePaths
     * @param bool        $shortCircuit
     * @param string|null $fileEncoding
     *
     * @throws \KadenceWP\KadenceStarterTemplates\Dotenv\Exception\InvalidEncodingException
     *
     * @return array<string,string>
     */
    public static function read(array $filePaths, bool $shortCircuit = true, ?string $fileEncoding = null)
    {
        $output = [];

        foreach ($filePaths as $filePath) {
            $content = self::readFromFile($filePath, $fileEncoding);
            if ($content->isDefined()) {
                $output[$filePath] = $content->get();
                if ($shortCircuit) {
                    break;
                }
            }
        }

        return $output;
    }

    /**
     * Read the given file.
     *
     * @param string      $path
     * @param string|null $encoding
     *
     * @throws \KadenceWP\KadenceStarterTemplates\Dotenv\Exception\InvalidEncodingException
     *
     * @return \KadenceWP\KadenceStarterTemplates\PhpOption\Option<string>
     */
    private static function readFromFile(string $path, ?string $encoding = null)
    {
        /** @var Option<string> */
        $content = Option::fromValue(@\file_get_contents($path), false);

        return $content->flatMap(static function (string $content) use ($encoding) {
            return Str::utf8($content, $encoding)->mapError(static function (string $error) {
                throw new InvalidEncodingException($error);
            })->success();
        });
    }
}

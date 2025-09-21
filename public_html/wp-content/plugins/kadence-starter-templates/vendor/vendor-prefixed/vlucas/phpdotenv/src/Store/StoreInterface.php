<?php
/**
 * @license BSD-3-Clause
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\Dotenv\Store;

interface StoreInterface
{
    /**
     * Read the content of the environment file(s).
     *
     * @throws \KadenceWP\KadenceStarterTemplates\Dotenv\Exception\InvalidEncodingException|\KadenceWP\KadenceStarterTemplates\Dotenv\Exception\InvalidPathException
     *
     * @return string
     */
    public function read();
}

<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace KadenceWP\KadenceStarterTemplates\Symfony\Component\HttpClient\Response;

use KadenceWP\KadenceStarterTemplates\Symfony\Contracts\HttpClient\ChunkInterface;
use KadenceWP\KadenceStarterTemplates\Symfony\Contracts\HttpClient\ResponseInterface;
use KadenceWP\KadenceStarterTemplates\Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
final class ResponseStream implements ResponseStreamInterface
{
    private $generator;

    public function __construct(\Generator $generator)
    {
        $this->generator = $generator;
    }

    public function key(): ResponseInterface
    {
        return $this->generator->key();
    }

    public function current(): ChunkInterface
    {
        return $this->generator->current();
    }

    public function next(): void
    {
        $this->generator->next();
    }

    public function rewind(): void
    {
        $this->generator->rewind();
    }

    public function valid(): bool
    {
        return $this->generator->valid();
    }
}

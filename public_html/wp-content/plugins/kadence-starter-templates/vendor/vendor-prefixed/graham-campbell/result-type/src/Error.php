<?php
/**
 * @license MIT
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

/*
 * This file is part of Result Type.
 *
 * (c) Graham Campbell <hello@gjcampbell.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KadenceWP\KadenceStarterTemplates\GrahamCampbell\ResultType;

use KadenceWP\KadenceStarterTemplates\PhpOption\None;
use KadenceWP\KadenceStarterTemplates\PhpOption\Some;

/**
 * @template T
 * @template E
 *
 * @extends \KadenceWP\KadenceStarterTemplates\GrahamCampbell\ResultType\Result<T,E>
 */
final class Error extends Result
{
    /**
     * @var E
     */
    private $value;

    /**
     * Internal constructor for an error value.
     *
     * @param E $value
     *
     * @return void
     */
    private function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Create a new error value.
     *
     * @template F
     *
     * @param F $value
     *
     * @return \KadenceWP\KadenceStarterTemplates\GrahamCampbell\ResultType\Result<T,F>
     */
    public static function create($value)
    {
        return new self($value);
    }

    /**
     * Get the success option value.
     *
     * @return \KadenceWP\KadenceStarterTemplates\PhpOption\Option<T>
     */
    public function success()
    {
        return None::create();
    }

    /**
     * Map over the success value.
     *
     * @template S
     *
     * @param callable(T):S $f
     *
     * @return \KadenceWP\KadenceStarterTemplates\GrahamCampbell\ResultType\Result<S,E>
     */
    public function map(callable $f)
    {
        return self::create($this->value);
    }

    /**
     * Flat map over the success value.
     *
     * @template S
     * @template F
     *
     * @param callable(T):\KadenceWP\KadenceStarterTemplates\GrahamCampbell\ResultType\Result<S,F> $f
     *
     * @return \KadenceWP\KadenceStarterTemplates\GrahamCampbell\ResultType\Result<S,F>
     */
    public function flatMap(callable $f)
    {
        /** @var \KadenceWP\KadenceStarterTemplates\GrahamCampbell\ResultType\Result<S,F> */
        return self::create($this->value);
    }

    /**
     * Get the error option value.
     *
     * @return \KadenceWP\KadenceStarterTemplates\PhpOption\Option<E>
     */
    public function error()
    {
        return Some::create($this->value);
    }

    /**
     * Map over the error value.
     *
     * @template F
     *
     * @param callable(E):F $f
     *
     * @return \KadenceWP\KadenceStarterTemplates\GrahamCampbell\ResultType\Result<T,F>
     */
    public function mapError(callable $f)
    {
        return self::create($f($this->value));
    }
}

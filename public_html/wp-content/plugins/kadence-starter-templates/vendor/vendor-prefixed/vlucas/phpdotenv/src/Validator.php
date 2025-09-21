<?php
/**
 * @license BSD-3-Clause
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\Dotenv;

use KadenceWP\KadenceStarterTemplates\Dotenv\Exception\ValidationException;
use KadenceWP\KadenceStarterTemplates\Dotenv\Repository\RepositoryInterface;
use KadenceWP\KadenceStarterTemplates\Dotenv\Util\Regex;
use KadenceWP\KadenceStarterTemplates\Dotenv\Util\Str;

class Validator
{
    /**
     * The environment repository instance.
     *
     * @var \KadenceWP\KadenceStarterTemplates\Dotenv\Repository\RepositoryInterface
     */
    private $repository;

    /**
     * The variables to validate.
     *
     * @var string[]
     */
    private $variables;

    /**
     * Create a new validator instance.
     *
     * @param \KadenceWP\KadenceStarterTemplates\Dotenv\Repository\RepositoryInterface $repository
     * @param string[]                               $variables
     *
     * @throws \KadenceWP\KadenceStarterTemplates\Dotenv\Exception\ValidationException
     *
     * @return void
     */
    public function __construct(RepositoryInterface $repository, array $variables)
    {
        $this->repository = $repository;
        $this->variables = $variables;
    }

    /**
     * Assert that each variable is present.
     *
     * @throws \KadenceWP\KadenceStarterTemplates\Dotenv\Exception\ValidationException
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Validator
     */
    public function required()
    {
        return $this->assert(
            static function (?string $value) {
                return $value !== null;
            },
            'is missing'
        );
    }

    /**
     * Assert that each variable is not empty.
     *
     * @throws \KadenceWP\KadenceStarterTemplates\Dotenv\Exception\ValidationException
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Validator
     */
    public function notEmpty()
    {
        return $this->assertNullable(
            static function (string $value) {
                return Str::len(\trim($value)) > 0;
            },
            'is empty'
        );
    }

    /**
     * Assert that each specified variable is an integer.
     *
     * @throws \KadenceWP\KadenceStarterTemplates\Dotenv\Exception\ValidationException
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Validator
     */
    public function isInteger()
    {
        return $this->assertNullable(
            static function (string $value) {
                return \ctype_digit($value);
            },
            'is not an integer'
        );
    }

    /**
     * Assert that each specified variable is a boolean.
     *
     * @throws \KadenceWP\KadenceStarterTemplates\Dotenv\Exception\ValidationException
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Validator
     */
    public function isBoolean()
    {
        return $this->assertNullable(
            static function (string $value) {
                if ($value === '') {
                    return false;
                }

                return \filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE) !== null;
            },
            'is not a boolean'
        );
    }

    /**
     * Assert that each variable is amongst the given choices.
     *
     * @param string[] $choices
     *
     * @throws \KadenceWP\KadenceStarterTemplates\Dotenv\Exception\ValidationException
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Validator
     */
    public function allowedValues(array $choices)
    {
        return $this->assertNullable(
            static function (string $value) use ($choices) {
                return \in_array($value, $choices, true);
            },
            \sprintf('is not one of [%s]', \implode(', ', $choices))
        );
    }

    /**
     * Assert that each variable matches the given regular expression.
     *
     * @param string $regex
     *
     * @throws \KadenceWP\KadenceStarterTemplates\Dotenv\Exception\ValidationException
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Validator
     */
    public function allowedRegexValues(string $regex)
    {
        return $this->assertNullable(
            static function (string $value) use ($regex) {
                return Regex::matches($regex, $value)->success()->getOrElse(false);
            },
            \sprintf('does not match "%s"', $regex)
        );
    }

    /**
     * Assert that the callback returns true for each variable.
     *
     * @param callable(?string):bool $callback
     * @param string                 $message
     *
     * @throws \KadenceWP\KadenceStarterTemplates\Dotenv\Exception\ValidationException
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Validator
     */
    public function assert(callable $callback, string $message)
    {
        $failing = [];

        foreach ($this->variables as $variable) {
            if ($callback($this->repository->get($variable)) === false) {
                $failing[] = \sprintf('%s %s', $variable, $message);
            }
        }

        if (\count($failing) > 0) {
            throw new ValidationException(\sprintf(
                'One or more environment variables failed assertions: %s.',
                \implode(', ', $failing)
            ));
        }

        return $this;
    }

    /**
     * Assert that the callback returns true for each variable.
     *
     * Skip checking null variable values.
     *
     * @param callable(string):bool $callback
     * @param string                $message
     *
     * @throws \KadenceWP\KadenceStarterTemplates\Dotenv\Exception\ValidationException
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Validator
     */
    public function assertNullable(callable $callback, string $message)
    {
        return $this->assert(
            static function (?string $value) use ($callback) {
                if ($value === null) {
                    return true;
                }

                return $callback($value);
            },
            $message
        );
    }
}

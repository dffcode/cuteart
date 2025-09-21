<?php
/**
 * @license BSD-3-Clause
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\Dotenv\Loader;

use KadenceWP\KadenceStarterTemplates\Dotenv\Parser\Entry;
use KadenceWP\KadenceStarterTemplates\Dotenv\Parser\Value;
use KadenceWP\KadenceStarterTemplates\Dotenv\Repository\RepositoryInterface;

final class Loader implements LoaderInterface
{
    /**
     * Load the given entries into the repository.
     *
     * We'll substitute any nested variables, and send each variable to the
     * repository, with the effect of actually mutating the environment.
     *
     * @param \KadenceWP\KadenceStarterTemplates\Dotenv\Repository\RepositoryInterface $repository
     * @param \KadenceWP\KadenceStarterTemplates\Dotenv\Parser\Entry[]                 $entries
     *
     * @return array<string,string|null>
     */
    public function load(RepositoryInterface $repository, array $entries)
    {
        return \array_reduce($entries, static function (array $vars, Entry $entry) use ($repository) {
            $name = $entry->getName();

            $value = $entry->getValue()->map(static function (Value $value) use ($repository) {
                return Resolver::resolve($repository, $value);
            });

            if ($value->isDefined()) {
                $inner = $value->get();
                if ($repository->set($name, $inner)) {
                    return \array_merge($vars, [$name => $inner]);
                }
            } else {
                if ($repository->clear($name)) {
                    return \array_merge($vars, [$name => null]);
                }
            }

            return $vars;
        }, []);
    }
}

<?php
/**
 * @license BSD-3-Clause
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace KadenceWP\KadenceStarterTemplates\Dotenv;

use KadenceWP\KadenceStarterTemplates\Dotenv\Exception\InvalidPathException;
use KadenceWP\KadenceStarterTemplates\Dotenv\Loader\Loader;
use KadenceWP\KadenceStarterTemplates\Dotenv\Loader\LoaderInterface;
use KadenceWP\KadenceStarterTemplates\Dotenv\Parser\Parser;
use KadenceWP\KadenceStarterTemplates\Dotenv\Parser\ParserInterface;
use KadenceWP\KadenceStarterTemplates\Dotenv\Repository\Adapter\ArrayAdapter;
use KadenceWP\KadenceStarterTemplates\Dotenv\Repository\Adapter\PutenvAdapter;
use KadenceWP\KadenceStarterTemplates\Dotenv\Repository\RepositoryBuilder;
use KadenceWP\KadenceStarterTemplates\Dotenv\Repository\RepositoryInterface;
use KadenceWP\KadenceStarterTemplates\Dotenv\Store\StoreBuilder;
use KadenceWP\KadenceStarterTemplates\Dotenv\Store\StoreInterface;
use KadenceWP\KadenceStarterTemplates\Dotenv\Store\StringStore;

class Dotenv
{
    /**
     * The store instance.
     *
     * @var \KadenceWP\KadenceStarterTemplates\Dotenv\Store\StoreInterface
     */
    private $store;

    /**
     * The parser instance.
     *
     * @var \KadenceWP\KadenceStarterTemplates\Dotenv\Parser\ParserInterface
     */
    private $parser;

    /**
     * The loader instance.
     *
     * @var \KadenceWP\KadenceStarterTemplates\Dotenv\Loader\LoaderInterface
     */
    private $loader;

    /**
     * The repository instance.
     *
     * @var \KadenceWP\KadenceStarterTemplates\Dotenv\Repository\RepositoryInterface
     */
    private $repository;

    /**
     * Create a new dotenv instance.
     *
     * @param \KadenceWP\KadenceStarterTemplates\Dotenv\Store\StoreInterface           $store
     * @param \KadenceWP\KadenceStarterTemplates\Dotenv\Parser\ParserInterface         $parser
     * @param \KadenceWP\KadenceStarterTemplates\Dotenv\Loader\LoaderInterface         $loader
     * @param \KadenceWP\KadenceStarterTemplates\Dotenv\Repository\RepositoryInterface $repository
     *
     * @return void
     */
    public function __construct(
        StoreInterface $store,
        ParserInterface $parser,
        LoaderInterface $loader,
        RepositoryInterface $repository
    ) {
        $this->store = $store;
        $this->parser = $parser;
        $this->loader = $loader;
        $this->repository = $repository;
    }

    /**
     * Create a new dotenv instance.
     *
     * @param \KadenceWP\KadenceStarterTemplates\Dotenv\Repository\RepositoryInterface $repository
     * @param string|string[]                        $paths
     * @param string|string[]|null                   $names
     * @param bool                                   $shortCircuit
     * @param string|null                            $fileEncoding
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Dotenv
     */
    public static function create(RepositoryInterface $repository, $paths, $names = null, bool $shortCircuit = true, ?string $fileEncoding = null)
    {
        $builder = $names === null ? StoreBuilder::createWithDefaultName() : StoreBuilder::createWithNoNames();

        foreach ((array) $paths as $path) {
            $builder = $builder->addPath($path);
        }

        foreach ((array) $names as $name) {
            $builder = $builder->addName($name);
        }

        if ($shortCircuit) {
            $builder = $builder->shortCircuit();
        }

        return new self($builder->fileEncoding($fileEncoding)->make(), new Parser(), new Loader(), $repository);
    }

    /**
     * Create a new mutable dotenv instance with default repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Dotenv
     */
    public static function createMutable($paths, $names = null, bool $shortCircuit = true, ?string $fileEncoding = null)
    {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->make();

        return self::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Create a new mutable dotenv instance with default repository with the putenv adapter.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Dotenv
     */
    public static function createUnsafeMutable($paths, $names = null, bool $shortCircuit = true, ?string $fileEncoding = null)
    {
        $repository = RepositoryBuilder::createWithDefaultAdapters()
            ->addAdapter(PutenvAdapter::class)
            ->make();

        return self::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Create a new immutable dotenv instance with default repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Dotenv
     */
    public static function createImmutable($paths, $names = null, bool $shortCircuit = true, ?string $fileEncoding = null)
    {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->immutable()->make();

        return self::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Create a new immutable dotenv instance with default repository with the putenv adapter.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Dotenv
     */
    public static function createUnsafeImmutable($paths, $names = null, bool $shortCircuit = true, ?string $fileEncoding = null)
    {
        $repository = RepositoryBuilder::createWithDefaultAdapters()
            ->addAdapter(PutenvAdapter::class)
            ->immutable()
            ->make();

        return self::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Create a new dotenv instance with an array backed repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Dotenv
     */
    public static function createArrayBacked($paths, $names = null, bool $shortCircuit = true, ?string $fileEncoding = null)
    {
        $repository = RepositoryBuilder::createWithNoAdapters()->addAdapter(ArrayAdapter::class)->make();

        return self::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Parse the given content and resolve nested variables.
     *
     * This method behaves just like load(), only without mutating your actual
     * environment. We do this by using an array backed repository.
     *
     * @param string $content
     *
     * @throws \KadenceWP\KadenceStarterTemplates\Dotenv\Exception\InvalidFileException
     *
     * @return array<string,string|null>
     */
    public static function parse(string $content)
    {
        $repository = RepositoryBuilder::createWithNoAdapters()->addAdapter(ArrayAdapter::class)->make();

        $phpdotenv = new self(new StringStore($content), new Parser(), new Loader(), $repository);

        return $phpdotenv->load();
    }

    /**
     * Read and load environment file(s).
     *
     * @throws \KadenceWP\KadenceStarterTemplates\Dotenv\Exception\InvalidPathException|\KadenceWP\KadenceStarterTemplates\Dotenv\Exception\InvalidEncodingException|\KadenceWP\KadenceStarterTemplates\Dotenv\Exception\InvalidFileException
     *
     * @return array<string,string|null>
     */
    public function load()
    {
        $entries = $this->parser->parse($this->store->read());

        return $this->loader->load($this->repository, $entries);
    }

    /**
     * Read and load environment file(s), silently failing if no files can be read.
     *
     * @throws \KadenceWP\KadenceStarterTemplates\Dotenv\Exception\InvalidEncodingException|\KadenceWP\KadenceStarterTemplates\Dotenv\Exception\InvalidFileException
     *
     * @return array<string,string|null>
     */
    public function safeLoad()
    {
        try {
            return $this->load();
        } catch (InvalidPathException $e) {
            // suppressing exception
            return [];
        }
    }

    /**
     * Required ensures that the specified variables exist, and returns a new validator object.
     *
     * @param string|string[] $variables
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Validator
     */
    public function required($variables)
    {
        return (new Validator($this->repository, (array) $variables))->required();
    }

    /**
     * Returns a new validator object that won't check if the specified variables exist.
     *
     * @param string|string[] $variables
     *
     * @return \KadenceWP\KadenceStarterTemplates\Dotenv\Validator
     */
    public function ifPresent($variables)
    {
        return new Validator($this->repository, (array) $variables);
    }
}

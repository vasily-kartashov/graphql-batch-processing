<?php

namespace BatchProcessor;

use GraphQL\Deferred;
use RuntimeException;

abstract class FetchContext extends Batch
{
    /** @var Batch */
    protected $batch;

    /** @var mixed */
    protected $key;

    /** @var mixed */
    protected $context;

    /** @var bool */
    protected $defaultValueSet = false;

    /** @var mixed */
    protected $defaultValue;

    /**
     * @var null|callable
     * @psalm-var null|callable(mixed):bool
     */
    protected $filter;

    /**
     * @var null|callable
     * @psalm-var null|callable(mixed,mixed,array):mixed
     */
    protected $formatter;

    /**
     * @param Batch $batch
     * @param mixed $key
     * @param mixed $context
     */
    public function __construct(Batch $batch, $key, $context)
    {
        parent::__construct();
        $this->batch = $batch;
        $this->key = $key;
        $this->context = $context;
    }

    /**
     * @param mixed $defaultValue
     * @return FetchContext
     */
    public function defaultTo($defaultValue): FetchContext
    {
        if ($this->defaultValueSet) {
            throw new RuntimeException('Default value already set');
        }
        $this->defaultValue = $defaultValue;
        $this->defaultValueSet = true;
        return $this;
    }

    /**
     * @param callable $filter
     * @psalm-param @psalm-var @callable(mixed) : bool $filter
     * @return FetchContext
     */
    public function filter(callable $filter): FetchContext
    {
        if ($this->filter !== null) {
            throw new RuntimeException('Filter already set');
        }
        $this->filter = $filter;
        return $this;
    }

    /**
     * @param callable $formatter
     * @psalm-param callable(mixed,mixed,array):mixed $formatter
     * @return FetchContext
     */
    public function format(callable $formatter): FetchContext
    {
        if ($this->formatter !== null) {
            throw new RuntimeException('Formatter already set');
        }
        $this->formatter = $formatter;
        return $this;
    }

    abstract public function fetchOneToOne(callable $callable): Deferred;

    abstract public function fetchOneToMany(callable $callable): Deferred;
}

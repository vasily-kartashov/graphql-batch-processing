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

    /** @var callable|null */
    protected $formatter;

    /**
     * @param Batch $batch
     * @param mixed $key
     * @param mixed $context
     */
    public function __construct(Batch $batch, $key, $context)
    {
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

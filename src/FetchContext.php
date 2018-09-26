<?php

namespace BatchProcessor;

use GraphQL\Deferred;
use Psr\Log\LoggerInterface;
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

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param Batch $batch
     * @param mixed $key
     * @param mixed $context
     * @param LoggerInterface $logger
     */
    public function __construct(Batch $batch, $key, $context, LoggerInterface $logger)
    {
        parent::__construct($batch->name);

        $this->batch   = $batch;
        $this->key     = $key;
        $this->context = $context;
        $this->logger  = $logger;
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
     * @psalm-param callable(mixed):bool $filter
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

    /**
     * @param callable $callable
     * @return void
     */
    protected function processUnresolvedKeys(callable $callable)
    {
        list($keys, $context) = $this->batch->unresolvedKeysWithContext();
        if (!empty($keys)) {
            $this->logger->debug('Batch {name} processing keys [{keys}]', [
                'name' => $this->name,
                'keys' => join(', ', $keys)
            ]);
            $result = ($callable)($keys, $context);
            if (empty($result)) {
                $this->logger->debug('Batch {name} empty result set', [
                    'name' => $this->name
                ]);
            } else {
                $this->logger->debug('Batch {name} fetch returned keys [{keys}]', [
                    'name' => $this->name,
                    'keys' => join(', ', array_keys($result))
                ]);
            }
            $this->batch->update($result);
        }
    }
}

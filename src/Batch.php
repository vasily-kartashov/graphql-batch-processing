<?php

namespace BatchProcessor;

use GraphQL\Deferred;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Batch
{
    /** @var Batch[] */
    private static $batches = [];

    /** @var string */
    protected $name;

    /** @var array */
    protected $unresolvedKeys = [];

    /** @var array */
    protected $resolvedReferences = [];

    /** @var mixed */
    protected $context = [];

    /** @var LoggerInterface */
    protected $logger;

    protected function __construct(string $name)
    {
        $this->name = $name;
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): Batch
    {
        $this->logger = $logger;
        return $this;
    }

    public static function as(string $batchName): Batch
    {
        if (!isset(static::$batches[$batchName])) {
            static::$batches[$batchName] = new Batch($batchName);
        }
        return static::$batches[$batchName];
    }

    /**
     * @param array $references
     * @param array $keys
     * @return void
     */
    protected function update(array $references, array $keys)
    {
        foreach ($references as $key => $reference) {
            $this->resolvedReferences[$key] = $reference;
        }
        foreach ($keys as $key) {
            unset($this->unresolvedKeys[$key]);
        }
    }

    protected function unresolvedKeysWithContext(): array
    {
        $keys = [];
        $context = [];
        foreach ($this->unresolvedKeys as $key) {
            $keys[] = $key;
            $context[$key] = $this->context[$key] ?? $key;
        }
        return [$keys, $context];
    }

    /**
     * @param mixed $key
     * @param mixed $context
     * @return FetchContext
     */
    public function collectOne($key, $context = null): FetchContext
    {
        if (!array_key_exists($key, $this->resolvedReferences)) {
            $this->unresolvedKeys[$key] = $key;
            $this->context[$key] = $context;
        }

        return new class($this, $key, $context, $this->logger) extends FetchContext
        {
            /**
             * @param callable $callable
             * @psalm-param callable(array,array): array $callable
             * @return Deferred
             */
            public function fetchOneToOne(callable $callable): Deferred
            {
                if (!$this->defaultValueSet) {
                    $this->defaultTo(null);
                }
                return new Deferred(function () use ($callable) {
                    $this->processUnresolvedKeys($callable);
                    $resolvedReferences = $this->batch->resolvedReferences;
                    $resolvedReference = $this->defaultValue;
                    if (array_key_exists($this->key, $resolvedReferences)) {
                        if ($this->filter === null || ($this->filter)($resolvedReferences[$this->key])) {
                            $resolvedReference = $resolvedReferences[$this->key];
                        }
                    }
                    $context = $this->context ?? $this->key;
                    if ($this->formatter === null) {
                        return $resolvedReference;
                    } else {
                        return ($this->formatter)($resolvedReference, $context, $resolvedReferences);
                    }
                });
            }

            /**
             * @param callable $callable
             * @psalm-param callable(array,array):array $callable
             * @return Deferred
             */
            public function fetchOneToMany(callable $callable): Deferred
            {
                if (!$this->defaultValueSet) {
                    $this->defaultTo([]);
                }
                return new Deferred(function () use ($callable) {
                    $this->processUnresolvedKeys($callable);
                    $resolvedReferences = $this->batch->resolvedReferences;
                    $resolvedReference = array_key_exists($this->key, $resolvedReferences)
                        ? $resolvedReferences[$this->key]
                        : $this->defaultValue;
                    $context = $this->context ?? $this->key;

                    $result = [];
                    foreach ($resolvedReference as $key => $referencedObject) {
                        if ($this->filter && !($this->filter)($referencedObject)) {
                            continue;
                        }
                        if ($this->formatter === null) {
                            $result[$key] = $referencedObject;
                        } else {
                            $result[$key] = ($this->formatter)($referencedObject, $context, $resolvedReferences);
                        }
                    }
                    return $result;
                });
            }
        };
    }


    /**
     * @param array $keys
     * @param mixed $context
     * @return FetchContext
     */
    public function collectMultiple(array $keys, $context = null): FetchContext
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->resolvedReferences)) {
                $this->unresolvedKeys[$key] = $key;
                $this->context[$key] = $context;
            }
        }

        return new class($this, $keys, $context, $this->logger) extends FetchContext
        {
            /**
             * @param callable $callable
             * @psalm-param callable(array,array|null):array $callable
             * @return Deferred
             */
            public function fetchOneToOne(callable $callable): Deferred
            {
                if (!$this->defaultValueSet) {
                    $this->defaultTo(null);
                }
                return new Deferred(function () use ($callable) {
                    $this->processUnresolvedKeys($callable);
                    $resolvedReferences = $this->batch->resolvedReferences;

                    $result = [];
                    foreach ($this->key as $key) {
                        $referencedObject = $this->defaultValue;
                        if (array_key_exists($key, $resolvedReferences)) {
                            if ($this->filter === null || ($this->filter)($resolvedReferences[$key])) {
                                $referencedObject = $resolvedReferences[$key];
                            }
                        }
                        $context = $this->context ?? $key;
                        if ($this->formatter === null) {
                            $result[$key] = $referencedObject;
                        } else {
                            $result[$key] = ($this->formatter)($referencedObject, $context, $resolvedReferences);
                        }
                    }
                    return $result;
                });
            }

            /**
             * @param callable $callable
             * @psalm-param callable(array,array|null):array $callable
             * @return Deferred
             */
            public function fetchOneToMany(callable $callable): Deferred
            {
                if (!$this->defaultValueSet) {
                    $this->defaultTo([]);
                }
                return new Deferred(function () use ($callable) {
                    $this->processUnresolvedKeys($callable);
                    $resolvedReferences = $this->batch->resolvedReferences;

                    $result = [];
                    foreach ($this->key as $key) {
                        $resolvedReference = array_key_exists($key, $resolvedReferences)
                            ? $resolvedReferences[$key]
                            : $this->defaultValue;
                        $context = $this->context ?? $key;
                        $result[$key] = [];
                        foreach ($resolvedReference as $referencedObjectKey => $referencedObject) {
                            if ($this->filter && !($this->filter)($referencedObject)) {
                                continue;
                            }
                            if ($this->formatter === null) {
                                $result[$key][$referencedObjectKey] = $referencedObject;
                            } else {
                                $result[$key][$referencedObjectKey] = ($this->formatter)($referencedObject, $context, $resolvedReferences);
                            }
                        }
                    }
                    return $result;
                });
            }
        };
    }
}

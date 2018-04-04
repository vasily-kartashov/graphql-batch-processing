<?php

namespace BatchProcessor;

use GraphQL\Deferred;

class Batch
{
    /** @var Batch[] */
    private static $batches = [];

    /** @var array */
    protected $unresolvedKeys = [];

    /** @var array */
    protected $resolvedReferences = [];

    /** @var array */
    protected $context = [];

    protected function __construct()
    {
    }

    public static function as(string $batchName): Batch
    {
        if (!isset(static::$batches[$batchName])) {
            static::$batches[$batchName] = new Batch();
        }
        return static::$batches[$batchName];
    }

    /**
     * @param array $references
     * @return void
     */
    protected function update(array $references)
    {
        foreach ($references as $key => $reference) {
            $this->resolvedReferences[$key] = $reference;
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

        return new class($this, $key, $context) extends FetchContext
        {
            /**
             * @param callable $callable
             * @psalm-param callable(array, array) : array $callable
             * @return Deferred
             */
            public function fetchOneToOne(callable $callable): Deferred
            {
                if ($this->formatter === null) {
                    $this->format(function ($referencedObject) {
                        return $referencedObject;
                    });
                }
                if (!$this->defaultValueSet) {
                    $this->defaultTo(null);
                }
                return new Deferred(function () use ($callable) {
                    list($keys, $context) = $this->batch->unresolvedKeysWithContext();
                    if (!empty($keys)) {
                        $this->batch->update(($callable)($keys, $context));
                    }
                    $resolvedReferences = $this->batch->resolvedReferences;
                    $resolvedReference = $this->defaultValue;
                    if (array_key_exists($this->key, $resolvedReferences)) {
                        if ($this->filter === null || ($this->filter)($resolvedReferences[$this->key])) {
                            $resolvedReference = $resolvedReferences[$this->key];
                        }
                    }
                    $context = $this->context ?? $this->key;

                    /** @psalm-suppress PossiblyNullFunctionCall */
                    return ($this->formatter)($resolvedReference, $context, $resolvedReferences);
                });
            }

            /**
             * @param callable $callable
             * @psalm-param callable(array, array) : array $callable
             * @return Deferred
             */
            public function fetchOneToMany(callable $callable): Deferred
            {
                if ($this->formatter === null) {
                    $this->format(function ($referencedObject) {
                        return $referencedObject;
                    });
                }
                if (!$this->defaultValueSet) {
                    $this->defaultTo([]);
                }
                return new Deferred(function () use ($callable) {
                    list($keys, $context) = $this->batch->unresolvedKeysWithContext();
                    if (!empty($keys)) {
                        $this->batch->update(($callable)($keys, $context));
                    }
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
                        /** @psalm-suppress PossiblyNullFunctionCall */
                        $result[$key] = ($this->formatter)($referencedObject, $context, $resolvedReferences);
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

        return new class($this, $keys, $context) extends FetchContext
        {
            /**
             * @param callable $callable
             * @psalm-param callable(array,array|null):array $callable
             * @return Deferred
             */
            public function fetchOneToOne(callable $callable): Deferred
            {
                if ($this->formatter === null) {
                    $this->format(function ($referencedObject) {
                        return $referencedObject;
                    });
                }
                if (!$this->defaultValueSet) {
                    $this->defaultTo(null);
                }

                return new Deferred(function () use ($callable) {
                    list($keys, $context) = $this->batch->unresolvedKeysWithContext();
                    if (!empty($keys)) {
                        $this->batch->update(($callable)($keys, $context));
                    }
                    $resolvedReferences = $this->batch->resolvedReferences;

                    $result = [];
                    foreach ($this->key as $key) {
                        $referencedObject = $this->defaultValue;
                        if (array_key_exists($key, $resolvedReferences)) {
                            if ($this->filter === null || ($this->filter)($resolvedReferences[$key])) {
                                $referencedObject = $resolvedReferences[$key];
                            }
                        }
                        $context = $fetchContext->context ?? $key;
                        /** @psalm-suppress PossiblyNullFunctionCall */
                        $result[$key] = ($this->formatter)($referencedObject, $context, $resolvedReferences);
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
                if ($this->formatter === null) {
                    $this->format(function ($referencedObject) {
                        return $referencedObject;
                    });
                }
                if (!$this->defaultValueSet) {
                    $this->defaultTo([]);
                }

                return new Deferred(function () use ($callable) {
                    list($keys, $context) = $this->batch->unresolvedKeysWithContext();
                    if (!empty($keys)) {
                        $this->batch->update(($callable)($keys, $context));
                    }
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
                            /** @psalm-suppress PossiblyNullFunctionCall */
                            $result[$key][$referencedObjectKey] = ($this->formatter)($referencedObject, $context, $resolvedReferences);
                        }
                    }
                    return $result;
                });
            }
        };
    }
}

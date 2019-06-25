<?php

namespace BatchProcessor;

use GraphQL\Deferred;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class BatchTest extends TestCase
{
    public function testSimpleFetch()
    {
        $deferred = Batch::as(__METHOD__)
            ->collectOne('Ivan')
            ->fetchOneToOne(function () {
                return [
                    'Ivan' => 'Petrov'
                ];
            });

        Deferred::runQueue();
        Assert::assertEquals('Petrov', $deferred->promise->result);
    }

    public function testDefaultValue()
    {
        $deferred = Batch::as(__METHOD__)
            ->collectOne(1)
            ->defaultTo('value')
            ->fetchOneToOne(function () {
                return [];
            });

        Deferred::runQueue();
        Assert::assertEquals('value', $deferred->promise->result);
    }

    public function testZipWithArray()
    {
        $deferred = Batch::as(__METHOD__)
            ->collectOne(1, 'x')
            ->format(function ($referencedObject, $context) {
                return $context . $referencedObject;
            })
            ->fetchOneToMany(function () {
                return [
                    1 => ['a', 'b', 'c']
                ];
            });

        Deferred::runQueue();
        Assert::assertEquals(['xa', 'xb', 'xc'], $deferred->promise->result);
    }

    public function testZipWithScalar()
    {
        $deferred = Batch::as(__METHOD__)
            ->collectOne(1, 'context-')
            ->format(function (string $referencedObject, string $context) {
                return $context . $referencedObject;
            })
            ->fetchOneToOne(function () {
                return [
                    1 => 'value'
                ];
            });

        Deferred::runQueue();
        Assert::assertEquals('context-value', $deferred->promise->result);
    }

    public function testZipWithArrayFromMultipleReferences()
    {
        $deferred = Batch::as(__METHOD__)
            ->collectMultiple([1, 2])
            ->format(function ($referencedObject, $context) {
                return $context . '-' . $referencedObject;
            })
            ->fetchOneToMany(function () {
                return [
                    1 => ['a', 'b', 'c'],
                    2 => ['x', 'y', 'z'],
                    3 => ['+', '%', '*']
                ];
            });

        Deferred::runQueue();
        Assert::assertEquals([
            1 => ['1-a', '1-b', '1-c'],
            2 => ['2-x', '2-y', '2-z']
        ], $deferred->promise->result);
    }

    public function testCacheing()
    {
        $fetchKeys = [];

        $resolver = function (array $keys) use (&$fetchKeys) {
            $fetchKeys = $keys;
            return [
                'Ivan' => 'Petrov',
                'John' => 'Ivanov'
            ];
        };

        Batch::as(__METHOD__)
            ->collectOne('Ivan')
            ->fetchOneToOne($resolver);

        Batch::as(__METHOD__)
            ->collectMultiple(['Ivan', 'John'])
            ->fetchOneToOne($resolver);

        Batch::as(__METHOD__)
            ->collectOne('John')
            ->fetchOneToOne($resolver);
        Deferred::runQueue();

        Assert::assertEquals(['Ivan', 'John'], $fetchKeys);
    }

    public function testFilterOneToMany()
    {
        $deferred = Batch::as(__METHOD__)
            ->collectOne('Ivan')
            ->filter(function (string $lastName) {
                return $lastName != 'Smirnov';
            })
            ->fetchOneToMany(function () {
                return [
                    'Ivan' => [
                        'Petrov',
                        'Smirnov'
                    ]
                ];
            });

        Deferred::runQueue();
        Assert::assertEquals(['Petrov'], $deferred->promise->result);
    }

    public function testFilterOneToOne()
    {
        $deferred = Batch::as(__METHOD__)
            ->collectOne('Ivan')
            ->defaultTo('Defaultsky')
            ->filter(function (string $lastName) {
                return $lastName != 'Petrov';
            })
            ->fetchOneToOne(function () {
                return [
                    'Ivan' => 'Petrov'
                ];
            });

        Deferred::runQueue();
        Assert::assertEquals('Defaultsky', $deferred->promise->result);
    }

    public function testFailedKeysAreNotRefetched()
    {
        $deferred = [];
        $count = 0;
        for ($i = 0; $i < 5; $i++) {
            $deferred[$i] = Batch::as(__METHOD__)
                ->collectOne(1)
                ->defaultTo($i)
                ->fetchOneToOne(function () use (&$count) {
                    $count++;
                    return [];
                });
        }

        Deferred::runQueue();
        foreach ($deferred as $i => $d) {
            Assert::assertEquals($i, $d->promise->result);
        }
        Assert::assertEquals(1, $count);
    }

    public function testDefaultValuesAreUnique()
    {
        $deferred = [];
        for ($i = 0; $i < 5; $i++) {
            $deferred[$i] = Batch::as(__METHOD__)
                ->collectOne($i)
                ->defaultTo('default-' . $i)
                ->fetchOneToOne(function () use (&$count) {
                    return [];
                });
        }

        Deferred::runQueue();
        foreach ($deferred as $i => $d) {
            Assert::assertEquals('default-' . $i, $d->promise->result);
        }
    }
}

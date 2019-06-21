# GraphQL batch processor

[![Build Status](https://travis-ci.org/vasily-kartashov/graphql-batch-processing.svg)](https://travis-ci.org/vasily-kartashov/graphql-batch-processing)

## Simple Example:

```php
// Name of cache is `addressesByUserId`
return Batch::as('addressesByUserId')
    // In this context collect user's ID
    ->collectOne($user->id())
    // When all user IDs are collected, fetch addresses for all collected user IDs
    // The callback is only executed once for each set of user IDs
    // And cached internally under name `addressesByUserId`
    ->fetchOneToMany(function (array $userIds) { 
        return $this->addressRepository->findAddressesByUserIds($userIds);
    });
```

## More complex example

```php
return Batch::as('accountsByOrgranizationId')
    ->collectMultiple($organization->accountIds())
    ->fetchOneToOne(function (array $accountIds) {
        return $this->accountRepository->findAccountsByAccountIds($accountIds);
    });
```

## Proper example

Get all addresses by users; post filter hidden address; format each address as a string; if no address, default to company address

```php
return Batch::as('addressesByUserId')
    ->collectOne($user->id())
    ->filter(function (Address $address) {
        return !$address->hidden();
    })
    ->format(function (Address $address) {
        return (string) $address;
    })
    ->defaultTo([$company->defaultAddress()])
    ->fetchOneToMany(function (array $userIds) {
        return $this->addressRepository->findAddressesByUserIds($userIds);
    });
```

## Tracing

Batches understand PSR-3 Loggers which can provide you with mode feel of what's going on behind the scene

```php
return Batch::as('usersByUserIds')
    ->setLogger($logger)
    ->collectOne(...)
    ...
```

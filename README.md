# GraphQL batch processor

[![Build Status](https://travis-ci.org/vasily-kartashov/graphql-batch-processing.svg)](https://travis-ci.org/vasily-kartashov/graphql-batch-processing)

## Simple Example:

```php
// Name of the cache is `addressesByUserId`
return Batch::as('addressesByUserId')
    // Collect user IDs
    ->collectOne($user->id())
    // When all user IDs are collected, fetch addresses for all collected user IDs
    // The callback is only executed once for each set of user IDs
    // And cached internally under the name `addressesByUserId`
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

Get all addresses for each user; post filter out hidden addresses; format each address as a string; if there's no address, default to company's address

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

Batches accept PSR-3 Loggers

```php
return Batch::as('usersByUserIds')
    ->setLogger($logger)
    ->collectOne(...)
    ...
```

# GraphQL batch processor

[![Build Status](https://travis-ci.org/vasily-kartashov/graphql-batch-processing.svg)](https://travis-ci.org/vasily-kartashov/graphql-batch-processing)

## Simple Example:

```php
return Batch::as('addressesByUserId')
    ->collectOne($user->id())
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

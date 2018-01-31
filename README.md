GraphQL batch processor
===

[![Build Status](https://travis-ci.org/vasily-kartashov/graphql-batch-processing.svg)](https://travis-ci.org/vasily-kartashov/graphql-batch-processing)

Simple Example:

```php
return Batch::as('addressesByUserId')
    ->collectOne($user->id())
    ->fetchOneToMany(function (array $userIds) {
        return $this->addressRepository->findAddressesByUserIds($userIds);
    });
```

More complex example

```php
<?php

return Batch::as('accountsByOrgranizationId')
    ->collectMultiple($organization->accountIds())
    ->fetchOneToOne(function (array $accountIds) {
        return $this->accountRepository->findAccountsByAccountIds($accountIds);
    });
```

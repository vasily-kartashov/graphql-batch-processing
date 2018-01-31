GraphQL batch processor
===

[![Build Status](https://travis-ci.org/vasily-kartashov/graphql-batch-processing)](https://travis-ci.org/vasily-kartashov/graphql-batch-processing)

Simple Example:

```php
return Batch::as('addressesByUserId')
    ->collectOne($user->id())
    ->fetchOneToMany(function (array $userIds) {
        return $this->addressRepository->findAddressesByUserIds($userIds);
    });
```
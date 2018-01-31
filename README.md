GraphQL batch processor
===

Simple Example:

```php
return Batch::as('addressesByUserId')
    ->collectOne($user->id())
    ->fetchOneToMany(function (array $userIds) {
        return $this->addressRepository->findAddressesByUserIds($userIds);
    });
```
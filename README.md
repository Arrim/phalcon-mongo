# Phalcon Mongo

Расширение для MongoCollection из пакета phalcon/incubator. Добавляет встроенные коллекции и защиту полей при отправке данных клиенту

## Getting Started

Добавляем коллекцию Users

```php
<?php
namespace MyApp\Collection;

use Arrim\Phalcon\Mvc\MongoCollection;

class Users extends MongoCollection
{
    protected $_securedFields = [
        'password',
    ];
    
    protected $_embeddedFields = [
        'profile' => Profile::class    
    ];

    public $password;

    public $email;

    public $login;
    
    public $profile;
}
```

Добавляем встроенную коллекцию Profile для хранения сведений о пользователе

```php
<?php
namespace MyAll\Collection;

use Arrim\Phalcon\Mvc\MongoCollection;

class Profile extends MongoCollection
{
    protected $_embedded = true;
    
    public $firstName;

    public $lastName;
}

```

Для пример создадим пользователя из массива. К элементам массива profile можно обращаться как к объекту.

```php
<?php
namespace MyAll\Controller;

class Controller 
{
   public function action()
   {
       $user = Users::fromArray([
               'login'    => 'test',
               'email'    => 'login',
               'password' => 'password',
               'profile'  => [
                    'firstName' => 'Vasya',
                    'lastName'  => 'Pupkin',   
                ],
           ]);
       
       // Printing "Vasya"
       print $user->profile->firstName;
       
       /**
        *  Print field password
        */
       print json_encode($user->toArray(false));
       
       /**
        *  Not printing field password
        */
       print json_encode($user->toArray(true));
   }
}
   
```

### Installing

Install using Composer:
```
{
    "require": {
        "arrim/phalcon-mongo": "dev-master"
    }
}
```

## Built With

* [Phalcon](https://github.com/phalcon/cphalcon) - Framework
* [Incubator](https://github.com/phalcon/incubator)

## Authors

* **Max Arrim Popov** - *Initial work* - [Arrim](https://github.com/Arrim)
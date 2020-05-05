yii2-users-options
=================
Хранение настроек пользователя на сервере (a-la server-side cookies)

Установка
---------

Предпочтительный вариант установки расширения через [composer](http://getcomposer.org/download/).


Выполните

```
php composer.phar require pozitronik/yii2-users-options "dev-master"
```

или добавьте

```
"pozitronik/yii2-users-options": "dev-master"
```

В секцию require файла `composer.json` в вашем проекте.

Описание
--------

Модель UsersOptions умеет хранить набор произвольных key-value параметров, привязанных к любому объекту (подразумевается, что таким объектом выступает пользователь системы, но, при желании, модель может быть использована и для других объектов).
Данные хранятся в таблице со структурой `user_id|option_name|option_value,` и модель всего лишь предоставляет интерфейсы для удобного доступа к хранилищу.

Использование
-------------

Расширению необходима таблица для хранения данных. Её можно создать, выполнив команду:

`yii migrate --migrationPath=@vendor/pozitronik/yii2-users-options/migrations`

В этом случае будет создана таблица `users_options`, и никакой дополнительной настройки более не потребуется.

При необходимости можно переопределить имя используемой таблицы. Для этого нужно подключить в конфигурационном файле вашего приложения модуль UsersOptionsModule с именем `usersoptions`, и в его конфигурации указать имя используемой таблицы в параметре `tableName`:
```php
'modules' => [
		'usersoptions' => [
			'class' => UsersOptionsModule::class,
			'params' => [
				'tableName' => 'auth_users_options'
		],
		...
]
```

Класс `pozitronik\users_options\models\UsersOptions.php` предоставляет методы:

* `get(string $option, bool $decoded = false)` - возвращает значение параметра `$option`. Подразумевается, что значение хранится в виде массива. Чтобы получить скалярное значение параметра, установите параметр `$decoded=true`.
* `set(string $option, array $value):bool` - присваивает параметру `$option` значение `$value`. Если `$value` содержит скалярный тип данных, то оно должно быть преобразовано в массив. Возвращает успех сохранения параметра.

а также статические методы
* `getStatic(int $user_id, string $option, bool $decoded = false)`
* `setStatic(int $user_id, string $option, array $value):bool`

Аналогичные вызовам `get`/`set`.

В случае, если модель пользователя расширяет класс `ActiveRecord` и имеет целочисленный идентификатор `$id`, то проще всего использовать трейт `pozitronik\users_options\traits\UsersOptionsTrait.php`. В нём описано свойство `$options`, возвращающее объект `UsersOptions`. Просто используйте трейт в модели пользователя, например так:
```php
<?php
declare(strict_types = 1);

namespace app\models;

use pozitronik\users_options\traits\UsersOptionsTrait;

/**
 * @property int $id
 * ...
 */
class AuthUsers extends ActiveRecord {
    use UsersOptionsTrait;

    /*...*/
}
```

После этого обращаться к параметрам текущего пользователя станет возможно через вызовы
```php
$value = AuthUsers::findOne($id)->options->get($option);
AuthUsers::findOne($id)->options->set($option, $value);
```

В иных случаях описывайте атрибут `$options` в модели самостоятельно, либо используйте статические методы `UsersOptions::getStatic()`/`UsersOptions::getStatic()`

Для сохранения настроек с помощью AJAX, подключите в нужном view-файле ассет `pozitronik\users_options\assets\UsersOptionsAsset.php` и используйте js-вызов `set_option(key, value)`. Подразумевается, что он должен обратиться к экшену `actionUserSetOption()` реализованному в контроллере `pozitronik\users_options\controllers\AjaxController`, либо контроллеру, содержащему реализацию такого метода (либо наследующегося от контроллера расширения). Приведённую реализацию следует считать примером, т.к. конкретный подход может различаться в каждом отдельном случае.

Лицензия
--------
GNU GPL v3.0
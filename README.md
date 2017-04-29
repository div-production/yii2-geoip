# Geo Ip extension for Yii2
Расширение позволяет получить город пользователя, а также 
его регион и страну на основании его IP адреса
## Установка
Через composer:

```bash
$ composer require div/yii2-geoip
```
## Использование
Настройка yii компонента:
```php
'components' => [
    ...
    'geo' => [
        'class' => 'div\geoip\Geo',
        'cityClass' => 'app\models\City' // модель города
    ],
],
```
Примеры использования:
```php
// определение города по текущему адресу
$city = Yii::$app->geo->getCity();
echo $city->name;

// определение города по любому ip
$city = Yii::$app->geo->getCity('123.123.123.123');
echo $city->name;

// получение гео данных по ip
$data = Yii::$app->geo->getData();
/*
Array
(
    [country] => RU
    [city] => Москва
    [region] => Москва
    [district] => Центральный федеральный округ
    [lat] => 55.000000
    [lng] => 37.000000
)
*/
```

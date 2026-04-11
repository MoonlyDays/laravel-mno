# BalancePlus Operator

Пакет Laravel для валидации, нормализации и работы с MSISDN (телефонными номерами). Обёртка над
`giggsey/libphonenumber-for-php` с интеграцией в систему валидации Laravel, Eloquent-касты и фасады.

> **Проприетарный пакет.** Только для внутреннего использования.

## Требования

- PHP 8.2+
- Laravel 11, 12 или 13

## Установка

```bash
composer require balanceplus/operator
```

Опубликуйте конфигурационный файл:

```bash
php artisan vendor:publish --tag="operator-config"
```

## Конфигурация

Настройте переменные окружения:

```env
OPERATOR_NAME=MTS
OPERATOR_COUNTRY=RU
OPERATOR_NETWORK_CODES=910,911,912
OPERATOR_CARRIER_LOCALE=ru_RU
OPERATOR_MSISDN_MIN_LENGTH=10
OPERATOR_MSISDN_MAX_LENGTH=10
```

| Переменная                   | Описание                                             |
|------------------------------|------------------------------------------------------|
| `OPERATOR_NAME`              | Название оператора связи                             |
| `OPERATOR_COUNTRY`           | ISO 3166-1 alpha-2 код страны (например, `RU`, `TZ`) |
| `OPERATOR_NETWORK_CODES`     | NDC-префиксы оператора через запятую                 |
| `OPERATOR_CARRIER_LOCALE`    | Локаль для названий операторов (IETF BCP 47)         |
| `OPERATOR_MSISDN_MIN_LENGTH` | Минимальная длина национального номера               |
| `OPERATOR_MSISDN_MAX_LENGTH` | Максимальная длина национального номера              |

## Использование

### Создание MSISDN

```php
use BalancePlus\Operator\PhoneNumber;

// Парсинг с исключением при ошибке
$msisdn = PhoneNumber::from('+79101234567');
$msisdn = PhoneNumber::from('9101234567', 'RU');

// Безопасный парсинг (возвращает null при ошибке)
$msisdn = PhoneNumber::tryFrom('invalid'); // null

// Глобальный хелпер
$msisdn = msisdn('+79101234567');
```

### Форматирование

```php
$msisdn = Msisdn::from('+79101234567');

$msisdn->e164();          // "+79101234567"
$msisdn->national();      // "8 910 123-45-67"
$msisdn->international(); // "+7 910 123-45-67"
```

### Получение компонентов номера

```php
$msisdn = Msisdn::from('+79101234567');

$msisdn->countryCode();     // 7
$msisdn->countryIso();      // "RU"
$msisdn->nationalNumber();  // "9101234567"
$msisdn->networkCode();     // "910"
$msisdn->subscriberNumber(); // "1234567"
$msisdn->carrierName();     // "MTS"
```

### Валидация в запросах

```php
use Illuminate\Validation\Rule;

// Использование макроса Rule::msisdn()
$request->validate([
    'phone' => ['required', Rule::msisdn()],
]);

// Кастомная конфигурация правила
use BalancePlus\Operator\Rules\PhoneNumberRule;

$request->validate([
    'phone' => [
        'required',
        (new PhoneNumberRule)
            ->country('RU', 'BY', 'KZ')
            ->networkCodes('910', '911')
            ->minLength(10)
            ->maxLength(10),
    ],
]);
```

### Eloquent Cast

```php
use BalancePlus\Operator\Casts\PhoneNumberCast;
use BalancePlus\Operator\PhoneNumber;

class User extends Model
{
    protected $casts = [
        'phone' => PhoneNumberCast::class,
    ];
}

// Использование
$user->phone = '+79101234567';
$user->save(); // Сохраняется в E.164: "+79101234567"

$user->phone->national(); // "8 910 123-45-67"
```

### Фасад Operator

```php
use BalancePlus\Operator\Facades\Operator;

Operator::country();       // "RU"
Operator::countryCode();   // 7
Operator::name();          // "MTS"
Operator::networkCodes();  // ["910", "911", "912"]
Operator::carrierLocale(); // "ru_RU"
Operator::minLength();     // 10
Operator::maxLength();     // 10
Operator::exampleNumber(); // Msisdn instance
```

### Работа с абонентом

```php
use BalancePlus\Operator\Subscriber;
use BalancePlus\Operator\PhoneNumber;

$subscriber = new Subscriber(PhoneNumber::from('+79101234567'));

$subscriber->msisdn()->e164(); // "+79101234567"

// Загрузка компонентов (lazy-loading)
$subscriber->load(BalanceComponent::class);
$subscriber->component(BalanceComponent::class);
```

### Расширение через макросы

```php
use BalancePlus\Operator\PhoneNumber;

PhoneNumber::macro('isRussian', function (): bool {
    return $this->countryIso() === 'RU';
});

$msisdn = PhoneNumber::from('+79101234567');
$msisdn->isRussian(); // true
```

## Тестирование

```bash
composer test
```

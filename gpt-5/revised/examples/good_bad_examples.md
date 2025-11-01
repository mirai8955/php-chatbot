# 良い/悪いコード例（rule_id 紐付け）

## php.strict_types

### good {#php.strict_types-good}
```php
<?php declare(strict_types=1);

namespace App;

class Example {}
```

### bad {#php.strict_types-bad}
```php
<?php
namespace App;
class Example {}
```

---

## typing.property_types

### good {#typing.property_types-good}
```php
<?php declare(strict_types=1);

class User
{
    private string $name;
}
```

### bad {#typing.property_types-bad}
```php
<?php declare(strict_types=1);

class User
{
    private $name; // 型なし
}
```

---

## typing.return_types

### good {#typing.return_types-good}
```php
<?php declare(strict_types=1);

class Calculator
{
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }
}
```

### bad {#typing.return_types-bad}
```php
<?php declare(strict_types=1);

class Calculator
{
    public function add($a, $b) // 戻り値型・引数型なし
    {
        return $a + $b;
    }
}
```

---

## exceptions.usage_categories

### good {#exceptions.usage_categories-good}
```php
<?php declare(strict_types=1);

function setLevel(int $level): void
{
    if ($level < 0) {
        throw new \InvalidArgumentException('level must be >= 0');
    }
}
```

### bad {#exceptions.usage_categories-bad}
```php
<?php declare(strict_types=1);

function setLevel($level): void
{
    if ($level < 0) {
        throw new \Exception('bad');
    }
}
```

---

## structure.guard_clauses

### good {#structure.guard_clauses-good}
```php
<?php declare(strict_types=1);

function process(array $data): void
{
    if ($data === []) { return; }
    if (!isset($data['enabled'])) { return; }
    // 正常系だけを書く
}
```

### bad {#structure.guard_clauses-bad}
```php
<?php declare(strict_types=1);

function process(array $data): void
{
    if ($data !== []) {
        if (isset($data['enabled'])) {
            // ネストが深い
        }
    }
}
```

# Monolog コーディングスタイルガイド

## 📑 目次

1. [ファイル構造](#ファイル構造)
2. [命名規則](#命名規則)
3. [型システム](#型システム)
4. [クラス設計](#クラス設計)
5. [メソッド実装](#メソッド実装)
6. [エラーハンドリング](#エラーハンドリング)
7. [PHPDoc ドキュメンテーション](#phpdoc-ドキュメンテーション)
8. [テスト構造](#テスト構造)
9. [チェックリスト](#チェックリスト)

---

## ファイル構造

### 必須要素の順序

```php
<?php declare(strict_types=1);

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog\Handler;

use DateTimeZone;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Handler\HandlerInterface;
use Psr\Log\InvalidArgumentException;

/**
 * Class-level documentation.
 */
class StreamHandler extends AbstractProcessingHandler
{
    // implementation
}
```

### 順序ルール

1. **PHP 開始タグ**: `<?php` （最初の文字は列1）
2. **declare ステートメント**: `declare(strict_types=1);`
3. **空行**
4. **ライセンスコメント**: 標準の3行ブロック
5. **空行**
6. **namespace 宣言**
7. **use ステートメント**: アルファベット順、大文字小文字区別
8. **空行**
9. **クラスドキュメント（PHPDoc）**
10. **クラス定義**

### use ステートメントのアルファベット順

```php
use ArrayAccess;
use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Fiber;
use InvalidArgumentException;
use LogicException;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\LogRecord;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;
use WeakMap;
```

---

## 命名規則

### クラス名

**形式**: PascalCase （UpperCamelCase）

```php
// ✅ 正しい
class StreamHandler {}
class JsonFormatter {}
class GitProcessor {}
class LoggerInterface {}

// ❌ 間違い
class stream_handler {}
class jsonFormatter {}
class git_processor {}
```

### インターフェース名

**形式**: PascalCase で、`Interface` サフィックス

```php
// ✅ 正しい
interface HandlerInterface {}
interface FormatterInterface {}
interface ProcessorInterface {}

// ❌ 間違い
interface IHandler {}
interface Formattable {}
```

### メソッド名

**形式**: camelCase

```php
// ✅ 正しい
public function pushHandler(HandlerInterface $handler): self {}
public function popHandler(): HandlerInterface {}
public function isHandling(LogRecord $record): bool {}
public function getName(): string {}
private function customErrorHandler(int $code, string $msg): bool {}

// ❌ 間違い
public function PushHandler() {}
public function push_handler() {}
```

### プロパティ名

**形式**: camelCase、先頭にドルマーク、可視性修飾子必須

```php
// ✅ 正しい
protected string $name;
private int $logDepth = 0;
protected array $handlers;
protected DateTimeZone $timezone;

// ❌ 間違い
protected string $_name;        // アンダースコア接頭辞不要
public string $name;             // プロパティは protected/private を推奨
var $handlers;                   // 古い var キーワード
```

### 定数名

**形式**: UPPER_SNAKE_CASE

```php
// ✅ 正しい
public const API = 3;
protected const MAX_CHUNK_SIZE = 2147483647;
/** 10MB */
protected const DEFAULT_CHUNK_SIZE = 10 * 1024 * 1024;
private const RFC_5424_LEVELS = [/* ... */];

// ❌ 間違い
public const api = 3;
public const API_VERSION = 3;    // 設定内容に応じた命名に
```

### ローカル変数名

**形式**: camelCase

```php
// ✅ 正しい
$logDepth = 0;
$stream = fopen($url, $this->fileOpenMode);
$handler = new StreamHandler($path);
$isValid = true;

// ❌ 間違い
$log_depth = 0;
$STREAM = fopen(...);
```

---

## 型システム

### プロパティの型付与（必須）

```php
class Logger
{
    // ✅ 必ず型を付ける
    protected string $name;
    protected array $handlers;
    protected bool $microsecondTimestamps = true;
    protected DateTimeZone $timezone;
    private int $logDepth = 0;
    private WeakMap $fiberLogDepth;
    protected Closure|null $exceptionHandler = null;
    
    // ❌ 型なしは許されない
    protected $name;
    private $depth;
}
```

### メソッド引数の型付与（必須）

```php
// ✅ 型付き
public function pushHandler(HandlerInterface $handler): self
{
    array_unshift($this->handlers, $handler);
    return $this;
}

public function addRecord(
    int|Level $level,
    string $message,
    array $context = [],
    JsonSerializableDateTimeImmutable|null $datetime = null
): bool
{
    // ...
}

// ❌ 型なし
public function pushHandler($handler)
{
    // ...
}
```

### Union Types の活用

```php
// ✅ Union Types を使用
public static function toMonologLevel(string|int|Level $level): Level
{
    if ($level instanceof Level) {
        return $level;
    }
    // ...
}

// Nullable Types
protected DateTimeZone|null $timezone = null;
private bool|null $dirCreated = null;

// ❌ 型チェックなし
public static function toMonologLevel($level)
{
    // ...
}
```

### Readonly プロパティ

```php
// ✅ 不変プロパティに readonly 修飾子
class LogRecord
{
    public function __construct(
        public readonly DateTimeImmutable $datetime,
        public readonly string $channel,
        public readonly Level $level,
        public readonly string $message,
        public readonly array $context = [],
        public array $extra = [],
        public mixed $formatted = null,
    ) {
    }
}

// ❌ readonly なしで代入可能にしない
public DateTimeImmutable $datetime;
```

### Named Arguments の活用

```php
// ✅ Named Arguments で可読性向上
$record = new LogRecord(
    datetime: $datetime ?? new JsonSerializableDateTimeImmutable($this->microsecondTimestamps, $this->timezone),
    channel: $this->name,
    level: self::toMonologLevel($level),
    message: $message,
    context: $context,
    extra: [],
);

// ❌ 位置引数のみ
$record = new LogRecord(
    $datetime ?? new JsonSerializableDateTimeImmutable(...),
    $this->name,
    self::toMonologLevel($level),
    $message,
    $context,
    []
);
```

---

## クラス設計

### クラスメンバーの順序

```php
class MyHandler extends AbstractHandler
{
    // 1. クラス定数
    public const CONSTANT1 = 'value';
    private const CONSTANT2 = 123;

    // 2. プロパティ（public → protected → private）
    public string $publicProp;
    protected string $protectedProp;
    private int $privateProp;

    // 3. Constructor
    public function __construct() {}

    // 4. Public メソッド
    public function publicMethod(): void {}

    // 5. Protected メソッド
    protected function protectedMethod(): void {}

    // 6. Private メソッド
    private function privateMethod(): void {}

    // 7. マジックメソッド
    public function __serialize(): array {}
    public function __unserialize(array $data): void {}
}
```

### 継承とインターフェース実装

```php
// ✅ 継承 → インターフェース実装
class Logger extends BaseLogger implements LoggerInterface, ResettableInterface
{
    // implementation
}

// ❌ インターフェース実装を先に書かない
class Logger implements LoggerInterface extends BaseLogger
{
    // implementation
}
```

### Trait の活用

```php
// ✅ 共通機能を Trait で実装
trait FormattableHandlerTrait
{
    protected ?FormatterInterface $formatter = null;

    public function setFormatter(FormatterInterface $formatter): self
    {
        $this->formatter = $formatter;
        return $this;
    }
}

class MyHandler extends AbstractHandler
{
    use FormattableHandlerTrait;
}

// ❌ 複数の同様機能を各クラスで重複実装
class Handler1 extends AbstractHandler
{
    public function setFormatter(FormatterInterface $formatter): self { }
}

class Handler2 extends AbstractHandler
{
    public function setFormatter(FormatterInterface $formatter): self { }
}
```

### Enum の活用

```php
// ✅ 固定値セットは Enum を使用
enum Level: int
{
    case Debug = 100;
    case Info = 200;
    case Notice = 250;
    case Warning = 300;
    case Error = 400;
    case Critical = 500;
    case Alert = 550;
    case Emergency = 600;

    public function getName(): string
    {
        return match($this) {
            self::Debug => 'DEBUG',
            self::Info => 'INFO',
            // ...
        };
    }
}

// ❌ クラス定数で代替
class LogLevel
{
    public const DEBUG = 100;
    public const INFO = 200;
    // ...
}
```

---

## メソッド実装

### 基本形

```php
/**
 * Method description.
 *
 * @param string $name  Parameter description
 * @param array  $items Item list
 * @return bool         Success flag
 */
public function myMethod(string $name, array $items): bool
{
    // パラメーター検証
    if (empty($name)) {
        throw new InvalidArgumentException('Name cannot be empty');
    }

    // 早期 return による可読性向上
    if (!$this->isValid()) {
        return false;
    }

    // メイン処理
    $result = $this->doSomething($name, $items);

    // 戻り値
    return $result;
}
```

### Fluent インターフェース

```php
// ✅ メソッドチェーン可能に
class Logger
{
    public function pushHandler(HandlerInterface $handler): self
    {
        array_unshift($this->handlers, $handler);
        return $this;
    }

    public function pushProcessor(callable $callback): self
    {
        array_unshift($this->processors, $callback);
        return $this;
    }

    public function useMicrosecondTimestamps(bool $micro): self
    {
        $this->microsecondTimestamps = $micro;
        return $this;
    }
}

// 使用例
$logger
    ->pushHandler($handler)
    ->pushProcessor($processor)
    ->useMicrosecondTimestamps(true);
```

### Guard 句による可読性向上

```php
// ❌ ネストが深い
public function process(LogRecord $record): LogRecord
{
    if ($record->level->isHigherThan($this->level)) {
        if ($this->isValid($record)) {
            $record->extra['data'] = $this->getData();
            return $record;
        }
    }
    return $record;
}

// ✅ Guard 句で早期 return
public function process(LogRecord $record): LogRecord
{
    // Level チェック
    if ($record->level->isLowerThan($this->level)) {
        return $record;
    }

    // Validation チェック
    if (!$this->isValid($record)) {
        return $record;
    }

    // メイン処理
    $record->extra['data'] = $this->getData();
    return $record;
}
```

---

## エラーハンドリング

### 例外の使い分け

```php
// ✅ 適切な例外を使い分ける

// 無効な引数
if (!\is_resource($stream) && !\is_string($stream)) {
    throw new InvalidArgumentException('A stream must either be a resource or a string.');
}

// ロジックエラー
if (0 === count($this->handlers)) {
    throw new LogicException('You tried to pop from an empty handler stack.');
}

// 実行時エラー
if (!\is_resource($stream)) {
    throw new UnexpectedValueException(sprintf('The stream "%s" could not be opened', $url));
}
```

### try-finally でリソース解放

```php
// ✅ finally 句で確実なリソース解放
public function write(LogRecord $record): void
{
    $this->errorMessage = null;
    set_error_handler($this->customErrorHandler(...));

    try {
        $this->streamWrite($stream, $record);
    } finally {
        restore_error_handler();
    }

    if ($this->errorMessage !== null) {
        throw new UnexpectedValueException('Writing to the log file failed: ' . $this->errorMessage);
    }
}

// ❌ finally なしでリソースが残る
public function write(LogRecord $record): void
{
    set_error_handler($this->customErrorHandler(...));
    $this->streamWrite($stream, $record);
    restore_error_handler();
}
```

### 例外チェーン

```php
// ✅ 前の例外を保持
try {
    $result = $this->riskyOperation();
} catch (Throwable $e) {
    $this->handleException($e, $record);
    throw new RuntimeException('Operation failed', previous: $e);
}

// ❌ 元の例外情報が消える
try {
    $result = $this->riskyOperation();
} catch (Throwable $e) {
    throw new RuntimeException('Operation failed');
}
```

### エラーコールバック

```php
// ✅ カスタム error handler でエラーメッセージをキャプチャ
private function customErrorHandler(int $code, string $msg): bool
{
    $this->errorMessage = preg_replace(
        '{^(fopen|mkdir|fwrite)\(.*?\): }',
        '',
        $msg
    );
    return true;
}
```

---

## PHPDoc ドキュメンテーション

### クラスドキュメント

```php
/**
 * Stores to any stream resource
 *
 * Can be used to store into php://stderr, remote and local files, etc.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class StreamHandler extends AbstractProcessingHandler
{
    // ...
}
```

### メソッドドキュメント

```php
/**
 * Adds a log record.
 *
 * @param  int|Level                      $level    The logging level (Monolog or RFC 5424)
 * @param  string                         $message  The log message
 * @param  array<mixed>                   $context  The log context
 * @param  JsonSerializableDateTimeImmutable|null $datetime Optional log date
 * @return bool                           Whether the record was processed
 *
 * @throws InvalidArgumentException If level is invalid
 *
 * @phpstan-param value-of<Level::VALUES>|Level $level
 */
public function addRecord(
    int|Level $level,
    string $message,
    array $context = [],
    JsonSerializableDateTimeImmutable|null $datetime = null
): bool
{
    // ...
}
```

### パラメータドキュメント

```php
/**
 * @param resource|string $stream         Stream resource or file path
 * @param int|string|Level $level        Minimum log level
 * @param bool             $bubble        Propagate to other handlers
 * @param int|null         $filePermission File permissions (default 0644)
 * @param bool             $useLocking    Lock file before writing
 * @param string           $fileOpenMode  File open mode (default 'a')
 */
public function __construct(
    $stream,
    int|string|Level $level = Level::Debug,
    bool $bubble = true,
    ?int $filePermission = null,
    bool $useLocking = false,
    string $fileOpenMode = 'a'
)
{
    // ...
}
```

### PHPStan アノテーション

```php
/**
 * @param  string  $name
 * @return static
 *
 * @phpstan-param value-of<Level::NAMES> $name
 * @phpstan-return static
 */
public static function fromName(string $name): self
{
    // ...
}
```

### 非推奨要素

```php
/**
 * Gets the name of the logging level as a string.
 *
 * This still returns a string instead of a Level for BC,
 * but new code should not rely on this method.
 *
 * @deprecated Since 3.0, use {@see toMonologLevel} or {@see Level::getName()} instead
 *
 * @throws InvalidArgumentException
 */
public static function getLevelName(int|Level $level): string
{
    // ...
}
```

---

## テスト構造

### テストクラスの命名と構造

```php
/**
 * @covers \Monolog\Handler\StreamHandler
 */
class StreamHandlerTest extends MonologTestCase
{
    /**
     * @covers StreamHandler::__construct
     */
    public function testConstructor(): void
    {
        $handler = new StreamHandler('php://memory');
        $this->assertInstanceOf(StreamHandler::class, $handler);
    }

    /**
     * @covers StreamHandler::write
     */
    public function testWrite(): void
    {
        $handler = new StreamHandler('php://memory');
        $record = $this->getRecord();
        
        $handler->handle($record);
        
        $this->assertTrue(true); // assertion
    }
}
```

### テストメソッドの命名

```php
// ✅ test + メソッド名 + 条件
public function testWriteWithValidStream(): void {}
public function testWriteThrowsOnInvalidStream(): void {}
public function testGetNameReturnsString(): void {}
public function testPushHandlerReturnsSelf(): void {}

// ❌ 曖昧な命名
public function testIt(): void {}
public function testWorks(): void {}
public function testMethod(): void {}
```

### Data Provider の活用

```php
#[DataProvider('validLevelProvider')]
public function testConvertValidLevel(string|int $level, Level $expected): void
{
    $this->assertEquals($expected, Logger::toMonologLevel($level));
}

public static function validLevelProvider(): array
{
    return [
        ['debug', Level::Debug],
        [100, Level::Debug],
        ['info', Level::Info],
        [200, Level::Info],
    ];
}
```

---

## チェックリスト

### コードレビュー用チェックリスト

- [ ] **ファイル構造**
  - [ ] `<?php declare(strict_types=1);` が最初の行
  - [ ] ライセンスコメント（3行）が含まれている
  - [ ] namespace が適切
  - [ ] use ステートメントがアルファベット順

- [ ] **命名規則**
  - [ ] クラス名が PascalCase
  - [ ] メソッド名が camelCase
  - [ ] プロパティ名が camelCase で型付き
  - [ ] 定数が UPPER_SNAKE_CASE

- [ ] **型システム**
  - [ ] 全プロパティに型付与
  - [ ] 全メソッド引数に型付与
  - [ ] 全メソッド戻り値に型付与
  - [ ] readonly が適切に使用されている

- [ ] **クラス設計**
  - [ ] メンバーが順序通り（定数 → プロパティ → constructor → public → protected → private）
  - [ ] インターフェース実装が明確
  - [ ] 継承階層が合理的

- [ ] **メソッド実装**
  - [ ] Guard 句で可読性向上
  - [ ] 長いメソッドはサブメソッドに分割
  - [ ] Fluent インターフェース適用可能か確認

- [ ] **エラーハンドリング**
  - [ ] 適切な例外クラスを使用
  - [ ] try-finally でリソース解放
  - [ ] エラーメッセージが情報豊富

- [ ] **PHPDoc**
  - [ ] クラスにドキュメント有
  - [ ] public メソッドにドキュメント有
  - [ ] @param, @return, @throws 記載
  - [ ] @deprecated 使用時に理由記載

- [ ] **テスト**
  - [ ] テストメソッド名が `test + メソッド名 + 条件` の形式
  - [ ] @covers タグで対応関係明記
  - [ ] データが多い場合は DataProvider 使用

---

## 参考資料

- PSR-12: Extended Coding Style
- PHP 8.1+ Language Features
- Monolog Official Documentation
- PHPStan Docs

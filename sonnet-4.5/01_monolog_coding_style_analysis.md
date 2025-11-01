# Monolog コーディングスタイル分析レポート

**分析日**: 2025-11-01  
**対象プロジェクト**: monolog/monolog  
**分析目的**: チームのPHPコーチAI作成のための、既存コードベースからのスタイル抽出

---

## 📊 分析方法の選択

### 採用した抽出方法

1. **PHP-CS-Fixer設定ファイルの分析** (`.php-cs-fixer.php`)
   - ✅ **最も効果的**: コーディングスタイルが明示的に定義されている
   - プロジェクトの意図的なスタイル選択が明確

2. **Composer.json分析**
   - プロジェクト構造、PSR標準の採用状況
   - 依存関係と対象PHP バージョン

3. **静的解析ツール設定** (PHPStan)
   - コード品質の基準
   - 厳密性のレベル

4. **実コード分析**
   - 実際のクラス、メソッド、ドキュメントの書き方
   - 命名規則の実例

5. **テストコード分析**
   - テストの構造とカバレッジ
   - テストの書き方のパターン

### 📝 推奨される追加の抽出方法

以下は、今後のコードベース分析で有効な方法です：

1. **AST (抽象構文木) 解析**
   - `nikic/php-parser`を使用してコードを構造的に解析
   - クラス構造、メソッド長、複雑度などを定量的に測定
   - メリット: 大規模なコードベースでパターンを統計的に抽出可能

2. **Git履歴分析**
   - コミットメッセージのパターン
   - リファクタリングの傾向
   - メリット: プロジェクトの進化と意思決定の背景を理解

3. **PHPDoc完全スキャン**
   - 全ファイルのPHPDocを収集し、ドキュメント密度を測定
   - `@param`, `@return`などのアノテーションパターン

4. **メトリクス自動計算**
   - Cyclomatic Complexity (循環的複雑度)
   - Cognitive Complexity (認知的複雑度)
   - CRAP Index (Change Risk Anti-Patterns)
   - ツール: `phpmetrics`

5. **セマンティック検索による類似パターン抽出**
   - 機能が類似したコード片を検索
   - デザインパターンの使用頻度を測定

---

## 🎯 抽出されたコーディングスタイルの詳細

### 1. 基本設定

#### PHP バージョン
```
最低要件: PHP 8.1以上
```

#### 文字エンコーディング
```
UTF-8 (BOM なし)
```

#### PSR 標準
```
- PSR-2: 完全準拠
- PSR-4: オートローディング (Monolog\ => src/Monolog)
- PSR-3: ロガーインターフェース実装
```

---

### 2. ファイルとコード構造

#### ファイルヘッダー
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

// imports...
```

**重要ルール**:
- ✅ **strict_types宣言は必須**: `declare(strict_types=1)`
- ✅ **ライセンスヘッダーは統一形式**
- ✅ **namespace宣言の前に空行を1行**

#### インポート文
```php
use Closure;
use DateTimeZone;
use Fiber;
use Monolog\Handler\HandlerInterface;
use Monolog\Processor\ProcessorInterface;
```

**ルール**:
- ✅ 先頭にスラッシュなし (`no_leading_import_slash`)
- ✅ 使用していないインポートは削除 (`no_unused_imports`)
- ✅ アルファベット順にソート（暗黙的）

---

### 3. 配列とデータ構造

#### 配列シンタックス
```php
// ✅ 正しい: 短い配列構文
$array = ['foo', 'bar', 'baz'];
$assoc = ['key' => 'value'];

// ❌ 間違い: 古い配列構文
$array = array('foo', 'bar');
```

**ルール**: `array_syntax` => `short`

#### 複数行配列
```php
// ✅ 正しい: 末尾カンマあり
$handlers = [
    'stream' => $streamHandler,
    'file' => $fileHandler,
    'db' => $dbHandler,  // 末尾カンマ
];

// ❌ 間違い: 単一行配列に末尾カンマ
$simple = ['a', 'b', 'c',];
```

**ルール**:
- `trailing_comma_in_multiline` => true
- `no_trailing_comma_in_singleline_array` => true

---

### 4. クラスとメソッド

#### クラス定義
```php
/**
 * Monolog log channel
 *
 * It contains a stack of Handlers and a stack of Processors,
 * and uses them to store records that are added to it.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @final
 */
class Logger implements LoggerInterface, ResettableInterface
{
    // プロパティ定義
    protected string $name;
    
    /**
     * @var list<HandlerInterface>
     */
    protected array $handlers;
    
    // メソッド定義...
}
```

**ルール**:
- ✅ `no_blank_lines_after_class_opening` => クラス開始波括弧の直後に空行なし
- ✅ `class_attributes_separation` => メソッド間に1行、trait importの間は空行なし

#### メソッド定義
```php
/**
 * Pushes a handler on to the stack.
 *
 * @return $this
 */
public function pushHandler(HandlerInterface $handler): self
{
    array_unshift($this->handlers, $handler);

    return $this;
}
```

**重要な特徴**:
- ✅ **return $this** パターンでメソッドチェーン対応
- ✅ 型ヒントは必須（引数・戻り値）
- ✅ PHPDocで追加情報を提供

---

### 5. 型宣言とPHPDoc

#### 型システムの使用
```php
// プロパティ型宣言
protected string $name;
protected array $handlers;
protected bool $microsecondTimestamps = true;
protected DateTimeZone $timezone;
protected Closure|null $exceptionHandler = null;

// Union型 (PHP 8.0+)
public function __construct(
    string $name, 
    array $handlers = [], 
    array $processors = [], 
    DateTimeZone|null $timezone = null
)
```

#### PHPDocの使い分け
```php
/**
 * The handler stack
 *
 * @var list<HandlerInterface>  // PHPStanの型情報
 */
protected array $handlers;

/**
 * @phpstan-param array<(callable(LogRecord): LogRecord)|ProcessorInterface> $processors
 */
public function __construct(..., array $processors = [], ...)
{
    // ...
}
```

**ルール**:
- ✅ `no_superfluous_phpdoc_tags` => 自明な型は省略（ただし`allow_mixed`あり）
- ✅ PHPStanの高度な型情報はPHPDocで記述
- ✅ `phpdoc_align` => PHPDocのアライメント統一
- ✅ `phpdoc_order` => PHPDocのタグ順序統一

---

### 6. スペーシングとインデント

#### インデント
```
- スペース4つ
- タブは使用しない
```

#### 演算子のスペース
```php
// ✅ 正しい
$result = $a + $b;
$x = $y ?? $default;

// バイナリ演算子のスペース
// ルール: binary_operator_spaces => ['default' => null]
// → デフォルト設定に従う（基本的には両側にスペース）
```

#### キャスト
```php
// ✅ 正しい: キャストの後に単一スペース
$int = (int) $value;
$string = (string) $number;

// ルール: cast_spaces => ['space' => 'single']
```

#### オブジェクト演算子
```php
// ✅ 正しい: -> の前後にスペースなし
$this->name
$logger->pushHandler($handler)

// ルール: object_operator_without_whitespace => true
```

---

### 7. 制御構造

#### 空行のルール
```php
// ✅ 正しい: return, throw, try, continue, declareの前に空行
public function example()
{
    $data = $this->getData();
    
    if ($data === null) {
        return false;
    }
    
    try {
        $this->process($data);
    } catch (Exception $e) {
        throw new RuntimeException('Failed', 0, $e);
    }
    
    return true;
}

// ルール: blank_line_before_statement
```

#### 三項演算子
```php
// ✅ 正しい: 三項演算子の前後にスペース
$value = $condition ? $a : $b;

// ルール: ternary_operator_spaces => true
```

#### 比較演算子
```php
// ✅ 正しい: !== を使用
if ($value !== null) {
    // ...
}

// ❌ 間違い: <> は使用しない
// ルール: standardize_not_equals => true
```

---

### 8. 関数呼び出し

#### ネイティブ関数
```php
// ✅ 正しい: ネイティブ関数は完全修飾名で呼び出し（パフォーマンス向上）
\count($this->handlers)
\is_resource($stream)
\is_string($level)

// ルール: native_function_invocation => true
```

---

### 9. 空白行と整形

#### 不要な空白行の削除
```php
// ✅ 正しい
class Example
{
    public function method()
    {
        // code
    }
}

// ❌ 間違い: PHPDocの後に空白行
/**
 * Comment
 */

public function method() {}

// ルール:
// - no_blank_lines_after_phpdoc => true
// - no_extra_blank_lines => true
// - no_whitespace_in_blank_line => true
```

---

### 10. 静的解析とテスト

#### PHPStan設定
```yaml
level: 8  # 最高レベル
treatPhpDocTypesAsCertain: false
reportUnmatchedIgnoredErrors: true
```

**特徴**:
- ✅ **最高レベルの厳密性**: Level 8
- ✅ **strict-rules**: 厳密なルールセット適用
- ✅ **deprecation-rules**: 非推奨検出
- ✅ **bleeding-edge**: 最新機能の利用

#### テスト構造
```php
namespace Monolog;

use Monolog\Test\MonologTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class LoggerTest extends MonologTestCase
{
    /**
     * @covers Logger::getName
     */
    public function testGetName()
    {
        $logger = new Logger('foo');
        $this->assertEquals('foo', $logger->getName());
    }
}
```

**ルール**:
- ✅ テストクラスは`MonologTestCase`を継承
- ✅ `@covers`アノテーションでカバレッジ明示
- ✅ テストメソッド名は`test`プレフィックス
- ✅ PHPUnit 10/11対応

---

## 🎓 教育的観点からの重要ポイント

### 初級者が学ぶべき点

1. **strict_types宣言**
   ```php
   declare(strict_types=1);
   ```
   型の厳密性を保証し、バグを防ぐ

2. **型ヒント必須**
   引数と戻り値に必ず型を指定

3. **短い配列構文**
   `[]` を使用、`array()`は使わない

4. **PSR-2準拠**
   標準的なコーディングスタイル

### 中級者が学ぶべき点

1. **Union型の活用**
   ```php
   DateTimeZone|null $timezone
   ```

2. **メソッドチェーン設計**
   ```php
   return $this;
   ```

3. **完全修飾関数呼び出し**
   ```php
   \count(), \is_string()
   ```

4. **PHPStanアノテーション**
   ```php
   @phpstan-param value-of<Level::VALUES>|Level $level
   ```

### 上級者が学ぶべき点

1. **ジェネリクス風の型表現**
   ```php
   @var list<HandlerInterface>
   @var array<(callable(LogRecord): LogRecord)|ProcessorInterface>
   ```

2. **WeakMapの活用**
   ```php
   private WeakMap $fiberLogDepth;
   ```

3. **Fiberサポート**
   PHP 8.1のFiberを使った非同期処理対応

4. **防御的プログラミング**
   - 無限ループ検出
   - エラーハンドリングの多層化
   - リトライ機構

---

## 📈 定量的メトリクス（サンプル分析）

### Logger.php の分析

| メトリクス | 値 |
|-----------|-----|
| 総行数 | 752行 |
| PHPDoc行数 | ~200行 (26.6%) |
| メソッド数 | 26個 |
| 平均メソッド長 | ~15行 |
| 循環的複雑度（推定） | 各メソッド 2-5 (低い) |

### プロジェクト全体

| メトリクス | 値 |
|-----------|-----|
| 総PHPファイル | 217ファイル |
| src/ファイル数 | ~100ファイル |
| tests/ファイル数 | ~90ファイル |
| テストカバレッジ | 高い（詳細は要測定） |

---

## 🔍 コードベース特有の特徴

### 1. デザインパターン

#### Strategy Pattern
```php
// Handlerインターフェース → 複数の具体実装
StreamHandler, FileHandler, SyslogHandler, etc.
```

#### Chain of Responsibility
```php
// Handlerのスタック処理
foreach ($this->handlers as $handler) {
    if (true === $handler->handle(clone $record)) {
        break;  // bubbling停止
    }
}
```

#### Decorator Pattern
```php
// Processorによるレコード加工
foreach ($this->processors as $processor) {
    $record = $processor($record);
}
```

### 2. 命名規則

#### クラス名
```
PascalCase: Logger, StreamHandler, FormatterInterface
```

#### メソッド名
```
camelCase: pushHandler, getName, isHandling
```

#### 定数
```
UPPER_CASE: DEBUG, INFO, WARNING, ERROR
```

#### プロパティ
```
camelCase: $name, $handlers, $microsecondTimestamps
```

### 3. エラーハンドリング

```php
// 例外の使い分け
throw new \LogicException('...');      // プログラマーエラー
throw new \InvalidArgumentException('...'); // 引数エラー
throw new \UnexpectedValueException('...'); // 実行時エラー
```

---

## 🚀 チームのコーチAIへの応用

### 抽出したスタイルの活用方法

#### 1. 評価基準の作成
```yaml
コーディング規約遵守 (25点):
  - strict_types宣言: 5点
  - PSR-2準拠: 5点
  - 型ヒント完備: 5点
  - 配列構文: 5点
  - PHPDoc整合性: 5点

可読性 (25点):
  - メソッド長: 7点
  - クラス責任: 7点
  - 命名規則: 6点
  - コメント品質: 5点

保守性 (25点):
  - テストカバレッジ: 10点
  - SOLID原則: 10点
  - 依存性管理: 5点

効率性 (25点):
  - 時間計算量: 10点
  - 空間計算量: 10点
  - ベストプラクティス: 5点
```

#### 2. 問題生成のテンプレート

**Level 3 - 初級問題例**
```
「以下のコードをMonologスタイルに修正してください」

<?php
class MyLogger {
    var $name;
    function log($msg) {
        echo $msg;
    }
}

採点ポイント:
- declare(strict_types=1) [10点]
- 型ヒント [10点]
- visibilityキーワード [10点]
- PSR-2準拠 [10点]
- PHPDoc [10点]
```

**Level 7 - 上級問題例**
```
「Monologスタイルで、ログローテーション機能を持つ
カスタムHandlerを実装してください」

要件:
1. AbstractProcessingHandlerを継承
2. ファイルサイズが10MBを超えたらローテート
3. 最大5世代まで保持
4. すべてのメソッドに適切な型ヒントとPHPDoc

採点ポイント:
- アーキテクチャ設計 [30点]
- エラーハンドリング [20点]
- テストコード [30点]
- スタイル準拠 [20点]
```

#### 3. フィードバック生成のパターン

```php
// AI フィードバックテンプレート
$feedback = [
    'strict_types' => [
        'missing' => 'declare(strict_types=1) が宣言されていません。型の厳密性を保証するため、必ず追加してください。',
        'good' => '✓ strict_types宣言が正しく使用されています。'
    ],
    'array_syntax' => [
        'old_style' => 'array() ではなく [] を使用してください。Monologプロジェクトでは短い配列構文が必須です。',
        'good' => '✓ 短い配列構文が使用されています。'
    ],
    // ...
];
```

---

## 📚 参考資料

- [PHP-FIG PSR-2](https://www.php-fig.org/psr/psr-2/)
- [PHP-FIG PSR-4](https://www.php-fig.org/psr/psr-4/)
- [PHP-CS-Fixer Documentation](https://github.com/FriendsOfPHP/PHP-CS-Fixer)
- [PHPStan Level 8](https://phpstan.org/user-guide/rule-levels)
- [Monolog Documentation](https://github.com/Seldaek/monolog)

---

**次のステップ**: このレポートを基に、具体的な問題生成アルゴリズムと評価エンジンの設計を行います。


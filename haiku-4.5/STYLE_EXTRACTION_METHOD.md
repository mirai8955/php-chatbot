# Monolog プロジェクト - コーディングスタイル抽出方法論

## 📌 抽出方法論の概要

Monolog プロジェクトのコーディングスタイルを抽出するために、以下の **複層的・段階的な分析アプローチ** を採用しました。

### 実施したアプローチ

#### **1. 構造的分析（Structural Analysis）**
- **対象**: ファイル構成、ディレクトリ設計、名前空間の使い方
- **方法**: ファイルシステムの探索と composer.json の確認
- **発見**:
  - PSR-4 オートローディング標準に完全準拠
  - `src/Monolog/` 配下に機能別ディレクトリ（Handler, Formatter, Processor）
  - テストも同じ構造を模倣（`tests/Monolog/`）
  - クリアな責任分離

#### **2. 文法・構文的分析（Syntax & Declaration Analysis）**
- **対象**: ファイル先頭のステートメント、型宣言、名前空間管理
- **方法**: 複数のコアファイル（Logger.php, LogRecord.php, Level.php）を読み込み比較
- **発見**:
  - `<?php declare(strict_types=1);` が全ファイルで統一
  - 厳密な型宣言が強制されている
  - PHP 8.1+ の最新機能（Enum、Union Types、Named Arguments など）を積極活用

#### **3. インターフェース・設計パターン分析（Design Pattern Analysis）**
- **対象**: 抽象クラス、インターフェース、トレイト、Enum の活用
- **方法**: `HandlerInterface`, `FormatterInterface`, `ProcessorInterface` などの確認
- **発見**:
  - インターフェース駆動設計が徹底
  - trait による横断的関心事の処理（`FormattableHandlerTrait` など）
  - Enum の活用（`Level` enum）
  - 継承階層が明確（`AbstractHandler` → 具体クラス）

#### **4. 命名規則分析（Naming Convention Analysis）**
- **対象**: クラス名、メソッド名、変数名、定数名
- **方法**: 複数ファイルの識別子を収集・分類
- **発見**:
  - **クラス名**: PascalCase（`StreamHandler`, `JsonFormatter`）
  - **メソッド名**: camelCase（`pushHandler`, `getRecords`）
  - **定数**: UPPER_SNAKE_CASE（`MAX_CHUNK_SIZE`, `BATCH_MODE_JSON`）
  - **プライベート変数**: 前置アンダースコア廃止、可視性修飾子に頼る

#### **5. コメント・ドキュメンテーション分析（Documentation Style Analysis）**
- **対象**: ファイルヘッダー、クラスドキュメント、メソッドドキュメント
- **方法**: PHPDoc コメントの形式・詳細度を検査
- **発見**:
  - ファイルに統一された LICENSE ブロック（3行ブロック）
  - 全クラスに PHPDoc付属（作者情報、簡潔な説明）
  - メソッドの `@param`, `@return`, `@throws` が詳細
  - `@phpstan-param` による型ヒント強化
  - 非推奨要素に `@deprecated` を明記

#### **6. コード構造・フロー分析（Code Structure & Flow Analysis）**
- **対象**: 関数の実装、制御フロー、エラーハンドリング
- **方法**: StreamHandler.php, JsonFormatter.php の詳細確認
- **発見**:
  - 長い関数をサブメソッドに分割（責任の分離）
  - `try-finally` による確実なリソース解放
  - 明確な条件分岐（早期 return による可読性向上）
  - 例外処理の明確さ（`\InvalidArgumentException`, `\LogicException` など）

#### **7. 型システム活用分析（Type System Analysis）**
- **対象**: 引数型、戻り値型、ジェネリック的表現
- **方法**: メソッドシグネチャの確認
- **発見**:
  - **Union Types**: `int|string|Level`
  - **Nullable Types**: `DateTimeZone|null`
  - **Named Arguments**: コンストラクタで `datetime:`, `channel:` 等
  - **Typed Properties**: `protected string $name;`
  - **匿名クラス使用**: `fn()` 無名関数の活用

#### **8. 可視性・アクセス制御分析（Visibility & Access Control）**
- **対象**: public, protected, private の使い分け
- **方法**: クラスメンバーの修飾子を集計
- **発見**:
  - **public**: API として公開すべきもののみ
  - **protected**: サブクラスでオーバーライド可能な拡張ポイント
  - **private**: 厳密な内部実装のみ
  - readonly 修飾子の活用（LogRecord のプロパティ）

#### **9. バージョン・互換性管理分析（Version & Compatibility Analysis）**
- **対象**: composer.json、PHP バージョン要件、非推奨の扱い
- **方法**: composer.json の `require` と弃用マーク確認
- **発見**:
  - PHP >= 8.1 の要件を明記
  - PSR-3 準拠を `provide` で宣言
  - 過去バージョンとの互換性維持（@deprecated コメント）
  - 段階的な API 進化

---

## 🎯 抽出したコーディングスタイル

### A. ファイル構造

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

use OtherNamespace\Class;
// インポート順: アルファベット順

/**
 * Class description
 *
 * Long description if needed.
 *
 * @author Name <email@example.com>
 */
class MyClass extends AbstractClass implements MyInterface
{
    // ...
}
```

**ポイント**:
- 最初の行に `<?php declare(strict_types=1);`
- 空行
- ライセンスコメント（3行）
- 空行
- namespace 宣言
- use ステートメント（アルファベット順）
- 空行
- クラスドキュメント

### B. クラス宣言と構成

```php
class Logger implements LoggerInterface, ResettableInterface
{
    // 定数（キャメルケース）
    public const API = 3;
    private const RFC_5424_LEVELS = [/* ... */];

    // プロパティ（型付き）
    protected string $name;
    protected array $handlers;
    protected bool $microsecondTimestamps = true;
    protected DateTimeZone $timezone;
    private int $logDepth = 0;

    // __construct メソッド
    public function __construct(/* ... */) { }

    // public メソッド
    public function getName(): string { }
    public function pushHandler(HandlerInterface $handler): self { }

    // protected メソッド
    protected function write(LogRecord $record): void { }

    // private メソッド
    private function customErrorHandler(int $code, string $msg): bool { }

    // マジックメソッド
    public function __serialize(): array { }
}
```

**ポイント**:
- 定数 → プロパティ → __construct → public → protected → private の順序
- 全プロパティに型付与
- Fluent インターフェース使用（`return $this;`）

### C. メソッドのシグネチャと実装

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

/**
 * Adds a log record.
 *
 * @param  int                    $level    The logging level
 * @param  string                 $message  The log message
 * @param  mixed[]                $context  The log context
 * @return bool                   Whether the record has been processed
 *
 * @phpstan-param value-of<Level::VALUES>|Level $level
 */
public function addRecord(int|Level $level, string $message, array $context = [], 
    JsonSerializableDateTimeImmutable|null $datetime = null): bool
{
    // ...
}
```

**ポイント**:
- PHPDoc: `@param` は型、名前、説明
- Union Types と Nullable Types の明確な使用
- Named Arguments サポート
- PHPStan 用の追加型情報（`@phpstan-param`）
- 戻り値の型必須

### D. エラーハンドリング

```php
try {
    // メイン処理
    $stream = fopen($url, $this->fileOpenMode);
    if ($this->filePermission !== null) {
        @chmod($url, $this->filePermission);
    }
} finally {
    restore_error_handler();
}

if (!\is_resource($stream)) {
    $this->stream = null;
    throw new \UnexpectedValueException(
        sprintf('The stream or file "%s" could not be opened in append mode: ' . $this->errorMessage, $url)
    );
}
```

**ポイント**:
- `try-finally` で確実なリソース解放
- 具体的な例外クラスを使用（`\InvalidArgumentException`, `\UnexpectedValueException` など）
- エラーメッセージは情報豊富
- チェーン不可例外の直接スロー

### E. 型システムの活用

```php
// Union Types
public static function toMonologLevel(string|int|Level $level): Level { }

// Nullable Types
private DateTimeZone|null $timezone = null;

// Enum
enum Level: int {
    case Debug = 100;
    case Info = 200;
}

// readonly プロパティ
public function __construct(
    public readonly \DateTimeImmutable $datetime,
    public readonly string $channel,
    public readonly Level $level,
) { }

// Named Arguments
new LogRecord(
    datetime: $datetime ?? new JsonSerializableDateTimeImmutable(...),
    channel: $this->name,
    level: self::toMonologLevel($level),
    message: $message,
    context: $context,
    extra: [],
);
```

### F. テストの構造

```php
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

    #[DataProvider('levelProvider')]
    public function testLevel($level, $expected)
    {
        // ...
    }
}
```

**ポイント**:
- テストクラスは対応する実装クラスと同じ構造
- テストメソッド名: `test` + 実装メソッド名
- `@covers` タグで対応関係を明記
- PHPUnit attributes を活用（`#[DataProvider]` など）

---

## 💡 より良い抽出方法の提案

### 1. **静的解析ツールの活用**（推奨）

```bash
# PHPStan による型チェック分析
phpstan analyse --level 9 src/

# PHPCodeSniffer による PSR コンプライアンス確認
phpcs --standard=PSR12 src/

# PHP_CodeSniffer のカスタムルール作成で独自スタイル抽出
```

**メリット**: 自動化、一貫性、客観的指標

### 2. **AST（Abstract Syntax Tree）解析**

```php
// PHP Parser ライブラリを使用
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FirstFindingVisitor;

$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
$ast = $parser->parse($code);

// 各種パターンを検出：
// - メソッドの可視性分布
// - 型宣言の使用率
// - 例外の使用パターン
// - コメント密度
```

**メリット**: より詳細な構造解析、パターン検出の高精度

### 3. **機械学習ベースの分析**

```
訓練データ: Monolog の全 PHP ファイル
特徴量:
  - トークンタイプの分布
  - 制御フロー構造
  - 命名パターン（正規表現マッチング）
  - コメント密度
  - 型宣言率

出力: スタイル「スコアカード」
  - 予測: スタイル一貫性スコア
  - クラスタリング: 似たスタイルのファイル分類
```

**メリット**: 暗黙的なパターン検出、新規ファイルの自動評価可能

### 4. **メトリクスベース分析**

```
静的メトリクス:
  - サイクロマティック複雑度（CCN）
  - 行の平均長（AL）
  - ネスト深度（MaxND）
  - クラスあたりメソッド数（NOM）
  - メソッドあたり行数（LOCC）

例）Monolog の基準値設定:
  - CCN: 平均 4～6
  - AL: 平均 60～90 文字
  - MaxND: <= 4
  - NOM: 平均 8～12 メソッド/クラス
```

**メリット**: スタイル基準の数値化、品質評価

### 5. **比較分析アプローチ**

```
複数プロジェクトとの比較:
  - Laravel フレームワーク
  - Symfony フレームワーク
  - PHP-FIG（PSR基準）

差分抽出:
  - Monolog固有のスタイル
  - 業界標準パターン
  - アンチパターン
```

**メリット**: コンテキスト理解、標準との乖離検出

---

## 📊 提案：統合的なスタイル抽出パイプライン

```
┌─────────────────────────────────────────────────────┐
│ 1. 自動スキャン（PHPStan, PHPCS, PHPMetrics）      │
│    ↓                                               │
│ 2. AST 解析（PHP Parser）                         │
│    ↓                                               │
│ 3. メトリクス計算                                  │
│    ↓                                               │
│ 4. 機械学習による異常検出                         │
│    ↓                                               │
│ 5. 人間による検証・調整                           │
│    ↓                                               │
│ 6. ガイドライン書出・自動化ツール生成            │
└─────────────────────────────────────────────────────┘
```

---

## ✅ 本分析で抽出した「Monolog スタイルガイド」の構成

次のドキュメント（`MONOLOG_STYLE_GUIDE.md`）で以下を詳細化:

- [ ] ファイル構造と命名規則
- [ ] 型システムの活用基準
- [ ] クラス設計パターン
- [ ] メソッド実装テンプレート
- [ ] エラーハンドリング基準
- [ ] PHPDoc ドキュメンテーション基準
- [ ] テスト構造と命名
- [ ] チェックリスト（コード審査用）

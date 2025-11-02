# スタイルの観点と次元

コードスタイルは多次元的です。このドキュメントでは、抽出すべき具体的な項目を次元別に整理します。

## 次元の分類体系

```
メタ次元（Meta）：哲学・価値観
    ↑
マクロ次元（Macro）：アーキテクチャ・構造
    ↑
メゾ次元（Meso）：コンポーネント・クラス設計
    ↑
ミクロ次元（Micro）：構文・記法
```

---

## 1. ミクロ次元（Micro Level）

### 1.1 命名規則（Naming Conventions）

#### クラス名
```php
// パターン抽出項目
- PascalCase / snake_case / その他
- 抽象クラスのPrefix/Suffix（Abstract, Base）
- インターフェースのSuffix（Interface, ...able）
- トレイトのSuffix（Trait, Concern）
- 例外クラスの命名（...Exception）
- テストクラスの命名（...Test）

// 具体例
✓ UserRepository
✓ DatabaseConnectionInterface
✓ LoggableTrait
✗ user_repository
```

#### メソッド・関数名
```php
// パターン抽出項目
- camelCase / snake_case
- getter/setterの形式（get/set, is/has）
- 真偽値を返すメソッド（is, has, can, should）
- アクションメソッドの動詞選択（create vs make, fetch vs get）
- privateメソッドの命名（prefixの有無）

// 具体例
✓ getUserById($id)
✓ isActive()
✓ hasPermission($permission)
✓ canExecute()
```

#### 変数名
```php
// パターン抽出項目
- camelCase / snake_case
- 配列・コレクションの複数形使用
- ブーリアン変数のPrefix（is, has, should, can）
- ループ変数の慣習（$i, $j vs $index vs $key）
- 一時変数の命名（$tmp, $temp, or 意味のある名前）

// 具体例
✓ $userId
✓ $isValid
✓ $users (配列)
✓ $user (単一)
```

#### 定数
```php
// パターン抽出項目
- UPPER_SNAKE_CASE / PascalCase（クラス定数）
- グループ化の方法（クラス定数 vs 名前空間定数）
- マジックナンバー排除の徹底度

// 具体例
✓ const MAX_RETRY_COUNT = 3;
✓ const DEFAULT_TIMEOUT = 30;
```

### 1.2 フォーマット規則（Formatting）

#### インデント
```php
// 抽出項目
- スペース / タブ
- スペース幅（2 / 4 / 8）
- 配列・チェーンメソッドのインデント

// 検出方法
トークン解析で全ファイルの統計を取る
```

#### 括弧とスペース
```php
// Same-line vs Next-line braces
// スタイル A（K&R）
class Example {
    public function method() {
        if ($condition) {
            // ...
        }
    }
}

// スタイル B（Allman）
class Example
{
    public function method()
    {
        if ($condition)
        {
            // ...
        }
    }
}

// 抽出項目
- クラス宣言の括弧位置
- メソッド宣言の括弧位置
- 制御構文の括弧位置
- 演算子周りのスペース
- カンマ後のスペース
```

#### 配列記法
```php
// 抽出項目
- Short syntax [] vs Long syntax array()
- 複数行配列のフォーマット
- 末尾カンマの有無

// パターン1: Short syntax, trailing comma
$data = [
    'name' => 'John',
    'age' => 30,
];

// パターン2: Long syntax, no trailing comma
$data = array(
    'name' => 'John',
    'age' => 30
);
```

### 1.3 型システムの使用（Type System）

```php
// 抽出項目
- strict_types 宣言の使用率
- 引数の型宣言カバレッジ（%）
- 戻り値の型宣言カバレッジ（%）
- プロパティ型宣言の使用（PHP 7.4+）
- Union types の使用（PHP 8.0+）
- Nullable 型の表現方法（?string vs string|null）
- mixed 型の使用方針

// 例
declare(strict_types=1);

class User {
    private string $name;
    private ?int $age;
    
    public function __construct(string $name, ?int $age = null) {
        $this->name = $name;
        $this->age = $age;
    }
    
    public function getName(): string {
        return $this->name;
    }
}
```

### 1.4 ドキュメントブロック（Docblocks）

```php
// 抽出項目
- Docblock の記述率（全メソッド、publicのみ、など）
- @param, @return, @throws の記述徹底度
- 説明文の有無（タグのみ vs 説明付き）
- @var でのプロパティ型の補足
- 複雑な型のドキュメント方法（generics表現など）

/**
 * Retrieves a user by their unique identifier.
 *
 * @param int $id The user ID
 * @return User|null The user instance or null if not found
 * @throws DatabaseException If database connection fails
 */
public function getUserById(int $id): ?User
{
    // ...
}
```

---

## 2. メゾ次元（Meso Level）

### 2.1 メソッド設計

#### メソッドの長さ
```php
// 抽出項目
- 平均メソッド行数
- 中央値、標準偏差
- 長いメソッドの許容度（50行以上の割合）

// 統計例
平均: 12行
中央値: 8行
90パーセンタイル: 25行
→ 短いメソッドを好む文化
```

#### メソッドの複雑度
```php
// 抽出項目
- Cyclomatic Complexity の平均
- Cognitive Complexity の平均
- 深いネストの許容度（3レベル以上の割合）

// 評価
平均CC: 3.5
→ シンプルなロジックを好む
```

#### 引数の渡し方
```php
// パターン1: 位置引数（少数）
public function createUser(string $name, string $email) {}

// パターン2: 配列
public function createUser(array $userData) {}

// パターン3: オプションオブジェクト
public function createUser(UserCreationOptions $options) {}

// 抽出項目
- 平均引数数
- 4つ以上の引数を持つメソッドの割合
- 配列引数の使用頻度
- DTO/Options オブジェクトの使用頻度
```

#### 戻り値のパターン
```php
// 抽出項目
- null を返す頻度
- 空配列 vs null の使い分け
- 例外 vs エラー値 の選択
- 複数の戻り値（タプル的な配列）の使用

// パターンA: null 許容
public function findUser(int $id): ?User

// パターンB: 例外
public function getUser(int $id): User // throws UserNotFoundException

// パターンC: Result オブジェクト
public function findUser(int $id): Result
```

### 2.2 クラス設計

#### クラスの責務
```php
// 抽出項目
- 平均クラスサイズ（行数、メソッド数）
- 単一責任原則の徹底度
- 神クラス（God Class）の存在
- クラスあたりの依存数

// 指標
平均メソッド数: 8個
平均依存数: 3個
→ 小さく焦点を絞ったクラス設計
```

#### 継承 vs コンポジション
```php
// 抽出項目
- 継承階層の深さ（平均、最大）
- トレイトの使用頻度
- 委譲パターンの使用
- インターフェース分離の度合い

// 傾向分析
継承深度平均: 1.5（浅い）
トレイト使用率: 30%のクラス
→ コンポジション優先の文化
```

#### コンストラクタパターン
```php
// パターン1: Constructor Injection
class UserService {
    public function __construct(
        private UserRepository $repository,
        private Logger $logger
    ) {}
}

// パターン2: Named Constructor
class User {
    private function __construct(private string $name) {}
    
    public static function fromArray(array $data): self {
        return new self($data['name']);
    }
}

// パターン3: Builder
$user = User::builder()
    ->withName('John')
    ->withEmail('john@example.com')
    ->build();

// 抽出項目
- DI の徹底度
- Named constructor の使用頻度
- Builder パターンの採用
```

### 2.3 エラーハンドリング

```php
// 抽出項目
- 例外の使用頻度（全メソッドの何%が throws）
- 例外階層の設計（カスタム例外の体系）
- checked vs unchecked の感覚（ドキュメント義務）
- エラーログの方針

// パターンA: 積極的な例外
throw new UserNotFoundException("User {$id} not found");

// パターンB: nullとログ
if (!$user) {
    $this->logger->warning("User {$id} not found");
    return null;
}

// 傾向判定
例外使用率: 高 → 「例外駆動」の文化
例外使用率: 低 → 「nullチェック」の文化
```

### 2.4 不変性（Immutability）

```php
// 抽出項目
- readonly プロパティの使用率（PHP 8.1+）
- セッター無しクラスの割合
- 防御的コピーの実践
- バリューオブジェクトの採用

// パターン: イミュータブルオブジェクト
class Email {
    public function __construct(private readonly string $value) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException();
        }
    }
    
    public function getValue(): string {
        return $this->value;
    }
}
```

---

## 3. マクロ次元（Macro Level）

### 3.1 アーキテクチャパターン

#### レイヤー構造
```
// パターン1: レイヤードアーキテクチャ
src/
  ├── Presentation/   (Controllers, Views)
  ├── Application/    (Use Cases, Services)
  ├── Domain/         (Entities, Business Logic)
  └── Infrastructure/ (Database, External APIs)

// パターン2: 機能別（Vertical Slice）
src/
  ├── User/
  │   ├── UserController.php
  │   ├── UserService.php
  │   ├── UserRepository.php
  │   └── User.php
  └── Product/
      └── ...

// 抽出項目
- ディレクトリ構造の原則
- 依存の方向性ルール
- レイヤー間の境界の明確さ
```

#### 設計パターンの採用
```php
// 抽出項目
- Factory パターンの使用箇所
- Repository パターンの採用
- Strategy パターンの使用
- Observer/Event パターン
- Decorator パターン
- Command パターン

// 使用頻度統計
Repository: 全モデルで使用（100%）
Factory: 20%のクラスで使用
Strategy: 5%のクラスで使用
→ Repository は標準、他は適宜
```

### 3.2 依存関係管理

```php
// 抽出項目
- DIコンテナの使用（有無、種類）
- サービスロケーターの使用
- グローバル状態への依存度
- 循環依存の有無
- インターフェースベース設計の徹底度

// 分析
インターフェース抽出率: 80%
→ テスタビリティ・拡張性重視
```

### 3.3 名前空間戦略

```php
// 抽出項目
- 名前空間の深さ（平均、最大）
- ベンダー名の使用
- サブ名前空間の分割基準（機能 vs レイヤー）
- use文の並び順とグループ化

namespace App\Domain\User\Service;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Logger\LoggerInterface;

// パターン
1. 外部ライブラリ
2. 空行
3. 同一プロジェクト内（階層順）
```

### 3.4 ファイル構成

```php
// 抽出項目
- 1ファイル1クラス原則の徹底度
- ファイル名とクラス名の一致
- PSR-4準拠の度合い
- テストファイルの配置（同ディレクトリ vs tests/）

// 標準パターン
src/Domain/User/User.php
tests/Domain/User/UserTest.php
```

---

## 4. メタ次元（Meta Level）

### 4.1 設計哲学の抽出

#### パフォーマンス vs 可読性
```php
// 指標
- キャッシング機構の多用
- 最適化コメントの頻度
- プロファイリングコードの存在

// パターンA: パフォーマンス優先
$cached = $this->cache->get($key);
if ($cached !== null) {
    return $cached;
}
// ... 複雑な処理

// パターンB: 可読性優先
return $this->userRepository
    ->findByEmail($email)
    ->getProfile()
    ->getDisplayName();
```

#### 明示性 vs 簡潔性
```php
// 明示的
public function calculateTotalPrice(
    array $items,
    float $taxRate,
    float $discountRate
): float {
    $subtotal = $this->calculateSubtotal($items);
    $taxAmount = $this->calculateTax($subtotal, $taxRate);
    $discountAmount = $this->calculateDiscount($subtotal, $discountRate);
    return $subtotal + $taxAmount - $discountAmount;
}

// 簡潔
public function calculateTotalPrice($items, $tax, $discount): float {
    return array_sum($items) * (1 + $tax - $discount);
}

// 抽出: どちらの傾向が強いか
```

#### 防御的プログラミング
```php
// 抽出項目
- 入力検証の徹底度
- アサーションの使用
- 型チェックの頻度
- フェイルファストの実践

// 防御的スタイル
public function process(?array $data): void {
    if ($data === null) {
        throw new InvalidArgumentException('Data cannot be null');
    }
    if (empty($data)) {
        throw new InvalidArgumentException('Data cannot be empty');
    }
    // 処理
}

// 信頼するスタイル
public function process(array $data): void {
    // 型宣言を信頼して直接処理
}
```

### 4.2 文化的要素

#### コメント文化
```php
// 抽出項目
- コメント密度（行数比）
- WHYコメント vs WHATコメント
- TODOコメントの使用
- コードで語る vs コメントで説明

// パターンA: コメント豊富
/**
 * ユーザーの年齢を計算します。
 * うるう年を考慮して正確な日数で計算します。
 * タイムゾーンはシステムのデフォルトを使用します。
 */
public function calculateAge(): int

// パターンB: 自己説明的コード
public function calculateAgeConsideringLeapYears(): int
```

#### テスト文化
```php
// 抽出項目
- テストカバレッジ目標
- テスト種別（Unit vs Integration の比率）
- テストダブルの使用（Mock, Stub）
- TDD の実践度

// 分析
テストカバレッジ: 85%以上
Unit/Integration比: 7:3
→ テスト重視の文化
```

---

## 5. 横断的観点（Cross-cutting Concerns）

### 5.1 セキュリティ意識

```php
// 抽出項目
- SQL インジェクション対策（prepared statements）
- XSS 対策（エスケープ処理）
- CSRF 対策
- 認証・認可の実装パターン
- センシティブデータの扱い

// チェック
プリペアドステートメント使用率: 100%
→ セキュリティ意識高い
```

### 5.2 ログ・監視

```php
// 抽出項目
- ロギングライブラリ（Monolog等）
- ログレベルの使い分け
- 構造化ログの採用
- エラー追跡の仕組み

// パターン
$this->logger->info('User logged in', [
    'user_id' => $userId,
    'ip' => $request->getClientIp(),
]);
```

### 5.3 設定管理

```php
// 抽出項目
- 環境変数の使用
- 設定ファイルの形式（PHP, YAML, JSON）
- 多環境対応（dev, staging, prod）
- シークレット管理

// パターン
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbHost = config('database.host');
```

---

## まとめ：抽出チェックリスト

### 必須抽出項目（Must Have）
- ☑ 命名規則（4種: クラス、メソッド、変数、定数）
- ☑ フォーマット（インデント、括弧）
- ☑ 型宣言の使用方針
- ☑ ディレクトリ構造とレイヤー
- ☑ エラーハンドリング戦略
- ☑ 依存性注入パターン

### 推奨抽出項目（Should Have）
- ☑ Docblock の方針
- ☑ メソッド長/複雑度の基準
- ☑ 設計パターンの採用状況
- ☑ テスト方針
- ☑ ログ方針

### オプション抽出項目（Nice to Have）
- ☐ コメント文化の詳細
- ☐ パフォーマンス vs 可読性のトレードオフ
- ☐ 歴史的経緯

---

次のステップ: [03_problem_generation_strategy.md](./03_problem_generation_strategy.md)で、抽出したスタイルを問題に変換する方法を解説します。


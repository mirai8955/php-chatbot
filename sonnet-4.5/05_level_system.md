# レベルシステムとスキルツリー

PHPマスターへの段階的な成長パスを設計します。

## 1. レベルシステムの全体像

```
Level 0: 準備段階（PHP基礎確認）
  ↓
Level 1: 初級（Basic）- コーディング規約の習得
  ↓
Level 2: 中級（Intermediate）- クラス設計の基礎
  ↓
Level 3: 上級（Advanced）- 実践的な開発スキル
  ↓
Level 4: エキスパート（Expert）- アーキテクチャ設計
  ↓
Level 5: マスター（Master）- プロジェクトリーダーシップ
```

### 昇級条件

```yaml
level_up_requirements:
  level_1_to_2:
    min_problems_completed: 15
    min_average_score: 70
    required_skills: ['naming', 'formatting', 'types']
  
  level_2_to_3:
    min_problems_completed: 20
    min_average_score: 75
    required_skills: ['class_design', 'error_handling', 'testing']
  
  level_3_to_4:
    min_problems_completed: 25
    min_average_score: 80
    required_skills: ['patterns', 'refactoring', 'architecture']
  
  level_4_to_5:
    min_problems_completed: 30
    min_average_score: 85
    required_skills: ['design_decisions', 'tradeoffs', 'leadership']
```

---

## 2. Level 0: 準備段階（Prerequisites）

### 目的
プロジェクト固有の学習に入る前に、PHP基礎を確認

### 習得内容

#### PHP基本構文
- 変数、データ型
- 制御構造（if, for, while）
- 関数の定義と呼び出し
- 配列操作

#### オブジェクト指向の基礎
- クラスとオブジェクト
- プロパティとメソッド
- コンストラクタ
- 継承の基本

#### 診断テスト
```php
// 例題: 以下のコードの問題を指摘せよ

class User {
    var $name;
    
    function User($name) {
        $this->name = $name;
    }
    
    function get_name() {
        return $this->name;
    }
}

// 問題点:
// 1. var は古い書き方（visibility修飾子を使う）
// 2. PHP4スタイルのコンストラクタ（__construct を使う）
// 3. メソッド名が snake_case（camelCase にすべき）
```

### クリア条件
基礎診断テストで80%以上のスコア

---

## 3. Level 1: 初級（Basic）

### テーマ
「プロジェクトのコーディング規約に慣れる」

### 習得スキル

#### 1.1 命名規則マスター
```php
✓ 達成目標:
- クラス名をPascalCaseで書ける
- メソッド名をcamelCaseで書ける
- 定数をUPPER_SNAKE_CASEで書ける
- 真偽値変数にis/hasを付けられる

📝 問題例:
「以下の変数名をプロジェクト規約に従って修正せよ」
- user_name → $userName
- is_active → $isActive ✓（既に正しい）
- MAXCOUNT → MAX_COUNT
```

#### 1.2 フォーマット習得
```php
✓ 達成目標:
- 正しいインデント（4スペース）
- 括弧の適切な配置
- 演算子周りのスペース
- 配列のshort syntax使用

📝 問題例:
「以下のコードを適切にフォーマットせよ」
$user=array('name'=>'John','age'=>30);
↓
$user = [
    'name' => 'John',
    'age' => 30,
];
```

#### 1.3 型宣言の基礎
```php
✓ 達成目標:
- 引数に型を付けられる
- 戻り値に型を付けられる
- nullable型を理解している
- strict_types を宣言できる

📝 問題例:
「型宣言を追加せよ」
function getUserById($id) {
    return $this->users[$id] ?? null;
}
↓
function getUserById(int $id): ?User {
    return $this->users[$id] ?? null;
}
```

#### 1.4 Docblock記述
```php
✓ 達成目標:
- 基本的なDocblockを書ける
- @param, @return を正しく記述
- 説明文を簡潔に書ける

📝 問題例:
「適切なDocblockを追加せよ」

/**
 * ユーザーをIDで検索します。
 *
 * @param int $id ユーザーID
 * @return User|null 見つかったユーザー、または null
 */
public function getUserById(int $id): ?User {
    // ...
}
```

### Level 1 の問題セット例

```
総問題数: 20問

内訳:
- 命名規則: 6問（パターン認識2、修正4）
- フォーマット: 5問（修正4、コードレビュー1）
- 型宣言: 5問（追加3、修正2）
- Docblock: 4問（記述3、レビュー1）

合格ライン: 平均70点以上
```

### Level 1 完了時の状態
「プロジェクトのコードを見て、基本的なスタイルが分かる」

---

## 4. Level 2: 中級（Intermediate）

### テーマ
「クラスとメソッドを適切に設計できる」

### 習得スキル

#### 2.1 メソッド設計
```php
✓ 達成目標:
- メソッドを適切な長さに保てる（< 20行目安）
- 単一責任を意識できる
- 引数を適切な数に抑えられる（< 3個目安）
- 戻り値の型を適切に選べる

📝 問題例:
「以下の長大なメソッドを複数のメソッドに分割せよ」

public function processOrder($orderId) {
    // 50行の処理
}
↓
public function processOrder(int $orderId): void {
    $order = $this->validateOrder($orderId);
    $this->checkInventory($order);
    $this->calculateTotal($order);
    $this->sendConfirmation($order);
}

private function validateOrder(int $orderId): Order { ... }
private function checkInventory(Order $order): void { ... }
// ...
```

#### 2.2 クラス設計の基礎
```php
✓ 達成目標:
- 適切な responsibility を持つクラスを設計できる
- コンストラクタインジェクションを使える
- イミュータブルなValue Objectを作れる
- プロパティを適切に private にできる

📝 問題例:
「Email アドレスを表すValue Objectを実装せよ」

class Email {
    private readonly string $value;
    
    public function __construct(string $value) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email');
        }
        $this->value = $value;
    }
    
    public function getValue(): string {
        return $this->value;
    }
    
    public function getDomain(): string {
        return explode('@', $this->value)[1];
    }
}
```

#### 2.3 エラーハンドリング
```php
✓ 達成目標:
- 例外を適切に使い分けられる
- カスタム例外を定義できる
- エラーを適切にログに記録できる
- try-catch を適切に使える

📝 問題例:
「適切なエラーハンドリングを実装せよ」

class UserRepository {
    public function getUserById(int $id): User {
        $data = $this->database->query(
            'SELECT * FROM users WHERE id = :id',
            ['id' => $id]
        );
        
        if (!$data) {
            $this->logger->warning("User not found", ['id' => $id]);
            throw new UserNotFoundException("User {$id} not found");
        }
        
        return User::fromArray($data);
    }
}
```

#### 2.4 テストの基礎
```php
✓ 達成目標:
- 基本的なユニットテストを書ける
- テスタブルなコードを意識できる
- モックの基本を理解している

📝 問題例:
「以下のクラスのユニットテストを書け」

class UserServiceTest extends TestCase {
    public function test_createUser_success(): void {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class));
        
        $service = new UserService($repository);
        $user = $service->createUser('John', 'john@example.com');
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John', $user->getName());
    }
}
```

### Level 2 の問題セット例

```
総問題数: 25問

内訳:
- メソッド設計: 7問（分割3、設計2、レビュー2）
- クラス設計: 8問（実装5、レビュー3）
- エラーハンドリング: 5問（実装3、修正2）
- テスト: 5問（記述3、レビュー2）

合格ライン: 平均75点以上
```

### Level 2 完了時の状態
「シンプルな機能を適切なクラス設計で実装できる」

---

## 5. Level 3: 上級（Advanced）

### テーマ
「実践的な開発スキルを習得する」

### 習得スキル

#### 3.1 設計パターンの適用
```php
✓ 達成目標:
- Repository パターンを実装できる
- Factory パターンを使える
- Strategy パターンで柔軟性を確保できる
- 依存性逆転の原則を理解している

📝 問題例:
「通知システムを Strategy パターンで実装せよ」

interface NotificationStrategy {
    public function send(string $recipient, string $message): void;
}

class EmailNotification implements NotificationStrategy {
    public function send(string $recipient, string $message): void {
        // Email送信
    }
}

class SmsNotification implements NotificationStrategy {
    public function send(string $recipient, string $message): void {
        // SMS送信
    }
}

class NotificationService {
    public function __construct(
        private NotificationStrategy $strategy
    ) {}
    
    public function notify(string $recipient, string $message): void {
        $this->strategy->send($recipient, $message);
    }
}
```

#### 3.2 リファクタリング技術
```php
✓ 達成目標:
- レガシーコードを段階的に改善できる
- 抽出リファクタリングができる
- 重複を除去できる（DRY原則）
- テストを保ちながらリファクタリングできる

📝 問題例:
「以下のレガシーコードをリファクタリングせよ」
（300行のGodクラスを提示）
→ 責務ごとに分割、テスト追加、型安全性向上
```

#### 3.3 データベース設計とクエリ最適化
```php
✓ 達成目標:
- N+1問題を理解し回避できる
- Eager Loadingを適切に使える
- トランザクションを適切に使える
- インデックスの重要性を理解している

📝 問題例:
「以下のコードのN+1問題を解決せよ」

// Before: N+1問題
$users = $this->userRepository->findAll();
foreach ($users as $user) {
    echo $user->getProfile()->getName();  // 毎回クエリ
}

// After: Eager Loading
$users = $this->userRepository->findAllWithProfiles();
foreach ($users as $user) {
    echo $user->getProfile()->getName();  // クエリ1回のみ
}
```

#### 3.4 セキュリティ実装
```php
✓ 達成目標:
- SQLインジェクション対策（プリペアドステートメント）
- XSS対策（エスケープ処理）
- CSRF対策
- パスワードハッシュ化
- 認証・認可の実装

📝 問題例:
「セキュアなログイン機能を実装せよ」

class AuthenticationService {
    public function login(string $email, string $password): bool {
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user) {
            // タイミング攻撃対策
            password_hash('dummy', PASSWORD_BCRYPT);
            return false;
        }
        
        if (!password_verify($password, $user->getPasswordHash())) {
            $this->logger->warning('Failed login attempt', [
                'email' => $email,
                'ip' => $this->request->getClientIp()
            ]);
            return false;
        }
        
        $this->session->set('user_id', $user->getId());
        return true;
    }
}
```

### Level 3 の問題セット例

```
総問題数: 30問

内訳:
- 設計パターン: 8問（実装5、適用判断3）
- リファクタリング: 8問（実装6、レビュー2）
- DB・パフォーマンス: 7問（最適化5、設計2）
- セキュリティ: 7問（実装4、監査3）

合格ライン: 平均80点以上
```

### Level 3 完了時の状態
「実務レベルの機能を品質高く実装できる」

---

## 6. Level 4: エキスパート（Expert）

### テーマ
「アーキテクチャレベルの設計ができる」

### 習得スキル

#### 4.1 アーキテクチャ設計
```php
✓ 達成目標:
- レイヤードアーキテクチャを設計できる
- DDD（ドメイン駆動設計）の基礎を理解
- CQRS パターンを適用できる
- イベント駆動アーキテクチャを設計できる

📝 問題例:
「EC サイトの注文システムをクリーンアーキテクチャで設計せよ」

Directory Structure:
src/
  ├── Domain/
  │   ├── Order/
  │   │   ├── Order.php (Entity)
  │   │   ├── OrderStatus.php (Value Object)
  │   │   ├── OrderRepositoryInterface.php
  │   │   └── OrderService.php
  │   └── ...
  ├── Application/
  │   ├── UseCase/
  │   │   ├── CreateOrderUseCase.php
  │   │   └── CancelOrderUseCase.php
  │   └── ...
  ├── Infrastructure/
  │   ├── Persistence/
  │   │   └── DatabaseOrderRepository.php
  │   └── ...
  └── Presentation/
      └── Controller/
          └── OrderController.php
```

#### 4.2 パフォーマンス最適化
```php
✓ 達成目標:
- ボトルネックを特定できる
- キャッシング戦略を設計できる
- 非同期処理を導入できる
- メモリ使用量を最適化できる

📝 問題例:
「大量データ処理の最適化」

// Before: メモリオーバー
$users = $this->userRepository->findAll(); // 100万件
foreach ($users as $user) {
    $this->process($user);
}

// After: チャンク処理 + ジェネレータ
foreach ($this->userRepository->findAllChunked(1000) as $users) {
    foreach ($users as $user) {
        $this->process($user);
    }
}
```

#### 4.3 API設計
```php
✓ 達成目標:
- RESTful API を設計できる
- バージョニング戦略を理解
- エラーレスポンスを適切に設計
- ドキュメンテーション（OpenAPI）を書ける

📝 問題例:
「ユーザー管理APIを設計・実装せよ」

GET    /api/v1/users          - List users
GET    /api/v1/users/{id}     - Get user
POST   /api/v1/users          - Create user
PUT    /api/v1/users/{id}     - Update user
DELETE /api/v1/users/{id}     - Delete user

// レスポンス設計、エラーハンドリング、認証も含む
```

#### 4.4 テスト戦略
```php
✓ 達成目標:
- テストピラミッドを理解
- Integration テストを書ける
- E2E テストの方針を立てられる
- テストダブルを適切に使える

📝 問題例:
「注文システムの包括的なテストを設計せよ」

// Unit Test
OrderTest.php
OrderServiceTest.php

// Integration Test
OrderRepositoryIntegrationTest.php
OrderWorkflowTest.php

// E2E Test
OrderPlacementE2ETest.php
```

### Level 4 の問題セット例

```
総問題数: 30問

内訳:
- アーキテクチャ設計: 10問（設計7、レビュー3）
- パフォーマンス: 7問（最適化5、分析2）
- API設計: 7問（設計4、実装3）
- テスト戦略: 6問（設計4、実装2）

合格ライン: 平均85点以上
```

### Level 4 完了時の状態
「大規模システムのコアモジュールを設計・実装できる」

---

## 7. Level 5: マスター（Master）

### テーマ
「プロジェクトをリードし、技術的意思決定ができる」

### 習得スキル

#### 5.1 トレードオフの判断
```php
✓ 達成目標:
- 技術的な選択肢を評価できる
- ビジネス要求と技術要求のバランスを取れる
- 長期的な影響を考慮できる
- チームのスキルレベルを考慮できる

📝 問題例:
「以下のシナリオで最適なアプローチを選択し、理由を説明せよ」

シナリオ:
- パフォーマンス問題が発生している
- 解決策は3つ考えられる
  A) キャッシュ導入（複雑性+、即効性+）
  B) クエリ最適化（複雑性中、即効性中）
  C) インフラスケールアップ（コスト+、即効性+）

判断軸:
- 予算制約
- 開発期間
- チームスキル
- 保守性
```

#### 5.2 コードレビュー能力
```php
✓ 達成目標:
- 建設的なフィードバックができる
- 設計レベルの問題を指摘できる
- 代替案を提示できる
- チームの成長を促すレビューができる

📝 問題例:
「以下のPull Requestをレビューせよ」
（複雑な実装を提示）

期待するレビュー内容:
- 設計の問題点
- セキュリティリスク
- パフォーマンスへの影響
- 代替案の提示
- 良い点の指摘（ポジティブフィードバック）
```

#### 5.3 技術選定
```php
✓ 達成目標:
- ライブラリ/フレームワークを評価できる
- 技術スタックを提案できる
- 移行戦略を立てられる
- ドキュメントを作成できる

📝 問題例:
「新プロジェクトの技術スタックを提案せよ」

考慮事項:
- プロジェクト要件
- チーム構成
- 保守性
- コミュニティサポート
- ライセンス
```

#### 5.4 メンタリング
```php
✓ 達成目標:
- ジュニア開発者を指導できる
- 学習パスを提案できる
- ペアプログラミングを効果的に行える
- ナレッジシェアを推進できる

📝 問題例:
「新メンバーの3ヶ月育成プランを作成せよ」

Week 1-4: プロジェクトのコードベース理解
Week 5-8: 小機能の実装（レビュー重視）
Week 9-12: 中規模機能の独立実装
```

### Level 5 の問題セット例

```
総問題数: 20問（より深い思考を要求）

内訳:
- トレードオフ判断: 6問（シナリオ分析）
- コードレビュー: 5問（実践的レビュー）
- 技術選定: 5問（提案書作成）
- メンタリング: 4問（育成計画立案）

合格ライン: 平均90点以上
認定: プロジェクトメンバーによる推薦が必要
```

### Level 5 完了時の状態
「プロジェクトの技術的リーダーとして活躍できる」

---

## 8. スキルツリーの可視化

### 8.1 必須スキルと選択スキル

```
Level 1 (必須)
├─ 命名規則 ✓
├─ フォーマット ✓
├─ 型宣言 ✓
└─ Docblock ✓

Level 2 (必須)
├─ メソッド設計 ✓
├─ クラス設計 ✓
├─ エラーハンドリング ✓
└─ テスト基礎 ✓

Level 3 (必須 + 選択)
├─ 設計パターン ✓ (必須)
├─ リファクタリング ✓ (必須)
├─ DB最適化 ✓ (必須)
├─ セキュリティ ✓ (必須)
└─ 選択: フロントエンド統合 OR CLI開発 OR API設計

Level 4 (必須 + 選択)
├─ アーキテクチャ ✓ (必須)
├─ パフォーマンス ✓ (必須)
└─ 選択: マイクロサービス OR イベント駆動 OR DDD

Level 5 (実績ベース)
├─ 技術的意思決定の実績
├─ コードレビューの質
├─ チーム貢献
└─ プロジェクト推薦
```

### 8.2 進捗の可視化

```
ユーザーダッシュボード:

[あなたの現在地: Level 3 - Advanced]

進捗:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ 70%

習得済みスキル:
✓ 命名規則 (100%)
✓ フォーマット (95%)
✓ 型宣言 (100%)
✓ クラス設計 (85%)
🔄 設計パターン (60%) ← 現在取り組み中
⏳ リファクタリング (30%)
⏳ DB最適化 (20%)

次の目標:
1. 設計パターンを完了する（残り2問）
2. リファクタリング問題に挑戦する
3. Level 3 完了まであと10問

推奨問題:
→ 「Strategy パターンで決済システムを実装」
```

---

## 9. モチベーション維持の工夫

### 9.1 達成感の提供

**バッジシステム**:
```
🎖️ 「命名マスター」 - 命名問題を10問連続満点
🏆 「リファクタラー」 - 複雑なリファクタリング完了
⚡ 「パフォーマンスチューナー」 - 最適化問題で90点以上
🛡️ 「セキュリティガード」 - セキュリティ問題を全問クリア
```

### 9.2 ランキングと競争

```
今週のトップパフォーマー:
1. 田中さん - 15問クリア、平均92点
2. あなた - 12問クリア、平均87点
3. 佐藤さん - 10問クリア、平均85点

チーム平均: 82点
```

### 9.3 学習の記録

```
学習ログ:
2025-11-01: 「Repository パターン実装」 - 88点
2025-10-30: 「N+1問題の解決」 - 92点
2025-10-28: 「クラス設計レビュー」 - 78点

成長グラフ:
     📈
100 |         ●
 80 |     ●   ●   ●
 60 |   ●
 40 | ●
    +-------------------
     10/1  10/15  11/1
```

---

## まとめ

効果的なレベルシステムには:

1. **明確な成長パス**: 初心者からマスターまでの道筋が見える
2. **段階的な難易度**: 無理なく成長できるステップ
3. **実践的なスキル**: 実務で使える能力の習得
4. **モチベーション維持**: 達成感とフィードバック
5. **個別最適化**: ユーザーの進捗に応じた推奨

次のステップ: [06_implementation_guide.md](./06_implementation_guide.md)で、実装の具体的な方法を見ていきます。


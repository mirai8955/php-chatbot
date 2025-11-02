# 問題生成戦略

抽出したコードスタイルを、実践的な学習問題に変換する方法を解説します。

## 1. 問題生成の基本原則

### 1.1 学習目標の明確化

各問題は具体的な学習目標を持つべきです：

```
✓ 良い学習目標
「プロジェクトの Repository パターンを使って、ユーザー検索機能を実装できる」
「プロジェクトの命名規則に従って、メソッド名を適切に付けられる」

✗ 悪い学習目標
「PHP を書ける」
「コードが動く」
```

### 1.2 段階的な難易度設定

```
Level 1（基礎）：個別ルールの理解
  ↓
Level 2（応用）：複数ルールの組み合わせ
  ↓
Level 3（実践）：実際のユースケースへの適用
  ↓
Level 4（設計）：アーキテクチャレベルの判断
  ↓
Level 5（マスター）：トレードオフの意思決定
```

### 1.3 実践性の重視

```
✓ 実践的な問題
プロジェクトで実際に遭遇するシナリオ
既存コードの改善・拡張

✗ 机上の空論
文法クイズ的な問題
実務と乖離した例題
```

---

## 2. 問題形式のバリエーション

### 形式1: コード記述問題（Write Code）

**概要**: 仕様を満たすコードを書く

**難易度**: ★★★★★（最も難しい）

**例**:
```
【問題】
以下の仕様を満たす UserRepository クラスを実装してください。
プロジェクトのコーディング規約に従ってください。

仕様:
- ユーザーをIDで検索する機能
- ユーザーをメールアドレスで検索する機能
- データベース接続は constructor injection で受け取る
- 見つからない場合は null を返す
```

**評価観点**:
- 命名規則の遵守（25%）
- 型宣言の適切な使用（20%）
- DI パターンの実装（20%）
- Docblock の記述（15%）
- コードの可読性（20%）

### 形式2: コード修正問題（Fix Code）

**概要**: スタイル違反のコードを修正する

**難易度**: ★★★☆☆

**例**:
```php
【問題】
以下のコードはプロジェクトのスタイルに違反しています。
すべての違反を修正してください。

// 違反コード
class user_repository {
    private $db;
    
    function __construct($database) {
        $this->db = $database;
    }
    
    function get_user($id) {
        return $this->db->query("SELECT * FROM users WHERE id = $id");
    }
}
```

**模範解答**:
```php
class UserRepository {
    private DatabaseInterface $database;
    
    public function __construct(DatabaseInterface $database) {
        $this->database = $database;
    }
    
    public function getUserById(int $id): ?User {
        // プリペアドステートメント使用
        $result = $this->database->query(
            'SELECT * FROM users WHERE id = :id',
            ['id' => $id]
        );
        
        return $result ? User::fromArray($result) : null;
    }
}
```

**違反リスト**:
1. クラス名が snake_case（PascalCase にすべき）
2. 型宣言がない
3. メソッド名が snake_case（camelCase にすべき）
4. SQL インジェクション脆弱性
5. Docblock がない
6. visibility 修飾子がない

### 形式3: コードレビュー問題（Code Review）

**概要**: 複数の実装を比較し、どれが最も適切か選ぶ

**難易度**: ★★★★☆

**例**:
```php
【問題】
以下の3つの実装のうち、プロジェクトのスタイルに最も合致するものを選び、
理由を説明してください。

// 実装 A
public function createUser($name, $email) {
    $user = new User();
    $user->name = $name;
    $user->email = $email;
    $this->db->save($user);
    return $user;
}

// 実装 B
public function createUser(string $name, string $email): User {
    $user = new User($name, $email);
    $this->userRepository->save($user);
    return $user;
}

// 実装 C
public function createUser(UserCreationRequest $request): User {
    $user = User::create(
        $request->getName(),
        $request->getEmail()
    );
    
    return $this->userRepository->save($user);
}
```

**解答のポイント**:
- 型宣言の有無
- DI の使用
- イミュータビリティ
- DTO の使用

### 形式4: パターン認識問題（Pattern Recognition）

**概要**: プロジェクト内のコードから共通パターンを見つける

**難易度**: ★★☆☆☆

**例**:
```php
【問題】
以下は実際のプロジェクトコードからの抜粋です。
繰り返し現れるパターンを特定し、新しいクラスで同じパターンを適用してください。

// 既存コード1
class UserRepository {
    public function findById(int $id): ?User { ... }
    public function findByEmail(string $email): ?User { ... }
    public function save(User $user): void { ... }
}

// 既存コード2
class ProductRepository {
    public function findById(int $id): ?Product { ... }
    public function findBySku(string $sku): ?Product { ... }
    public function save(Product $product): void { ... }
}

// 問題: OrderRepository を同じパターンで実装してください
```

### 形式5: リファクタリング問題（Refactoring）

**概要**: レガシーコードをプロジェクトスタイルに合わせてリファクタリング

**難易度**: ★★★★★

**例**:
```php
【問題】
以下のレガシーコードを、プロジェクトの現在のスタイルに合わせて
リファクタリングしてください。機能は変えずに、設計を改善してください。

// レガシーコード（300行のメソッド）
public function processOrder($orderId) {
    // バリデーション
    if (empty($orderId)) {
        return ['error' => 'Invalid order ID'];
    }
    
    // データ取得
    $order = mysql_query("SELECT * FROM orders WHERE id = $orderId");
    
    // 在庫チェック
    // ... 50行のコード
    
    // 価格計算
    // ... 80行のコード
    
    // メール送信
    // ... 40行のコード
    
    // 決済処理
    // ... 100行のコード
    
    return ['success' => true];
}
```

**リファクタリングのゴール**:
- 単一責任原則の適用
- 適切なクラス分割
- 依存性の注入
- エラーハンドリングの改善
- テスタビリティの向上

---

## 3. 抽出スタイルから問題への変換マトリックス

### ミクロレベル → 問題例

| 抽出されたスタイル | 問題タイプ | 例 |
|---|---|---|
| 命名規則: camelCase | パターン認識 | 「以下の変数名を修正せよ」 |
| 型宣言: 徹底使用 | コード修正 | 「型宣言を追加せよ」 |
| Docblock: 詳細記述 | コード記述 | 「適切なDocblockを書け」 |
| 配列: Short syntax | コード修正 | 「array() を [] に変換せよ」 |

### メゾレベル → 問題例

| 抽出されたスタイル | 問題タイプ | 例 |
|---|---|---|
| メソッド長: 平均15行 | リファクタリング | 「長大メソッドを分割せよ」 |
| 引数: 3個まで | コードレビュー | 「どの設計が適切か」 |
| エラー: 例外優先 | コード記述 | 「例外を使って実装せよ」 |
| コンストラクタ: DI | コード記述 | 「DI を使ってクラスを作れ」 |

### マクロレベル → 問題例

| 抽出されたスタイル | 問題タイプ | 例 |
|---|---|---|
| アーキテクチャ: レイヤード | 設計問題 | 「適切なレイヤーに配置せよ」 |
| パターン: Repository | コード記述 | 「Repository を実装せよ」 |
| 依存管理: Interface重視 | コード記述 | 「Interface を定義せよ」 |
| ファイル構成: PSR-4 | 配置問題 | 「ファイルを正しく配置せよ」 |

### メタレベル → 問題例

| 抽出された哲学 | 問題タイプ | 例 |
|---|---|---|
| パフォーマンス重視 | トレードオフ問題 | 「最適化すべきか判断せよ」 |
| 防御的プログラミング | コード記述 | 「バリデーションを追加せよ」 |
| テスト重視文化 | テスト作成 | 「テストコードを書け」 |
| 明示性優先 | コードレビュー | 「より明示的な実装を選べ」 |

---

## 4. レベル別の問題設計

### Level 1: 基礎（Beginner）

**目標**: プロジェクトの基本的なコーディング規約を理解する

**問題例**:

#### 問題1-1: 命名規則クイズ
```
次のうち、プロジェクトの命名規則に従っているものはどれ？
A) class user_service {}
B) class UserService {}
C) class userService {}
D) class User_Service {}

答え: B
```

#### 問題1-2: フォーマット修正
```php
// このコードをプロジェクトのフォーマット規約に従って修正せよ
class Example{
public function test($value){
if($value>0){
return true;
}
return false;
}
}
```

#### 問題1-3: 型宣言追加
```php
// 適切な型宣言を追加せよ
public function getUserName($userId) {
    return $this->users[$userId]['name'];
}
```

### Level 2: 中級（Intermediate）

**目標**: 複数の規約を組み合わせて適用できる

**問題例**:

#### 問題2-1: シンプルクラス実装
```
【仕様】
Email アドレスを表すValue Objectクラスを実装してください。

要件:
- イミュータブルであること
- バリデーション付き
- プロジェクトのコーディング規約に従う
```

#### 問題2-2: リファクタリング（小規模）
```php
// 以下のコードをプロジェクトのスタイルに合わせてリファクタリングせよ
function get_user($id) {
    global $db;
    $result = $db->query("SELECT * FROM users WHERE id = $id");
    return $result[0];
}
```

#### 問題2-3: パターン適用
```
既存の UserRepository を参考に、ProductRepository を実装せよ。
同じパターンとスタイルを踏襲すること。
```

### Level 3: 上級（Advanced）

**目標**: 実務的なシナリオでスタイルを適用できる

**問題例**:

#### 問題3-1: 機能追加
```
【タスク】
既存のUserServiceに、以下の機能を追加してください。
既存コードのスタイルを崩さず、一貫性を保つこと。

新機能:
- ユーザーのパスワードリセット機能
- メール送信付き
- トークンの生成と検証
```

#### 問題3-2: バグ修正＋スタイル改善
```php
// 以下のコードにはバグとスタイル違反が複数あります。
// すべて修正してください。

class UserManager {
    function updateUser($id, $data) {
        $user = $this->getUser($id);
        $user->name = $data['name'];
        $user->email = $data['email'];
        $this->db->query(
            "UPDATE users SET name='{$user->name}', email='{$user->email}' WHERE id=$id"
        );
    }
}
```

#### 問題3-3: 設計判断
```
【シナリオ】
認証機能を実装する必要があります。
以下の2つの設計案のうち、プロジェクトのスタイルと哲学に
より合致するのはどちらですか？理由も説明してください。

案A: シンプルな実装（具象クラス直接使用）
案B: 抽象化された実装（Interface + DI）
```

### Level 4: エキスパート（Expert）

**目標**: アーキテクチャレベルの設計をスタイルに沿って行える

**問題例**:

#### 問題4-1: 新機能の設計
```
【タスク】
通知システムを新たに追加します。
プロジェクトのアーキテクチャとスタイルに従って、
以下を設計・実装してください：

- クラス構成
- ディレクトリ配置
- インターフェース定義
- 主要クラスの実装
- テストコード

要件:
- メール、SMS、プッシュ通知をサポート
- 非同期処理対応
- 再試行メカニズム
- ログ記録
```

#### 問題4-2: レガシーコードの全面リファクタリング
```
【タスク】
添付のレガシーモジュール（500行）を、
プロジェクトの現在のスタイルとアーキテクチャに合わせて
完全にリファクタリングしてください。

制約:
- 既存のAPIは変更しない（後方互換性）
- テストカバレッジ80%以上
- 設計パターンの適切な適用
```

### Level 5: マスター（Master）

**目標**: プロジェクトのスタイルを進化させる判断ができる

**問題例**:

#### 問題5-1: スタイル改善提案
```
【タスク】
プロジェクトの既存スタイルガイドを分析し、
改善提案を作成してください。

含めるべき内容:
- 現状の問題点（3つ以上）
- 改善案（具体的なコード例付き）
- 移行戦略（段階的な適用方法）
- メリット・デメリット分析
```

#### 問題5-2: トレードオフの意思決定
```
【シナリオ】
パフォーマンス問題が発生しています。
以下の3つのアプローチを評価し、
プロジェクトの哲学に最も合う解決策を選んで実装してください。

A) キャッシュを導入（複雑性増加）
B) クエリを最適化（SQL複雑化）
C) 非同期処理化（アーキテクチャ変更）

判断基準:
- プロジェクトの価値観との整合性
- 長期的な保守性
- チームのスキルセット
```

---

## 5. 問題の自動生成アプローチ

### 5.1 テンプレートベース生成

```yaml
# 問題テンプレート例
problem_template:
  type: "code_fix"
  level: 2
  pattern: "naming_convention_violation"
  
  template: |
    以下のコードの命名規則違反を修正してください。
    
    ```php
    {code_with_violation}
    ```
  
  violation_patterns:
    - snake_case_class
    - UPPER_CASE_method
    - camelCase_constant
  
  evaluation:
    - check: class_name_pascal_case
      weight: 40
    - check: method_name_camel_case
      weight: 40
    - check: constant_upper_snake_case
      weight: 20
```

### 5.2 既存コードからの生成

```python
# 擬似コード
def generate_pattern_recognition_problem(project_codebase):
    # 1. 同じパターンのクラスを複数抽出
    similar_classes = find_similar_classes(
        project_codebase,
        similarity_threshold=0.8
    )
    
    # 2. パターンを抽象化
    common_pattern = extract_common_pattern(similar_classes)
    
    # 3. 問題文生成
    problem = f"""
    以下は{project_name}の既存クラスです。
    共通パターンを見つけ、新しいクラス{new_class_name}で同じパターンを適用してください。
    
    既存例:
    {format_code_examples(similar_classes[:2])}
    
    実装してください: {new_class_name}
    """
    
    return problem
```

### 5.3 違反コードの自動生成

```python
# 擬似コード
def generate_code_fix_problem(style_rules):
    # 1. 正しいコードをサンプルから選択
    correct_code = select_random_good_code(project_codebase)
    
    # 2. 意図的に違反を注入
    violated_code = inject_violations(
        correct_code,
        num_violations=3,
        violation_types=['naming', 'type_hint', 'formatting']
    )
    
    # 3. 問題と解答を生成
    return {
        'problem': f"以下のコードを修正してください:\n{violated_code}",
        'answer': correct_code,
        'violations': list_violations(violated_code, correct_code)
    }
```

---

## 6. 問題の品質保証

### チェックリスト

#### 内容の妥当性
- ☑ 実際のプロジェクトで遭遇しうるシナリオか？
- ☑ 学習目標が明確か？
- ☑ 難易度が適切か？
- ☑ 模範解答が存在するか？

#### スタイルの正確性
- ☑ プロジェクトの実際のスタイルを反映しているか？
- ☑ 一般的なベストプラクティスとプロジェクト固有スタイルを混同していないか？
- ☑ 最新のスタイルガイドに基づいているか（古い慣習ではないか）？

#### 評価の公平性
- ☑ 評価基準が明確か？
- ☑ 部分点の付け方が定義されているか？
- ☑ 複数の正解がある場合、すべてカバーしているか？

---

## まとめ

効果的な問題生成には：

1. **多様な形式**: 記述、修正、レビュー、パターン認識、リファクタリング
2. **段階的難易度**: 基礎から応用、実践、設計、マスターまで
3. **実践性**: プロジェクトで実際に使えるスキル
4. **測定可能性**: 明確な評価基準

次のステップ: [04_evaluation_criteria.md](./04_evaluation_criteria.md)で、解答の評価方法を詳しく見ていきます。


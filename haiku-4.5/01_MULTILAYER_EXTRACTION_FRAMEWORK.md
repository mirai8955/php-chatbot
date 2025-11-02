# 多層的コードスタイル抽出フレームワーク

## 概要

このドキュメントは、PHPプロジェクトから3層のスタイルを体系的に抽出するためのフレームワークを提供します。

---

## 第1層：マクロレベル抽出フレームワーク

### 1.1 ディレクトリ構造分析

**目的**：プロジェクトがどのように機能ごとに分割されているかを把握

**抽出項目**：

```yaml
directory_patterns:
  structure_type: "機能ベース / レイヤーベース / ドメインベース"
  
  functional_grouping:
    - path: "src/Handler/"
      role: "リクエスト/レスポンス処理"
      responsibility_scope: "ハンドラー関連の全実装を集約"
    
    - path: "src/Exception/"
      role: "エラー表現"
      responsibility_scope: "プロジェクト全体で発生しうる例外を定義"
  
  depth_and_breadth:
    - max_directory_depth: 3
    - pattern: "単一責任の明確な分離"
    - consistency: "命名規則の一貫性（PascalCase、camelCase）"
```

**具体例（Guzzleプロジェクト）**：

```
src/
├── Client.php (メインエントリ)
├── Handler/ (HTTP通信の詳細処理)
│   ├── CurlHandler.php
│   ├── StreamHandler.php
│   └── MockHandler.php
├── Middleware.php (処理のパイプライン)
├── Cookie/ (Cookie管理)
├── Exception/ (例外定義)
└── Processor/ (オプション処理)
```

**学習ポイント**：
- 「Handler」という名称でHTTP通信を抽象化している
- 複数の実装（Curl、Stream、Mock）を提供する設計
- Middlewareパターンでリクエスト/レスポンス処理を挿入可能にしている

### 1.2 クラス・インターフェース設計パターン

**目的**：クラス設計の「このプロジェクトでの流儀」を理解

**抽出項目**：

```yaml
class_patterns:
  interface_adoption:
    percentage: "85%"  # インターフェース定義の比率
    pattern: "インターフェースと実装の分離が徹底的"
    naming_convention: "XInterface + X の組み合わせ"
  
  trait_usage:
    percentage: "40%"
    pattern: "共通機能の抽出に使用"
    example: "ClientTrait - クライアント共通機能"
  
  inheritance_vs_composition:
    preference: "composition over inheritance"
    reason: "柔軟性とテスト性の向上"
  
  abstract_classes:
    usage: "基本実装を提供する場合に限定"
    example: "BaseException → ClientException, ServerException"
```

**具体例**：

```php
// Guzzleの設計パターン
interface ClientInterface {
    public function request($method, $uri, array $options = []): ResponseInterface;
}

class Client implements ClientInterface {
    use ClientTrait;  // 共通機能をTrait化
}

// ハンドラーパターン
interface CurlFactoryInterface {
    public function create(RequestInterface $request): EasyHandle;
}

class CurlFactory implements CurlFactoryInterface {
    // 実装
}
```

**学習ポイント**：
- インターフェースで契約を明確にしている
- Traitで共通実装を安全に共有している
- 各ハンドラーが同じインターフェースを実装することで、交換可能にしている

### 1.3 依存関係・DI設計

**目的**：クラス間の関係性がどう設計されているか把握

**抽出項目**：

```yaml
dependency_management:
  di_pattern: "コンストラクタインジェクション"
  
  di_container_usage: false  # 明示的なコンテナは使わない
  
  dependency_direction:
    - from: "高レベル"
      to: "低レベル（インターフェース経由）"
    - rule: "具象クラスに依存しない"
  
  factory_usage:
    percentage: "25%"
    pattern: "複雑なオブジェクト生成時に限定"
  
  service_locator: "使用していない"
```

**学習ポイント**：
- 明示的なDIコンテナを使わず、手作業でDIしている（シンプル）
- インターフェース経由で依存性を注入している

### 1.4 エラーハンドリング全体方針

**目的**：エラーに対する考え方がどう統一されているか把握

**抽出項目**：

```yaml
error_handling_strategy:
  exception_hierarchy:
    root: "GuzzleException"
    categories:
      - ClientException: "4xx系エラー"
      - ServerException: "5xx系エラー"
      - RequestException: "リクエスト固有エラー"
      - ConnectException: "接続エラー"
  
  return_values:
    null_return: "最小限（ほぼ使わない）"
    exception_throwing: "エラーは例外で返す"
    
  result_wrapping:
    pattern: "Response/Resultオブジェクトで成功/失敗を表現"
```

**学習ポイント**：
- エラーは例外で返すという明確な方針
- 例外クラスの階層化で、キャッチの粒度を調整可能にしている

---

## 第2層：ミッドレベル抽出フレームワーク

### 2.1 メソッド設計パターン

**目的**：個々のメソッド実装の「このプロジェクトでの流儀」を把握

**抽出項目**：

```yaml
method_design:
  method_size:
    average_lines: 15
    median_lines: 12
    max_lines: 50
    pattern: "小さく、責任を絞った設計"
  
  method_naming:
    pattern: "camelCase（PHPの標準）"
    prefixes:
      - "get": "データ取得、副作用なし"
      - "set": "データ設定"
      - "is": "真偽値チェック"
      - "has": "存在チェック"
      - "create": "新規オブジェクト生成"
    
  visibility:
    public_percentage: 30%
    protected_percentage: 15%
    private_percentage: 55%
    pattern: "最小権限の原則"
```

**具体例**：

```php
// 小さく責任を絞ったメソッド
public function request($method, $uri, array $options = []): ResponseInterface {
    $request = $this->createRequest($method, $uri);
    $request = $this->applyOptions($request, $options);
    return $this->handler->handle($request);
}

// 適切なメソッド名の使い分け
public function hasHandler(): bool { return isset($this->handler); }
public function getHandler(): HandlerInterface { return $this->handler; }
private function validateRequest(RequestInterface $req): void { }
```

**学習ポイント**：
- 平均15行という「適度に短い」設計
- メソッド名で何をするのかが明確
- privateを55%も使っている（密結合を避ける）

### 2.2 パラメータ設計パターン

**目的**：メソッドのパラメータがどう設計されているか把握

**抽出項目**：

```yaml
parameter_patterns:
  option_array_pattern:
    description: "多くのオプションをサポートする場合、連想配列を1つのパラメータに統合"
    usage_percentage: 60%
    example: "request($method, $uri, $options = [])"
  
  builder_pattern:
    description: "複雑なオブジェクト生成時、ビルダー的メソッドチェーン"
    usage_percentage: 30%
    example: "client->setOption()->setHeader()->request()"
  
  parameter_type_hints:
    adoption_percentage: 85%
    pattern: "型ヒントを積極的に使用"
    example: "public function request(string $method, string $uri): ResponseInterface"
  
  variadic_parameters:
    usage: "配列の代わりに、可変長パラメータはあまり使わない"
```

**具体例**：

```php
// オプション配列パターン
public function request(
    $method,
    $uri,
    array $options = []  // ← 多くのオプションを1つの配列で表現
): ResponseInterface

// ビルダーパターンもサポート
public function setOption($key, $value): self {
    $this->options[$key] = $value;
    return $this;
}

// 型ヒントの積極的な使用
public function request(
    string $method,
    $uri,
    array $options = []
): ResponseInterface
```

**学習ポイント**：
- オプション配列パターンで、拡張性とパラメータ数のバランスを取っている
- 型ヒントを積極的に採用している

### 2.3 戻り値設計パターン

**目的**：メソッドの戻り値がどう設計されているか把握

**抽出項目**：

```yaml
return_value_patterns:
  return_type_hints:
    adoption_percentage: 75%
    pattern: "戻り値型を積極的に指定"
  
  null_returns:
    frequency: "10%"
    pattern: "nullを返すのは最小限"
    rationale: "明示的に『存在しない』を表現する場合のみ"
  
  result_objects:
    pattern: "成功/失敗を戻り値オブジェクトで表現することもある"
    usage_percentage: 20%
  
  arrays_vs_objects:
    arrays: "設定やオプションの集まり"
    objects: "ビジネスロジック、実体的なデータ"
  
  exception_throwing:
    percentage: 70%
    pattern: "エラーケースは例外を投げる"
```

**具体例**：

```php
// パターン1：型ヒント付き戻り値
public function request(string $method, string $uri): ResponseInterface {
    // ResponseInterfaceを必ず返す、nullは返さない
}

// パターン2：配列で設定を返す
public function getOptions(): array {
    return $this->options;
}

// パターン3：オブジェクトで複雑なデータを返す
public function getTransferStats(): TransferStats {
    return new TransferStats(/*...*/);
}

// パターン4：エラーは例外を投げる
public function request(...): ResponseInterface {
    if ($error) {
        throw new RequestException('...');
    }
}
```

**学習ポイント**：
- 戻り値の型ヒント率が75%と高い
- nullの使用を最小化している
- エラーは例外で表現する明確な方針

### 2.4 アクセス修飾子の使い分け

**目的**：クラスの内部構造がどの程度隠蔽されているか把握

**抽出項目**：

```yaml
visibility_strategy:
  private_methods: 55%
  protected_methods: 15%
  public_methods: 30%
  
  principle: "最小権限の原則（最初はprivate、必要ならprotected/public）"
  
  protected_usage:
    scenario: "サブクラスでのオーバーライドを想定する場合のみ"
    percentage: 15%
  
  public_usage:
    scenario: "外部インターフェースとなる機能のみ"
    percentage: 30%
```

**学習ポイント**：
- privateを55%使っている（実装を隠蔽）
- protectedは15%のみ（サブクラスでのオーバーライドを制限）
- 隠蔽を重視する設計方針が明確

---

## 第3層：マイクロレベル抽出フレームワーク

### 3.1 インデント・フォーマティング

**目的**：行レベルの一貫性を把握

**抽出項目**：

```yaml
indentation:
  unit: 4  # 4スペース
  tool_enforcement: "PSR-2/PSR-12標準に基づく"
  
  indentation_rules:
    - class_body: "4スペス"
    - method_body: "4スペス + method内は8スペース"
    - array_elements: "4スペス単位"
    - continuation_lines: "8スペス"

brace_style:
  style: "K&R style (Egyptian brackets)"
  opening_brace: "同じ行の末尾"
  closing_brace: "次の行の開始"
  
  examples:
    if_statement: |
      if ($condition) {
          // body
      }
    
    class_definition: |
      class MyClass {
          // members
      }

line_length:
  maximum: 120
  soft_limit: 100
  rationale: "モダンなモニタに対応、ただし80字時代の遺産も考慮"
```

**具体例**：

```php
// K&R style + 4スペースインデント
if ($condition) {
    $result = someFunction(
        $param1,
        $param2,
        $param3
    );
    return $result;
}

// クラス定義
class MyClass {
    private $property;
    
    public function method() {
        if ($this->property) {
            return true;
        }
    }
}
```

### 3.2 スペーシング・区切り文字

**目的**：微細な空白文字の規則を把握

**抽出項目**：

```yaml
spacing:
  operator_spacing:
    rule: "二項演算子の前後に1スペース"
    examples:
      - "$a = $b"
      - "$x > 5"
      - "$result = $a && $b"
  
  comma_spacing:
    rule: "カンマの後ろに1スペース、前にはなし"
    example: "function($a, $b, $c)"
  
  function_call:
    rule: "関数名と開き括弧の間にスペースなし"
    example: "someFunction($param)"
  
  control_structures:
    rule: "control文と開き括弧の間に1スペース"
    example: "if ($condition) { }"
  
  array_spacing:
    rule: "短配列は1行、長配列は複数行"
```

**具体例**：

```php
// スペーシングの例
$result = functionName($param1, $param2, $param3);

if ($condition && $otherCondition) {
    $array = ['key1' => 'value1', 'key2' => 'value2'];
}

$longArray = [
    'key1' => 'value1',
    'key2' => 'value2',
    'key3' => 'value3',
];
```

### 3.3 文字列表現

**目的**：文字列クォートの使い分けを把握

**抽出項目**：

```yaml
quotes:
  single_quotes:
    usage: "変数補間が不要な場合"
    percentage: 60%
    example: "'simple string'"
  
  double_quotes:
    usage: "変数補間が必要な場合、またはシンプルな文字列でも統一的に"
    percentage: 40%
    example: "\"string with $variable\""
  
  heredoc_nowdoc:
    usage: "多行文字列の場合"
    frequency: "10%"
```

**具体例**：

```php
// シングルクォート（補間なし）
$url = 'https://example.com/api';

// ダブルクォート（補間あり）
$message = "Error code: {$code}";

// Heredoc（多行）
$html = <<<EOT
    <div>
        <p>$variable</p>
    </div>
    EOT;
```

### 3.4 コメント・ドキュメンテーション

**目的**：ドキュメンテーションのスタイルを把握

**抽出項目**：

```yaml
comments:
  docblock_style: "PHPDocコメント"
  
  docblock_coverage:
    public_methods: 95%
    private_methods: 30%
    properties: 80%
  
  docblock_format:
    example: |
      /**
       * 短い説明
       *
       * 詳細説明（必要に応じて）
       *
       * @param string $name 説明
       * @param int $count 説明
       * @return ResponseInterface 説明
       * @throws RequestException エラー情報
       */
  
  inline_comments:
    style: "// コメント"
    usage: "複雑なロジックの説明に限定"
    percentage: 15%
```

**具体例**：

```php
/**
 * HTTPリクエストを送信します
 *
 * このメソッドはハンドラースタックを通してリクエストを処理し、
 * レスポンスを返します。
 *
 * @param string $method HTTP メソッド (GET, POST など)
 * @param string $uri リクエストURI
 * @param array $options リクエストオプション
 *
 * @return ResponseInterface レスポンスオブジェクト
 * @throws RequestException リクエスト失敗時
 *
 * @example
 *   $response = $client->request('GET', 'https://example.com');
 */
public function request($method, $uri, array $options = []): ResponseInterface {
    // 実装
}
```

---

## 抽出チェックリスト

```markdown
### マクロレベル
- [ ] ディレクトリ構造を分析し、モジュール分割方針を記録
- [ ] クラス/インターフェース設計パターンを5〜10個抽出
- [ ] 依存関係の方向性（DI vs Service Locator）を把握
- [ ] エラーハンドリング戦略を文書化

### ミッドレベル
- [ ] メソッドサイズの統計（平均、中央値、最大値）
- [ ] メソッド命名規則の標本を10個以上収集
- [ ] パラメータ設計パターン（オプション配列 vs ビルダー）の比率
- [ ] 戻り値型ヒント採用率を測定
- [ ] visibility（public/protected/private）の比率を計算

### マイクロレベル
- [ ] インデント文字を決定（スペース数またはタブ）
- [ ] ブレースのスタイルを確認（K&R vs Allman）
- [ ] 行の最大長を測定
- [ ] スペーシングルール（演算子、カンマ、括弧）を抽出
- [ ] シングル/ダブルクォートの使い分けを確認
- [ ] PHPDoc形式の統一性を確認
```

---

## 次のステップ

このフレームワークに基づいて、実装ツールと問題出題・評価への応用を作成します。

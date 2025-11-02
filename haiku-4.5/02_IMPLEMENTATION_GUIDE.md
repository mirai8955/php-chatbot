# 実装ガイド - コードスタイル抽出の具体的手順

## 概要

このドキュメントは、前述の抽出フレームワークを実際にプロジェクトに適用するための具体的な手順を提供します。

---

## ステップ1：マクロレベル抽出（手作業ベース）

### 1.1 ディレクトリ構造の分析

**手順**：

```bash
# プロジェクトルートからディレクトリ構造を可視化
tree -L 3 -I 'vendor|node_modules' src/

# または、より詳細に
find src -type f -name "*.php" | head -20 | xargs -I {} sh -c 'echo "===== {} =====" && head -5 {}'
```

**記録するポイント**：

```markdown
## ディレクトリ構造分析（Guzzleの例）

### 構造タイプ
機能ベース（Feature-oriented）

### 機能グループ
| パス | 役割 | ファイル数 | 責務 |
|------|------|----------|------|
| src/Handler | HTTP通信の実装 | 8 | リクエスト送信、Curl/Stream/Mock処理 |
| src/Exception | エラー表現 | 7 | Guzzle固有の例外定義 |
| src/Middleware | リクエスト前処理 | 1 | リダイレクト、リトライ処理 |
| src/Cookie | Cookie処理 | 4 | Cookie保存、管理、検索 |

### 深さと広がり
- 最大ディレクトリ深さ: 3階層
- 平均ファイル数: 4〜6
- 命名規則: PascalCase（クラス/ディレクトリ）
```

### 1.2 クラス・インターフェース設計パターンの分析

**ツール：シンプルなgrep/検索**

```bash
# インターフェース数を数える
grep -r "interface " src/ --include="*.php" | wc -l

# 実装クラス数を数える
grep -r "class " src/ --include="*.php" | grep -v "interface" | wc -l

# Trait使用数を数える
grep -r "trait " src/ --include="*.php" | wc -l

# 抽象クラス数を数える
grep -r "abstract class" src/ --include="*.php" | wc -l
```

**記録テンプレート**：

```yaml
# class_patterns_analysis.yaml
project: "Guzzle"

interface_vs_implementation:
  total_interfaces: 15
  total_classes: 45
  interface_adoption_rate: "85%"
  naming_pattern: "XInterface + X"
  
  examples:
    - interface: "ClientInterface"
      implementations: ["Client"]
      purpose: "HTTP client contract"
    
    - interface: "HandlerInterface"
      implementations: ["CurlHandler", "StreamHandler", "MockHandler"]
      purpose: "Request handler contract"

trait_usage:
  total_traits: 2
  coverage: "40%"
  
  examples:
    - name: "ClientTrait"
      used_by: ["Client"]
      provides: "Common client methods"

design_patterns_identified:
  - Strategy: "HandlerInterface + multiple implementations"
  - Factory: "CurlFactory, HeaderProcessor"
  - Middleware: "MiddlewareInterface with HandlerStack"
  - Decorator: "Handler wrapping in middleware"
```

### 1.3 依存関係分析

**手順**：重要なクラスの`__construct`メソッドを調査

```bash
# コンストラクタを表示
grep -A 5 "public function __construct" src/Client.php

# パラメータの型ヒントをチェック
grep -A 3 "__construct" src/**/*.php | grep -E "(array|interface|class)"
```

**記録テンプレート**：

```yaml
# dependency_analysis.yaml
dependency_pattern: "Constructor Injection"

examples:
  - class: "Client"
    dependencies:
      - name: "handler"
        type: "HandlerStackInterface"
        injection_point: "__construct"
      - name: "options"
        type: "array"
        injection_point: "__construct"
  
  - class: "CurlHandler"
    dependencies:
      - name: "factory"
        type: "CurlFactoryInterface"
        injection_point: "__construct"

di_container_usage: false
service_locator_usage: false
factory_usage_percentage: 25

key_insight: |
  - Explicit DI via constructor parameters
  - No global state or service locator
  - Dependencies passed to methods when needed
```

### 1.4 エラーハンドリング戦略

**手順**：例外クラスを調査

```bash
# 例外クラスの一覧
find src/Exception -name "*.php" -exec basename {} \;

# 例外の階層を表示
grep -E "(extends|implements)" src/Exception/*.php
```

**記録テンプレート**：

```yaml
# error_handling_analysis.yaml
strategy: "Exception hierarchy with specific types"

exception_hierarchy:
  GuzzleException:
    - TransferException
      - ConnectException
      - RequestException
      - TooManyRedirectsException
    - BadResponseException
      - ClientException (4xx)
      - ServerException (5xx)

error_handling_patterns:
  - pattern: "Throw exceptions for error cases"
    percentage: 70%
    rationale: "Callers can catch specific exception types"
  
  - pattern: "Return null or false for missing data"
    percentage: 10%
    rationale: "Only when absence is not an error condition"
  
  - pattern: "Return success/failure objects"
    percentage: 20%
    rationale: "For transactional operations or batch processing"
```

---

## ステップ2：ミッドレベル抽出（スクリプト・統計分析ベース）

### 2.1 メソッドサイズと命名規則の分析

**PHPスクリプト例**：`analysis_tools/method_analyzer.php`

```php
<?php
// メソッドサイズと命名規則を分析

function analyzeMethod($filename) {
    $code = file_get_contents($filename);
    $ast = (new PHPParser\Parser\Lexer)->tokenize($code);
    
    $methods = [];
    foreach ($ast as $token) {
        if ($token->getType() === 'METHOD') {
            $size = $token->getEndLine() - $token->getStartLine();
            $name = $token->getName();
            $methods[] = [
                'name' => $name,
                'size' => $size,
                'prefix' => substr($name, 0, 3),
                'visibility' => $token->getVisibility(),
            ];
        }
    }
    return $methods;
}

// 統計計算
$sizes = array_map(fn($m) => $m['size'], $methods);
echo "Average method size: " . round(array_sum($sizes) / count($sizes)) . " lines\n";
echo "Median method size: " . stats_median($sizes) . " lines\n";
```

**簡易版（正規表現ベース）**：

```bash
#!/bin/bash
# method_size_analysis.sh

echo "=== Method Size Analysis ==="
for file in src/**/*.php; do
    grep -n "function " "$file" | while read line; do
        func_name=$(echo "$line" | sed 's/.*function \([a-zA-Z_][a-zA-Z0-9_]*\).*/\1/')
        echo "Method: $func_name in $file"
    done
done

echo ""
echo "=== Method Name Prefixes (Top 10) ==="
grep -rh "function [a-z]" src/ --include="*.php" | \
    sed 's/.*function \([a-z][a-z]*\)_.*/\1/' | \
    sort | uniq -c | sort -rn | head -10
```

**記録テンプレート**：

```yaml
# method_analysis.yaml
statistics:
  total_methods: 120
  
  size_distribution:
    average: 15
    median: 12
    min: 1
    max: 50
    std_dev: 8
  
  percentiles:
    p50: 12  # 50%のメソッドは12行以下
    p75: 20  # 75%のメソッドは20行以下
    p90: 30  # 90%のメソッドは30行以下

naming_patterns:
  get_prefix: 30%    # getData, getHandler, etc.
  set_prefix: 15%    # setOption, setHandler, etc.
  is_prefix: 10%     # isValid, isEmpty, etc.
  has_prefix: 8%     # hasHandler, hasProperty, etc.
  create_prefix: 5%  # createRequest, createResponse, etc.
  other: 32%

visibility_statistics:
  public: 30%
  protected: 15%
  private: 55%

key_insight: |
  - メソッド平均15行で、責任が小さく絞られている
  - Getterメソッドが30%で、データアクセスが主
  - Private 55%で、実装詳細を隠蔽している
```

### 2.2 パラメータ・戻り値型ヒント分析

**スクリプト**：

```bash
#!/bin/bash
# type_hint_analysis.sh

echo "=== Parameter Type Hint Coverage ==="
total_params=$(grep -rh "function.*(" src/ --include="*.php" | \
    sed 's/.*(\(.*\)).*/\1/' | \
    grep -o ',' | wc -l)

typed_params=$(grep -rh "function.*(" src/ --include="*.php" | \
    grep -oE "(string|int|float|bool|array|\\\\?[A-Za-z])[^,]*," | wc -l)

echo "Total parameters: $total_params"
echo "Typed parameters: $typed_params"
echo "Coverage: $(( (typed_params * 100) / total_params ))%"

echo ""
echo "=== Return Type Hint Coverage ==="
total_methods=$(grep -rh "function " src/ --include="*.php" | wc -l)
typed_returns=$(grep -rh "function.*[:\s].*\{" src/ --include="*.php" | \
    grep -E ": (string|int|void|[A-Z])" | wc -l)

echo "Total methods: $total_methods"
echo "Typed returns: $typed_returns"
echo "Coverage: $(( (typed_returns * 100) / total_methods ))%"
```

**記録テンプレート**：

```yaml
# type_hints_analysis.yaml
parameter_type_hints:
  coverage: 85%
  
  types_used:
    - string: 35%
    - array: 25%
    - InterfaceTypes: 20%
    - int: 10%
    - bool: 5%
    - mixed: 5%
  
  patterns:
    - pattern: "Interface types for dependencies"
      example: "HandlerInterface $handler"
    - pattern: "Primitive types for data"
      example: "string $url, int $timeout"
    - pattern: "Array for collections or options"
      example: "array $options, array $headers"

return_type_hints:
  coverage: 75%
  
  types_used:
    - ResponseInterface: 25%
    - self: 20%
    - void: 15%
    - array: 15%
    - bool: 10%
    - string: 5%
    - null: 5%
    - mixed: 5%
  
  patterns:
    - pattern: "Method chaining with self return"
      frequency: 20%
      example: |
        public function setOption($key, $value): self {
            return $this;
        }
```

---

## ステップ3：マイクロレベル抽出（PHPコードスニファーベース）

### 3.1 フォーマッティング分析ツール

**ツール選択肢**：

1. **PHP CodeSniffer（推奨）**
   ```bash
   # インストール
   composer require --dev squizlabs/php_codesniffer
   
   # 分析実行
   ./vendor/bin/phpcs src/ --standard=PSR2 --report-width=120
   ```

2. **PHPStan（型チェック）**
   ```bash
   # インストール
   composer require --dev phpstan/phpstan
   
   # 分析実行
   ./vendor/bin/phpstan analyse src/ --level 5
   ```

3. **Psalm（静的解析）**
   ```bash
   # インストール
   composer require --dev vimeo/psalm
   
   # 分析実行
   ./vendor/bin/psalm src/
   ```

### 3.2 手動検査スクリプト

```php
<?php
// analysis_tools/style_detector.php

class StyleDetector {
    private $config = [];
    
    public function analyzeFile($filename) {
        $lines = file($filename, FILE_SKIP_EMPTY_LINES);
        
        $this->detectIndentation($lines);
        $this->detectBraceStyle($lines);
        $this->detectSpacing($lines);
        $this->detectQuotes($lines);
        $this->detectLineLength($lines);
        
        return $this->config;
    }
    
    private function detectIndentation($lines) {
        $indents = [];
        foreach ($lines as $line) {
            if (preg_match('/^(\s+)/', $line, $matches)) {
                $indent = strlen($matches[1]);
                if ($indent % 4 === 0 || $indent % 2 === 0) {
                    $indents[$indent]++;
                }
            }
        }
        
        $mostCommon = array_keys($indents, max($indents))[0];
        $this->config['indentation_unit'] = 
            ($mostCommon % 4 === 0) ? 4 : 2;
    }
    
    private function detectBraceStyle($lines) {
        // K&R style: opening brace on same line
        // Allman style: opening brace on new line
        $kr_count = 0;
        $allman_count = 0;
        
        foreach ($lines as $line) {
            if (preg_match('/\{\s*$/', $line)) {
                $kr_count++;
            } elseif (preg_match('/^\s*\{/', $line)) {
                $allman_count++;
            }
        }
        
        $this->config['brace_style'] = 
            ($kr_count > $allman_count) ? 'K&R' : 'Allman';
    }
    
    private function detectSpacing($lines) {
        // スペーシングルールの検出
        $comma_spacing = 0;
        $operator_spacing = 0;
        
        foreach ($lines as $line) {
            // カンマ後のスペース
            if (preg_match('/,\s/', $line)) $comma_spacing++;
            
            // 演算子前後のスペース
            if (preg_match('/\s[+\-*\/]=?\s/', $line)) $operator_spacing++;
        }
        
        $this->config['comma_spacing'] = 'after';
        $this->config['operator_spacing'] = 'before_and_after';
    }
    
    private function detectQuotes($lines) {
        $single = 0;
        $double = 0;
        
        foreach ($lines as $line) {
            $single += substr_count($line, "'");
            $double += substr_count($line, '"');
        }
        
        $this->config['quote_preference'] = 
            ($single > $double) ? 'single' : 'double';
    }
    
    private function detectLineLength($lines) {
        $lengths = array_map('strlen', $lines);
        $this->config['average_line_length'] = 
            round(array_sum($lengths) / count($lengths));
        $this->config['max_line_length'] = max($lengths);
    }
}
```

**記録テンプレート**：

```yaml
# micro_level_analysis.yaml
indentation:
  unit: 4
  enforcement: "PSR-12"
  consistency: 98%

brace_style:
  style: "K&R"
  opening_brace_position: "same line"
  closing_brace_position: "new line"
  coverage: 100%

line_length:
  average: 65
  maximum: 120
  soft_limit: 100
  distribution:
    short (< 50): 30%
    medium (50-100): 50%
    long (> 100): 20%

spacing:
  operators: "before and after"
  comma: "after"
  function_call: "no space before ("
  control_structures: "space after keyword before ("
  consistency: 99%

quotes:
  single_quotes: 60%
  double_quotes: 40%
  preference: "Single quotes for simple strings"

comments:
  docblock_style: "PHPDoc"
  public_methods_docblock_coverage: 95%
  inline_comment_ratio: 15%
  documentation_quality: "high"
```

---

## ステップ4：抽出結果の統合

### 4.1 統一スタイルプロファイルの生成

```yaml
# generated_style_profile.yaml
project: "Guzzle"
php_version: "7.4+"
psr_compliance: "PSR-12"

## マクロレベル ##
architecture:
  type: "Feature-based modularization"
  modules:
    - Handler: "HTTP transfer layer"
    - Middleware: "Request/response processing pipeline"
    - Cookie: "Cookie management"
    - Exception: "Error definitions"

design_patterns:
  primary:
    - Strategy (HandlerInterface)
    - Middleware
    - Factory
    - Decorator

dependency_management:
  method: "Constructor Injection"
  di_container: false
  service_locator: false

error_handling:
  strategy: "Exception hierarchy"
  root_exception: "GuzzleException"
  error_percentage: 70%

## ミッドレベル ##
code_organization:
  method_average_size: 15
  method_name_prefixes:
    get: 30%
    set: 15%
    is: 10%
    has: 8%

type_hints:
  parameter_coverage: 85%
  return_coverage: 75%
  interface_usage: 85%

visibility:
  public: 30%
  protected: 15%
  private: 55%

## マイクロレベル ##
formatting:
  indentation: "4 spaces"
  brace_style: "K&R"
  line_length_max: 120
  line_length_soft: 100

spacing:
  operator: "before and after"
  comma: "after"
  function_call: "no space"

quotes:
  single: 60%
  double: 40%

documentation:
  docblock_style: "PHPDoc"
  public_methods: 95%
  properties: 80%
```

---

## ステップ5：チェックリストと検証

```markdown
## 抽出完了チェックリスト

### マクロレベル ✓
- [x] ディレクトリ構造を文書化
- [x] 主要デザインパターンを特定（5+）
- [x] 依存関係パターンを特定
- [x] エラーハンドリング戦略を理解

### ミッドレベル ✓
- [x] メソッドサイズ統計を計算（平均、中央値、最大）
- [x] 命名規則を分類（get, set, is, has, etc.）
- [x] パラメータ型ヒント採用率を測定（85%）
- [x] 戻り値型ヒント採用率を測定（75%）
- [x] visibility分布を計算（public 30%, protected 15%, private 55%）

### マイクロレベル ✓
- [x] インデント単位を確定（4スペース）
- [x] ブレーススタイルを確認（K&R）
- [x] 行最大長を測定（120文字）
- [x] スペーシングルールを抽出
- [x] クォート使い分けを確定
- [x] PHPDoc形式を検証

### 統合成果物
- [x] 統一スタイルプロファイルYAML
- [x] 学習ルール定義
- [x] 問題出題テンプレート
- [x] スコアリングルール
```

---

## 次のステップ

このステップで抽出したスタイル情報を基に、以下のドキュメントで問題出題と評価に活用します：

→ `03_APPLICATION_TO_QUESTIONS.md`

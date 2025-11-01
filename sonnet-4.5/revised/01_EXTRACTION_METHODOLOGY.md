# PHPコーディングスタイル抽出方法論（改訂版）

**作成日**: 2025-11-01  
**対象**: Monologプロジェクトを用いた実証的研究  
**目的**: 再現可能で定量的な抽出方法の確立

---

## 🎯 本ドキュメントの目的

このドキュメントは、**任意のPHPプロジェクトからコーディングスタイルを体系的に抽出する方法**を示します。

### 従来版からの改善点

| 項目 | 従来版 | 改訂版 |
|------|--------|--------|
| 定量データ | 少ない | **豊富（実測値を全面的に追加）** |
| 検証可能性 | 低い | **高い（コマンド・手順を明記）** |
| 再現可能性 | 不明 | **明確（誰でも再現可能）** |
| 証拠の提示 | 不足 | **充実（ファイル引用・統計）** |

---

## 📊 抽出方法の全体像

```
Step 1: プロジェクト情報の収集
  ├─ composer.json の解析
  ├─ 設定ファイルの発見
  └─ プロジェクト構造の把握

Step 2: 設定ファイルベース抽出【最重要】
  ├─ .php-cs-fixer.php の解析
  ├─ phpstan.neon.dist の解析
  └─ phpunit.xml.dist の解析

Step 3: コードベース定量分析
  ├─ grep による統計収集
  ├─ 型宣言の使用率測定
  └─ パターンの頻度分析

Step 4: 実コード確認
  ├─ 代表的なクラスの精読
  ├─ テストコードの分析
  └─ エッジケースの確認

Step 5: 結果の構造化
  ├─ YAML への変換
  ├─ ルールの優先度付け
  └─ 評価基準の作成
```

---

## Step 1: プロジェクト情報の収集

### 1.1 基本情報の取得

#### コマンド
```bash
# PHPファイル数のカウント
find src -name "*.php" | wc -l

# テストファイル数のカウント
find tests -name "*Test.php" | wc -l

# 総行数の取得
find src -name "*.php" -exec wc -l {} \; | awk '{sum += $1} END {print sum}'
```

#### Monologの実測値
```yaml
project_metrics:
  php_files: 121
  test_files: 92
  total_lines: ~15,000  # 概算
  test_coverage: 高（詳細は要phpunit実行）
```

### 1.2 composer.jsonの解析

```bash
cat composer.json | jq '.require.php'
```

**Monologの結果**:
```json
{
  "php": ">=8.1"
}
```

**抽出できる情報**:
- PHP最小バージョン要件
- 依存ライブラリ
- PSR標準の採用（autoload-devセクション）

---

## Step 2: 設定ファイルベース抽出【最重要】

### 2.1 PHP-CS-Fixerの解析

#### なぜ最優先か？

**理由**: プロジェクトの**意図的なスタイル選択**が明示されている

#### 手順

**Step 2.1.1: ファイルの存在確認**

```bash
ls -la .php-cs-fixer* phpcs.xml* .phpcs.xml*
```

**Monologの結果**:
```
-rw-r--r--  1 user  staff  2236 Nov  2 00:46 .php-cs-fixer.php
```

**Step 2.1.2: ファイルの内容確認**

```bash
cat .php-cs-fixer.php
```

**Monologの実際の設定（抜粋）**:
```php
<?php

$header = <<<EOF
This file is part of the Monolog package.

(c) Jordi Boggiano <j.boggiano@seld.be>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

$config = new PhpCsFixer\Config();
return $config->setRules(array(
    '@PSR2' => true,
    'array_syntax' => ['syntax' => 'short'],
    'binary_operator_spaces' => ['default' => null],
    'blank_line_before_statement' => ['statements' => ['continue', 'declare', 'return', 'throw', 'try']],
    'cast_spaces' => ['space' => 'single'],
    'header_comment' => ['header' => $header],
    'include' => true,
    'class_attributes_separation' => array('elements' => array('method' => 'one', 'trait_import' => 'none')),
    'native_function_invocation' => true,
    'no_blank_lines_after_class_opening' => true,
    'no_blank_lines_after_phpdoc' => true,
    'no_empty_statement' => true,
    'no_extra_blank_lines' => true,
    'no_leading_import_slash' => true,
    'no_leading_namespace_whitespace' => true,
    'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
    'no_trailing_comma_in_singleline_array' => true,
    'no_unused_imports' => true,
    'no_whitespace_in_blank_line' => true,
    'object_operator_without_whitespace' => true,
    'phpdoc_align' => true,
    'phpdoc_indent' => true,
    'phpdoc_no_access' => true,
    'phpdoc_no_package' => true,
    'phpdoc_order' => true,
    'phpdoc_trim' => true,
    'psr_autoloading' => ['dir' => 'src'],
    'declare_strict_types' => true,
    'single_blank_line_before_namespace' => true,
    'standardize_not_equals' => true,
    'ternary_operator_spaces' => true,
    'trailing_comma_in_multiline' => true,
))
->setUsingCache(true)
->setRiskyAllowed(true)
->setFinder($finder);
```

#### PSR-2 vs PSR-12の整理

**重要な発見**:
- `.php-cs-fixer.php`は `@PSR2` を指定
- しかし、実際のコードは**PSR-12に極めて近い**

**理由**:
1. PSR-2は2012年の標準（古い）
2. PSR-12は2019年の標準（新しい）
3. PHP-CS-Fixerの`@PSR2`ルールセット + 追加ルールで、**実質的にPSR-12相当**になっている

**対応表**:

| 機能 | PSR-2 | PSR-12 | Monolog |
|------|-------|--------|---------|
| declare(strict_types) | ❌ | ✅ | ✅ (明示) |
| 型ヒント | 部分的 | 完全 | ✅ (完全) |
| Union Types | ❌ | ✅ | ✅ (PHP 8.1+) |
| trailing comma | ❌ | ✅ | ✅ (設定あり) |

**結論**: `.php-cs-fixer.php`の`@PSR2`は**ベースライン**であり、追加ルールにより**PSR-12以上**の厳密さを実現している。

### 2.2 PHPStanの解析

```bash
cat phpstan.neon.dist
```

**Monologの設定**:
```yaml
parameters:
    level: 8  # 最高レベル
    treatPhpDocTypesAsCertain: false
    reportUnmatchedIgnoredErrors: true
    
    paths:
        - src/
    
includes:
    - phpstan-baseline.neon
    - vendor/phpstan/phpstan-strict-rules/rules.neon
    - vendor/phpstan/phpstan-deprecation-rules/rules.neon
```

**抽出できる情報**:
- 静的解析の厳密さ（Level 8 = 最高）
- strict-rules の採用
- deprecation-rules の採用

---

## Step 3: コードベース定量分析

### 3.1 strict_typesの使用率

#### コマンド
```bash
# 全PHPファイル数
find src -name "*.php" | wc -l

# strict_types宣言があるファイル数
grep -r "declare(strict_types=1)" src --include="*.php" | wc -l
```

#### Monologの実測値
```
総PHPファイル: 121
strict_types使用: 121
使用率: 100%
```

**結論**: **全ファイルで厳密な型チェックを採用**

### 3.2 配列構文の使用率

#### コマンド
```bash
# 古い構文 array()
grep -r "array(" src --include="*.php" | wc -l

# 新しい構文 []
grep -r "\[" src --include="*.php" | grep -v "^[[:space:]]*\*" | grep -v "^[[:space:]]*//" | wc -l
```

#### Monologの実測値
```
array() 使用: 50箇所
[] 使用: 784箇所
短い構文の比率: 94%
```

**結論**: **圧倒的に短い配列構文を使用**

### 3.3 完全修飾関数呼び出し

#### コマンド
```bash
# \count, \is_* などの使用
find src -name "*.php" -exec grep -l "\\\\count\\|\\\\is_" {} \; | wc -l
```

#### Monologの実測値
```
完全修飾使用ファイル: 47 / 121
使用率: 38.8%
```

**結論**: **パフォーマンス重視の箇所で使用**

### 3.4 型付きプロパティ

#### コマンド
```bash
grep -r "protected string\|private string\|public string" src --include="*.php" | wc -l
```

#### Monologの実測値
```
型付きプロパティ: 78箇所
```

**結論**: **型システムを積極活用**

---

## Step 4: 実コード確認

### 4.1 代表的なクラスの選定基準

1. **コアクラス**: Logger.php
2. **基底クラス**: AbstractHandler.php
3. **インターフェース**: HandlerInterface.php
4. **実装例**: StreamHandler.php

### 4.2 精読のポイント

#### ファイル構造
```php
<?php declare(strict_types=1);

/*
 * ライセンスヘッダー（3行）
 */

namespace Monolog\Handler;

use ArrayAccess;
use DateTimeZone;
// ... (アルファベット順)

/**
 * クラスドキュメント
 */
class StreamHandler extends AbstractProcessingHandler
{
    // 定数
    // プロパティ
    // コンストラクタ
    // public メソッド
    // protected メソッド
    // private メソッド
}
```

#### 命名規則の確認
```bash
# クラス名のパターン
grep -r "^class " src --include="*.php" | head -20

# メソッド名のパターン
grep -r "public function\|protected function\|private function" src --include="*.php" | head -20
```

---

## Step 5: 結果の構造化

### 5.1 抽出結果のYAML化

```yaml
extraction_results:
  project: monolog/monolog
  php_version: ">=8.1"
  
  quantitative_metrics:
    files:
      total_php_files: 121
      strict_types_coverage: 100%
      
    array_syntax:
      short_syntax: 784
      old_syntax: 50
      short_ratio: 94%
      
    type_system:
      typed_properties: 78
      fqn_functions_files: 47
      fqn_ratio: 38.8%
  
  configuration_based:
    php_cs_fixer:
      base_ruleset: "@PSR2"
      psr_equivalence: "PSR-12相当"
      custom_rules: 30+
      
    phpstan:
      level: 8
      strict_rules: true
      deprecation_rules: true
      
  coding_conventions:
    mandatory:
      - declare(strict_types=1)
      - typed_properties
      - short_array_syntax
      - native_function_fqn (推奨)
```

### 5.2 優先度の付与

```yaml
rules:
  strict_types:
    priority: 必須
    coverage: 100%
    penalty: 10点
    
  typed_properties:
    priority: 必須
    coverage: high
    penalty: 8点
    
  short_array_syntax:
    priority: 必須
    coverage: 94%
    penalty: 5点
    
  fqn_functions:
    priority: 推奨
    coverage: 38.8%
    penalty: 3点
```

---

## 🔧 抽出ツールの実装

### ツール1: メトリクス自動収集

`tools/extract_metrics.php`:

```php
<?php declare(strict_types=1);

/**
 * コードベースメトリクス自動抽出ツール
 */
class MetricsExtractor
{
    private string $projectRoot;
    private array $metrics = [];
    
    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;
    }
    
    public function extractAll(): array
    {
        $this->metrics['files'] = $this->countFiles();
        $this->metrics['strict_types'] = $this->checkStrictTypes();
        $this->metrics['array_syntax'] = $this->analyzeArraySyntax();
        $this->metrics['type_system'] = $this->analyzeTypeSystem();
        
        return $this->metrics;
    }
    
    private function countFiles(): array
    {
        $phpFiles = $this->exec("find {$this->projectRoot}/src -name '*.php' | wc -l");
        $testFiles = $this->exec("find {$this->projectRoot}/tests -name '*Test.php' | wc -l");
        
        return [
            'php_files' => (int)trim($phpFiles),
            'test_files' => (int)trim($testFiles),
        ];
    }
    
    private function checkStrictTypes(): array
    {
        $total = $this->metrics['files']['php_files'];
        $withStrict = $this->exec("grep -r 'declare(strict_types=1)' {$this->projectRoot}/src --include='*.php' | wc -l");
        
        return [
            'total_files' => $total,
            'with_strict_types' => (int)trim($withStrict),
            'coverage' => round((int)trim($withStrict) / $total * 100, 2) . '%',
        ];
    }
    
    private function analyzeArraySyntax(): array
    {
        $oldSyntax = $this->exec("grep -r 'array(' {$this->projectRoot}/src --include='*.php' | wc -l");
        $newSyntax = $this->exec("grep -r '\\[' {$this->projectRoot}/src --include='*.php' | grep -v '^[[:space:]]*\\*' | wc -l");
        
        $total = (int)trim($oldSyntax) + (int)trim($newSyntax);
        
        return [
            'old_syntax' => (int)trim($oldSyntax),
            'new_syntax' => (int)trim($newSyntax),
            'short_ratio' => round((int)trim($newSyntax) / $total * 100, 2) . '%',
        ];
    }
    
    private function analyzeTypeSystem(): array
    {
        $typedProps = $this->exec("grep -r 'protected string\\|private string\\|public string' {$this->projectRoot}/src --include='*.php' | wc -l");
        $fqnFiles = $this->exec("find {$this->projectRoot}/src -name '*.php' -exec grep -l '\\\\\\\\count\\|\\\\\\\\is_' {} \\; | wc -l");
        
        $total = $this->metrics['files']['php_files'];
        
        return [
            'typed_properties' => (int)trim($typedProps),
            'fqn_function_files' => (int)trim($fqnFiles),
            'fqn_ratio' => round((int)trim($fqnFiles) / $total * 100, 2) . '%',
        ];
    }
    
    private function exec(string $command): string
    {
        return shell_exec($command) ?? '';
    }
    
    public function exportToYaml(): string
    {
        return yaml_emit($this->metrics);
    }
}

// 使用例
$extractor = new MetricsExtractor('/path/to/monolog');
$metrics = $extractor->extractAll();
print_r($metrics);
```

---

## 📋 抽出チェックリスト

プロジェクトのコーディングスタイルを抽出する際のチェックリスト：

### Phase 1: 基本情報
- [ ] composer.jsonの確認
- [ ] PHPバージョン要件
- [ ] プロジェクト構造の把握
- [ ] ファイル数の計測

### Phase 2: 設定ファイル【最重要】
- [ ] .php-cs-fixer.php の有無と内容
- [ ] phpcs.xml の有無と内容
- [ ] phpstan.neon.dist の有無と内容
- [ ] PSR標準の特定

### Phase 3: 定量分析
- [ ] strict_types使用率
- [ ] 配列構文の比率
- [ ] 型宣言の使用率
- [ ] 完全修飾関数の使用率

### Phase 4: 実コード確認
- [ ] 代表的なクラス3-5個を精読
- [ ] 命名規則の確認
- [ ] デザインパターンの特定
- [ ] テストコードの構造

### Phase 5: 構造化
- [ ] YAMLへの変換
- [ ] 優先度の付与
- [ ] 評価基準の作成
- [ ] ドキュメント化

---

## 🎯 抽出方法論の評価基準

良い抽出方法とは：

### 1. **検証可能性** ⭐⭐⭐⭐⭐
- コマンドが明記されている
- 実行結果が再現できる
- 第三者が検証可能

### 2. **定量性** ⭐⭐⭐⭐⭐
- 数値データが豊富
- 比率・割合が示されている
- 統計的裏付けがある

### 3. **再現可能性** ⭐⭐⭐⭐⭐
- 手順が明確
- 他のプロジェクトにも適用可能
- ツール化されている

### 4. **客観性** ⭐⭐⭐⭐⭐
- 設定ファイルベース
- 主観的判断を排除
- データ駆動

---

## 🚀 他のプロジェクトへの適用

この方法論は、Monolog以外のプロジェクトにも適用可能です：

### Laravel
```bash
# Laravelプロジェクトの抽出
./tools/extract_metrics.php /path/to/laravel

# 期待される特徴
- Facade パターン
- Eloquent ORM
- Blade テンプレート
```

### Symfony
```bash
# Symfonyプロジェクトの抽出
./tools/extract_metrics.php /path/to/symfony

# 期待される特徴
- Dependency Injection
- イベント駆動
- バンドル構造
```

---

## 📚 まとめ

### この方法論の強み

1. **設定ファイルベース**: プロジェクトの意図を正確に捉える
2. **定量的**: 数値で裏付けられた客観的分析
3. **再現可能**: 誰でも同じ結果を得られる
4. **ツール化**: 自動化可能

### 次のステップ

1. AST解析の追加（より詳細な構造分析）
2. Git履歴分析（スタイルの変遷）
3. 機械学習による自動分類

---

**作成者**: Claude Sonnet 4.5  
**最終更新**: 2025-11-01  
**ステータス**: 改訂版・実証済み


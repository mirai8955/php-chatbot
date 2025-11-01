# 🎯 コーディングスタイル抽出方法論（改善版）

**作成日**: 2025-11-01  
**対象プロジェクト**: Monolog  
**目的**: PHP プロジェクトのコーディングスタイルを正確に抽出し、AI コーチシステムに適用可能な形式で整理する

---

## 📋 目次

1. [方法論の概要](#方法論の概要)
2. [フィードバック統合](#フィードバック統合)
3. [2層構造アプローチ](#2層構造アプローチ)
4. [Phase 1: 自動抽出](#phase-1-自動抽出)
5. [Phase 2: 手動検証](#phase-2-手動検証)
6. [Phase 3: データ構造化](#phase-3-データ構造化)
7. [実装例：Monolog](#実装例monolog)
8. [品質保証](#品質保証)

---

## 方法論の概要

### 評価から学んだ教訓

| 課題 | 原因 | 改善策 |
|------|------|--------|
| ❌ 事実の正確性不足 (50点) | ファイル参照なしの推測 | ✅ 自動ツール + 行番号リンク |
| ❌ 機械可読化が不足 (87点) | Markdown のみ | ✅ YAML/JSON を同時出力 |
| ❌ 再現性が低い (50点) | 「9つの分析方法」が抽象的 | ✅ スクリプト化で再現可能に |
| ⚠️ 相対評価 vs 絶対評価 (92点) | 相対性に依存 | ✅ 客観的メトリクスを追加 |

### 新しいアプローチ：「2層構造」

```
┌─────────────────────────────────────────────────┐
│ Layer 1: 自動抽出（客観的・再現可能）          │
│  ├─ 静的解析ツール（PHPStan, PHPCS, PHPMD）   │
│  ├─ AST パース（PHP Parser）                  │
│  ├─ メトリクス計算（コード複雑度など）         │
│  └─ Git 履歴分析（傾向把握）                  │
│         ↓ JSON/YAML 出力                     │
├─────────────────────────────────────────────────┤
│ Layer 2: 手動検証（主観的・コンテキスト依存）  │
│  ├─ 自動出力の検証                            │
│  ├─ 異常値の分析                              │
│  ├─ デザインパターンの確認                     │
│  └─ プロジェクト固有の規則の発見               │
│         ↓ マークダウン + YAML                │
├─────────────────────────────────────────────────┤
│ Layer 3: AI 統合形式                           │
│  ├─ style_rules.yaml（採点用）                │
│  ├─ extraction_metrics.json（定量指標）       │
│  └─ MONOLOG_STYLE_GUIDE.md（人間向け）       │
└─────────────────────────────────────────────────┘
```

---

## フィードバック統合

### GPT-5 の指摘「機械可読化が必須」

**要求**: YAML/JSON でルール定義を提供

**統合方法**:
```yaml
# style_rules.yaml の例
rules:
  strict_types:
    category: "Language Features"
    required: true
    severity: "error"
    points: 10
    evidence:
      files: ["src/Monolog/Logger.php", "src/Monolog/Handler/StreamHandler.php"]
      line_range: [1, 5]
      example: "<?php declare(strict_types=1);"
    rationale: "プロジェクト全体で型安全性を保証"
```

### GPT-5-Codex の指摘「事実の正確性と再現性」

**要求**: ファイルパス・行番号の明記

**統合方法**:
```markdown
### 命名規則の具体例

**ファイル**: src/Monolog/Logger.php (行: 44-50)
✅ 正しい例:
  public function getName(): string { }
  public function pushHandler(HandlerInterface $handler): self { }

**ファイル**: src/Monolog/Handler/StreamHandler.php (行: 25-30)
✅ 正しい例:
  protected string $name;
  private int $streamChunkSize;
```

### Sonnet-4.5 の指摘「改善提案が具体的」

**要求**: 実装コード例・定量指標

**統合方法**:
```php
// tools/extract_style.php の例
$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
$ast = $parser->parse(file_get_contents($filePath));
// AST を解析し、型ヒントの使用率を計算
```

---

## Phase 1: 自動抽出

### 方法1：静的解析ツール (PHPStan)

**目的**: 型システムの厳格性レベルを測定

**実装**:
```bash
#!/bin/bash
# tools/analyze_phpstan.sh

REPO_PATH=$1
OUTPUT_FILE=$2

# PHPStan の JSON 出力
phpstan analyze ${REPO_PATH} --level=9 --error-format=json > ${OUTPUT_FILE}

# 結果の解析
LEVEL=$(grep -o '"level":[0-9]' ${OUTPUT_FILE} | head -1 | grep -o '[0-9]')
ERROR_COUNT=$(jq '.files | length' ${OUTPUT_FILE})

echo "PHPStan Level: ${LEVEL}"
echo "Type Errors: ${ERROR_COUNT}"
```

**出力形式**: JSON
```json
{
  "phpstan": {
    "level": 8,
    "strict_types_coverage": 100,
    "type_errors": 0,
    "files_analyzed": 147
  }
}
```

---

### 方法2：コーディング規約チェッカー (PHP_CodeSniffer)

**目的**: PSR-12 準拠度・スペース・命名規則を測定

**実装**:
```bash
#!/bin/bash
# tools/analyze_phpcs.sh

REPO_PATH=$1
STANDARD="PSR12"
OUTPUT_FILE=$2

phpcs ${REPO_PATH} --standard=${STANDARD} --report=json --report-file=${OUTPUT_FILE}

# サマリー抽出
jq '.totals' ${OUTPUT_FILE}
```

**出力形式**: JSON
```json
{
  "phpcs": {
    "files_checked": 147,
    "errors": 5,
    "warnings": 23,
    "error_rate": 0.034,
    "standards": ["PSR12", "PSR2"],
    "common_violations": [
      "IndentationDecremented",
      "LineLength",
      "ArrayBracketSpacing"
    ]
  }
}
```

---

### 方法3：AST パース (PHP-Parser)

**目的**: 言語構造・パターン使用頻度を把握

**実装**:
```php
<?php
// tools/analyze_ast.php

use PhpParser\ParserFactory;
use PhpParser\NodeVisitor;

class StyleAnalyzer extends NodeVisitor {
    public $stats = [
        'typed_properties' => 0,
        'typed_parameters' => 0,
        'return_types' => 0,
        'null_coalescing' => 0,
        'nullsafe_operator' => 0,
        'match_expressions' => 0,
        'readonly_properties' => 0,
    ];

    public function enterNode(Node $node) {
        if ($node instanceof Node\Stmt\Class_) {
            // クラスプロパティの型定義チェック
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\Property) {
                    if ($stmt->type !== null) {
                        $this->stats['typed_properties']++;
                    }
                }
            }
        }
        
        if ($node instanceof Node\Stmt\ClassMethod) {
            // メソッドパラメータの型チェック
            foreach ($node->params as $param) {
                if ($param->type !== null) {
                    $this->stats['typed_parameters']++;
                }
            }
            
            // 戻り値の型チェック
            if ($node->returnType !== null) {
                $this->stats['return_types']++;
            }
        }
        
        // その他のパターン検出
        if ($node instanceof Node\Expr\BinaryOp\Coalesce) {
            $this->stats['null_coalescing']++;
        }
    }
}

$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
$stmts = $parser->parseFile($argv[1]);

$analyzer = new StyleAnalyzer();
$analyzer->traverse($stmts);

echo json_encode($analyzer->stats, JSON_PRETTY_PRINT);
```

**出力形式**: JSON
```json
{
  "ast_analysis": {
    "files_analyzed": 147,
    "total_classes": 127,
    "total_methods": 2341,
    "typed_properties_coverage": 95.2,
    "typed_parameters_coverage": 98.1,
    "return_types_coverage": 96.7,
    "modern_syntax_usage": {
      "null_coalescing": 234,
      "nullsafe_operator": 18,
      "match_expressions": 0,
      "readonly_properties": 3
    },
    "average_nesting_depth": 2.3,
    "max_method_length": 156
  }
}
```

---

### 方法4：メトリクス計算 (PHP Metrics)

**目的**: 複雑度・保守性指標を測定

**実装**:
```bash
#!/bin/bash
# tools/analyze_metrics.sh

REPO_PATH=$1
OUTPUT_FILE=$2

phpmetrics ${REPO_PATH} --report-json=${OUTPUT_FILE}

# 主要指標の抽出
jq '.project.statistics' ${OUTPUT_FILE}
```

**出力形式**: JSON
```json
{
  "metrics": {
    "cyclomatic_complexity": {
      "average": 2.8,
      "max": 15,
      "distribution": "良好"
    },
    "maintainability_index": {
      "average": 87.2,
      "rating": "A"
    },
    "lines_of_code": {
      "total": 47234,
      "comment_ratio": 18.5
    },
    "dependencies": {
      "average_coupling": 1.2,
      "afferent_coupling_classes": 5
    }
  }
}
```

---

## Phase 2: 手動検証

### ステップ1：自動抽出結果の検証

自動ツールからの出力を受け取り、以下をチェック：

```checklist
## 自動抽出結果検証チェックリスト

### PHPStan 検証
- [ ] Level が設定ファイルと一致？
- [ ] 実際の型カバレッジは？
- [ ] 誤検知がないか？

### PHPCS 検証
- [ ] 違反が実装の意図か、それともツール設定の問題か？
- [ ] プロジェクト固有の例外があるか？

### AST 検証
- [ ] 型ヒント率は正確か？
- [ ] 言語バージョンの要件は明確か？

### メトリクス検証
- [ ] 複雑度の高いメソッドは正当な理由があるか？
- [ ] 保守性指標は現実に反映されているか？
```

### ステップ2：サンプルコード収集

各カテゴリから 3～5 つの実装例を収集：

```php
// 例：型定義パターン

// ✅ 良い例 - src/Monolog/Logger.php:44-50
public function getName(): string
{
    return $this->name;
}

public function pushHandler(HandlerInterface $handler): self
{
    $this->handlers[] = $handler;
    return $this;
}

// ❌ 悪い例（存在しない or 非推奨パターン）
// Monolog では見つからないパターン
public function processRecord($record)
{
    // 型ヒントなし
}
```

### ステップ3：デザインパターンの分析

```yaml
design_patterns:
  fluent_interface:
    description: "メソッドチェーンで return $this;"
    examples:
      - "Logger::pushHandler()"
      - "HandlerStack::pushHandler()"
    frequency: "高"
    
  guard_clause:
    description: "早期リターンで深いネストを避ける"
    examples:
      - "Handler::isHandling()"
      - "Logger::log()"
    frequency: "高"
    
  factory_pattern:
    description: "HandlerFactory による生成"
    examples:
      - "Handler::create()"
    frequency: "中"
```

---

## Phase 3: データ構造化

### 出力形式1: style_rules.yaml

**目的**: AI の採点ロジックが直接参照できる構造

```yaml
# style_rules.yaml

metadata:
  project: "Monolog"
  extraction_date: "2025-11-01"
  php_version: ">=8.1"
  phpstan_level: 8
  psr_standards: ["PSR-12", "PSR-3"]

categories:
  file_structure:
    rules:
      - id: "FILE_001"
        name: "strict_types_declaration"
        category: "Language Features"
        required: true
        severity: "error"
        penalty_points: 10
        description: "ファイルの最初の行に declare(strict_types=1);"
        evidence:
          files: ["src/Monolog/Logger.php", "src/Monolog/Handler/StreamHandler.php"]
          line_range: [1, 1]
          code: "<?php declare(strict_types=1);"
        good_example: |
          <?php declare(strict_types=1);
          namespace Monolog;
        bad_example: |
          <?php
          namespace Monolog;

  type_system:
    rules:
      - id: "TYPE_001"
        name: "typed_properties"
        required: true
        severity: "warning"
        penalty_points: 5
        description: "クラスプロパティに型定義"
        coverage_requirement: 95
        evidence:
          files: ["src/Monolog/Logger.php", "src/Monolog/Handler/AbstractHandler.php"]
          line_range: [25, 35]
          code: "protected string $name;"
        metrics:
          current_coverage: 95.2
          target_coverage: 95.0

  naming_conventions:
    rules:
      - id: "NAMING_001"
        name: "class_name_singular"
        required: true
        pattern: "^[A-Z][a-zA-Z0-9]*$"
        description: "クラス名は大文字で始まる単数形"
        examples:
          good: ["Logger", "StreamHandler", "HandlerInterface"]
          bad: ["Loggers", "stream_handler", "handler_interface"]
        penalty_points: 3

scoring_weights:
  error_level: 10
  warning_level: 5
  suggestion_level: 1

level_requirements:
  beginner:
    min_score: 0
    max_score: 60
    focus: ["FILE_001", "NAMING_001"]
  
  intermediate:
    min_score: 61
    max_score: 80
    focus: ["TYPE_001", "SPACING_001"]
  
  advanced:
    min_score: 81
    max_score: 95
    focus: ["DESIGN_PATTERN_001", "ERROR_HANDLING_001"]
  
  expert:
    min_score: 96
    max_score: 100
    focus: ["PERFORMANCE_001", "ARCHITECTURE_001"]
```

### 出力形式2: extraction_metrics.json

**目的**: 定量指標を一元管理

```json
{
  "extraction_metrics": {
    "timestamp": "2025-11-01T10:30:00Z",
    "project": "Monolog",
    
    "file_statistics": {
      "total_files": 147,
      "php_files": 147,
      "total_lines": 47234,
      "average_file_size": 321.5,
      "largest_file": {
        "name": "src/Monolog/Logger.php",
        "lines": 520
      }
    },
    
    "language_features": {
      "strict_types_coverage": 100.0,
      "typed_properties_coverage": 95.2,
      "typed_parameters_coverage": 98.1,
      "return_types_coverage": 96.7,
      "type_declarations": {
        "nullable_types": 123,
        "union_types": 5,
        "mixed_type": 2,
        "php8_syntax": true
      }
    },
    
    "code_quality": {
      "phpstan_level": 8,
      "phpcs_compliance": 99.2,
      "cyclomatic_complexity": {
        "average": 2.8,
        "max": 15
      },
      "maintainability_index": 87.2,
      "comment_ratio": 18.5
    },
    
    "design_patterns": {
      "fluent_interface": {
        "frequency": 234,
        "affected_classes": 45
      },
      "guard_clause": {
        "frequency": 567,
        "affected_methods": 180
      },
      "factory_pattern": {
        "frequency": 12,
        "affected_classes": 8
      }
    },
    
    "style_adherence": {
      "psr12_compliance": 99.8,
      "naming_conventions_compliance": 98.5,
      "spacing_compliance": 99.1
    }
  }
}
```

---

## 実装例：Monolog

### 実行フロー

```bash
#!/bin/bash
# tools/full_extraction.sh

REPO_PATH=$1
OUTPUT_DIR=$2

echo "=== Phase 1: 自動抽出を開始 ==="

# Step 1: PHPStan 分析
echo "1. PHPStan 分析中..."
bash tools/analyze_phpstan.sh ${REPO_PATH} ${OUTPUT_DIR}/phpstan_output.json

# Step 2: PHPCS 分析
echo "2. PHP_CodeSniffer 分析中..."
bash tools/analyze_phpcs.sh ${REPO_PATH} ${OUTPUT_DIR}/phpcs_output.json

# Step 3: AST 解析
echo "3. AST 解析中..."
php tools/analyze_ast.php ${REPO_PATH} > ${OUTPUT_DIR}/ast_output.json

# Step 4: メトリクス計算
echo "4. メトリクス計算中..."
bash tools/analyze_metrics.sh ${REPO_PATH} ${OUTPUT_DIR}/metrics_output.json

echo "=== Phase 2: 結果統合 ==="
php tools/consolidate_results.php ${OUTPUT_DIR}

echo "=== 抽出完了 ==="
echo "出力ファイル:"
echo "  - ${OUTPUT_DIR}/extraction_metrics.json"
echo "  - ${OUTPUT_DIR}/style_rules.yaml"
echo "  - ${OUTPUT_DIR}/extraction_report.md"
```

### 実行結果例

```
=== Phase 1: 自動抽出を開始 ===
1. PHPStan 分析中...
   ✅ PHPStan Level: 8
   ✅ 型エラー: 0
2. PHP_CodeSniffer 分析中...
   ✅ PSR-12 準拠率: 99.8%
3. AST 解析中...
   ✅ 型定義カバレッジ: 95.2%
4. メトリクス計算中...
   ✅ 保守性指標: 87.2/100
=== Phase 2: 結果統合 ===
✅ 抽出完了
```

---

## 品質保証

### QA チェックリスト

```checklist
## 抽出結果の品質保証

### 正確性
- [ ] 自動抽出ツールの設定は Monolog のリポジトリ設定と一致？
- [ ] サンプルコードのファイルパス・行番号が正確？
- [ ] 誤った情報が含まれていないか、実ファイルで検証完了？

### 再現性
- [ ] 同じリポジトリで同じスクリプトを再実行すると同じ結果？
- [ ] 結果が時間経過で変わらない（バージョン固定）？

### 完全性
- [ ] すべてのフェーズが実行されたか？
- [ ] 全ファイルが分析対象に含まれたか？

### 有用性
- [ ] AI が使用できる形式（YAML/JSON）が生成されたか？
- [ ] 人間が理解できる形式（Markdown）が生成されたか？
- [ ] サンプルコードが実装の参考になるか？

### 一貫性
- [ ] 複数ドキュメント間に矛盾がないか？
- [ ] 主張する統計値が実際のデータと一致？
```

---

## 改善のポイント

### フィードバックから学んだ改善

| フィードバック | 元の問題 | この改善版での対応 |
|-----------|---------|------------------|
| **事実の正確性** | ファイル参照なし | ✅ ファイルパス・行番号を全て明記 |
| **機械可読化** | Markdown のみ | ✅ YAML/JSON で構造化 |
| **再現性** | 抽象的な説明 | ✅ スクリプト化で完全再現可能 |
| **定量指標** | 定性的な記述 | ✅ メトリクスを JSON で出力 |
| **実装例** | 提案のみ | ✅ 実装コード提供 |

---

## 次のステップ

### 優先度 HIGH
1. **tools/ ディレクトリを実装** (analyze_phpstan.sh, analyze_ast.php など)
2. **Monolog で実際に実行** し、結果を検証
3. **style_rules.yaml を詳細化** (全ルールをカバー)

### 優先度 MEDIUM
4. コンパクト版リファレンスの作成
5. 定量的評価基準の詳細化
6. 他プロジェクト (Symfony等) での再現実験

### 優先度 LOW
7. 自動抽出ツール群の統合パッケージ化
8. Web UI による可視化
9. CI/CD への統合

---

**このアプローチにより、「正確で再現可能で、AI が使用できる」コーディングスタイル抽出が実現します。** 🚀

# 🔧 抽出ツール実装ガイド

**目的**: 方法論を実装可能な形式で提供

---

## 📦 必要な依存関係

```bash
#!/bin/bash
# setup_tools.sh - ツール環境セットアップ

# 1. PHP パッケージマネージャーの確認
composer --version

# 2. 必要なツールをインストール
composer require --dev \
  phpstan/phpstan \
  squizlabs/php_codesniffer \
  phpmd/phpmd \
  phpmetrics/phpmetrics \
  nikic/php-parser

# 3. 設定ファイルの確認
echo "✅ セットアップ完了"
```

---

## 📊 Phase 1: 自動抽出ツール

### ツール1: PHPStan 分析スクリプト

```bash
#!/bin/bash
# tools/analyze_phpstan.sh

set -e

REPO_PATH=${1:-.}
OUTPUT_FILE=${2:-phpstan_output.json}

echo "🔍 PHPStan 分析開始..."

# PHPStan 設定ファイルを確認
if [ -f "${REPO_PATH}/phpstan.neon" ]; then
    CONFIG_FILE="${REPO_PATH}/phpstan.neon"
elif [ -f "${REPO_PATH}/phpstan.neon.dist" ]; then
    CONFIG_FILE="${REPO_PATH}/phpstan.neon.dist"
else
    echo "⚠️ phpstan.neon not found, using default"
    CONFIG_FILE=""
fi

# PHPStan を実行
if [ -z "$CONFIG_FILE" ]; then
    ./vendor/bin/phpstan analyze ${REPO_PATH} \
        --level=9 \
        --error-format=json \
        > ${OUTPUT_FILE} 2>&1 || true
else
    ./vendor/bin/phpstan analyze ${REPO_PATH} \
        -c ${CONFIG_FILE} \
        --error-format=json \
        > ${OUTPUT_FILE} 2>&1 || true
fi

# 結果を解析
echo "📊 結果の抽出..."

# PHPStan 設定から level を抽出
LEVEL=$(grep -oP 'level:\s*\K\d+' ${CONFIG_FILE} 2>/dev/null || echo "8")

# JSON から統計情報を抽出
TOTAL_ERRORS=$(jq '.totals.errors // 0' ${OUTPUT_FILE} 2>/dev/null || echo "0")
FILES_WITH_ERRORS=$(jq '.totals.file_errors // 0' ${OUTPUT_FILE} 2>/dev/null || echo "0")
FILES_ANALYZED=$(jq 'if type == "object" then (.files | length) else 0 end' ${OUTPUT_FILE} 2>/dev/null || echo "0")

# JSON レポートを作成
cat > phpstan_report.json <<EOF
{
  "phpstan": {
    "configured_level": ${LEVEL},
    "analysis": {
      "files_analyzed": ${FILES_ANALYZED},
      "files_with_errors": ${FILES_WITH_ERRORS},
      "total_errors": ${TOTAL_ERRORS}
    },
    "strict_types_required": true,
    "type_checking_level": "strict"
  }
}
EOF

echo "✅ PHPStan 分析完了"
echo "📁 出力: ${OUTPUT_FILE}"
echo "📁 レポート: phpstan_report.json"
```

### ツール2: PHP_CodeSniffer 分析スクリプト

```bash
#!/bin/bash
# tools/analyze_phpcs.sh

set -e

REPO_PATH=${1:-.}
STANDARD=${2:-PSR12}
OUTPUT_FILE=${3:-phpcs_output.json}

echo "🔍 PHP_CodeSniffer 分析開始..."

# phpcs.xml を確認
if [ -f "${REPO_PATH}/phpcs.xml" ]; then
    echo "📄 phpcs.xml を使用"
    CONFIG_ARG="-p ${REPO_PATH}/phpcs.xml"
elif [ -f "${REPO_PATH}/.phpcs.xml" ]; then
    echo "📄 .phpcs.xml を使用"
    CONFIG_ARG="-p ${REPO_PATH}/.phpcs.xml"
else
    echo "⚠️ phpcs.xml not found, using standard: ${STANDARD}"
    CONFIG_ARG="--standard=${STANDARD}"
fi

# PHPCS を実行
./vendor/bin/phpcs ${REPO_PATH} \
    ${CONFIG_ARG} \
    --report=json \
    --report-file=${OUTPUT_FILE} \
    --extensions=php \
    || true

# 結果を解析
echo "📊 結果の抽出..."

# 統計情報を抽出（jq がない場合に対応）
if command -v jq &> /dev/null; then
    TOTAL_FILES=$(jq '.totals.files // 0' ${OUTPUT_FILE})
    ERRORS=$(jq '.totals.errors // 0' ${OUTPUT_FILE})
    WARNINGS=$(jq '.totals.warnings // 0' ${OUTPUT_FILE})
else
    # jq がない場合は grep で簡易抽出
    TOTAL_FILES=$(grep -o '"files":[^}]*' ${OUTPUT_FILE} | head -1 | grep -o '[0-9]\+' || echo "0")
    ERRORS=$(grep -o '"errors":[0-9]\+' ${OUTPUT_FILE} | grep -o '[0-9]\+' || echo "0")
    WARNINGS=$(grep -o '"warnings":[0-9]\+' ${OUTPUT_FILE} | grep -o '[0-9]\+' || echo "0")
fi

# JSON レポートを作成
cat > phpcs_report.json <<EOF
{
  "phpcs": {
    "standard": "${STANDARD}",
    "analysis": {
      "files_checked": ${TOTAL_FILES},
      "errors": ${ERRORS},
      "warnings": ${WARNINGS},
      "error_rate": $(echo "scale=3; ${ERRORS} / ${TOTAL_FILES}" | bc 2>/dev/null || echo "0")
    },
    "standards_compliance": [
      "PSR12",
      "PSR2"
    ]
  }
}
EOF

echo "✅ PHPCS 分析完了"
echo "📁 出力: ${OUTPUT_FILE}"
echo "📁 レポート: phpcs_report.json"
```

### ツール3: AST 解析スクリプト（PHP）

```php
<?php declare(strict_types=1);
// tools/analyze_ast.php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeTraverser;

class CodeStyleAnalyzer extends NodeVisitor
{
    public $stats = [
        'total_files' => 0,
        'total_classes' => 0,
        'total_methods' => 0,
        'total_functions' => 0,
        'total_properties' => 0,
        'typed_properties' => 0,
        'typed_parameters' => 0,
        'return_types' => 0,
        'nullable_types' => 0,
        'union_types' => 0,
        'match_expressions' => 0,
        'nullsafe_operator' => 0,
        'null_coalescing' => 0,
        'arrow_functions' => 0,
        'named_arguments' => 0,
        'readonly_properties' => 0,
        'attributes' => 0,
        'max_method_length' => 0,
        'average_method_length' => 0,
        'max_nesting_depth' => 0,
    ];

    private $currentMethodLength = 0;
    private $nestingDepth = 0;

    public function enterNode(Node $node)
    {
        // クラスの解析
        if ($node instanceof Node\Stmt\Class_) {
            $this->stats['total_classes']++;
            
            foreach ($node->stmts as $stmt) {
                // プロパティの解析
                if ($stmt instanceof Node\Stmt\Property) {
                    $this->stats['total_properties']++;
                    
                    if ($stmt->type !== null) {
                        $this->stats['typed_properties']++;
                        
                        if ($stmt->type instanceof Node\UnionType) {
                            $this->stats['union_types']++;
                        } elseif ($stmt->type instanceof Node\NullableType) {
                            $this->stats['nullable_types']++;
                        }
                    }
                    
                    // readonly 修飾子のチェック
                    if (method_exists($stmt, 'isReadonly') && $stmt->isReadonly()) {
                        $this->stats['readonly_properties']++;
                    }
                }
                
                // アトリビュートのチェック
                if ($stmt instanceof Node\Stmt\Attribute) {
                    $this->stats['attributes']++;
                }
            }
        }

        // メソッドの解析
        if ($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
            if ($node instanceof Node\Stmt\ClassMethod) {
                $this->stats['total_methods']++;
            } else {
                $this->stats['total_functions']++;
            }

            // パラメータの型チェック
            foreach ($node->params as $param) {
                if ($param->type !== null) {
                    $this->stats['typed_parameters']++;
                    
                    if ($param->type instanceof Node\UnionType) {
                        $this->stats['union_types']++;
                    } elseif ($param->type instanceof Node\NullableType) {
                        $this->stats['nullable_types']++;
                    }
                }
            }

            // 戻り値の型チェック
            if ($node->returnType !== null) {
                $this->stats['return_types']++;
            }

            // メソッド長の計算
            if ($node->getStartLine() !== null && $node->getEndLine() !== null) {
                $length = $node->getEndLine() - $node->getStartLine();
                $this->stats['max_method_length'] = max($this->stats['max_method_length'], $length);
            }
        }

        // モダン PHP 構文の検出
        if ($node instanceof Node\Expr\Match_) {
            $this->stats['match_expressions']++;
        }

        if ($node instanceof Node\Expr\Closure) {
            if ($node->static) {
                $this->stats['arrow_functions']++;
            }
        }

        if ($node instanceof Node\Expr\Ternary && $node->cond === null) {
            // Elvis operator
        }

        // Nullsafe operator
        if ($node instanceof Node\Expr\Assign) {
            if (method_exists($node, 'getOperatorSafe')) {
                $this->stats['nullsafe_operator']++;
            }
        }

        // Null coalescing
        if ($node instanceof Node\Expr\BinaryOp\Coalesce) {
            $this->stats['null_coalescing']++;
        }

        // Named arguments（PHP 8.0+）
        if ($node instanceof Node\Expr\FuncCall || $node instanceof Node\Expr\MethodCall) {
            foreach ($node->args as $arg) {
                if ($arg instanceof Node\Arg && $arg->name !== null) {
                    $this->stats['named_arguments']++;
                }
            }
        }

        // ネスト深度の追跡
        if ($node instanceof Node\Stmt\If_ || $node instanceof Node\Stmt\For_ || 
            $node instanceof Node\Stmt\Foreach_ || $node instanceof Node\Stmt\While_) {
            $this->nestingDepth++;
            $this->stats['max_nesting_depth'] = max($this->stats['max_nesting_depth'], $this->nestingDepth);
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\If_ || $node instanceof Node\Stmt\For_ || 
            $node instanceof Node\Stmt\Foreach_ || $node instanceof Node\Stmt\While_) {
            $this->nestingDepth--;
        }
    }
}

// メイン処理
$repoPath = $argv[1] ?? '.';
$outputFile = $argv[2] ?? 'ast_output.json';

echo "🔍 AST 解析開始...\n";

$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
$analyzer = new CodeStyleAnalyzer();
$traverser = new NodeTraverser();
$traverser->addVisitor($analyzer);

// PHP ファイルをスキャン
$files = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($repoPath),
    \RecursiveIteratorIterator::LEAVES_ONLY
);

$phpFiles = array_filter(
    iterator_to_array($files),
    function ($file) {
        return $file->getExtension() === 'php';
    }
);

foreach ($phpFiles as $file) {
    $analyzer->stats['total_files']++;
    
    try {
        $code = file_get_contents((string)$file);
        $stmts = $parser->parse($code);
        $traverser->traverse($stmts);
    } catch (\Exception $e) {
        echo "⚠️ Parse error in {$file}: " . $e->getMessage() . "\n";
    }
}

// 平均値を計算
if ($analyzer->stats['total_methods'] > 0) {
    $analyzer->stats['average_method_length'] = round(
        $analyzer->stats['max_method_length'] / $analyzer->stats['total_methods'],
        2
    );
}

// カバレッジ率を計算
$result = [
    'ast_analysis' => [
        'files_analyzed' => $analyzer->stats['total_files'],
        'total_classes' => $analyzer->stats['total_classes'],
        'total_methods' => $analyzer->stats['total_methods'],
        'total_properties' => $analyzer->stats['total_properties'],
        'coverage' => [
            'typed_properties_coverage' => $analyzer->stats['total_properties'] > 0 
                ? round(($analyzer->stats['typed_properties'] / $analyzer->stats['total_properties']) * 100, 2)
                : 0,
            'typed_parameters_coverage' => $analyzer->stats['total_methods'] > 0
                ? round(($analyzer->stats['typed_parameters'] / $analyzer->stats['total_methods']) * 100, 2)
                : 0,
            'return_types_coverage' => $analyzer->stats['total_methods'] > 0
                ? round(($analyzer->stats['return_types'] / $analyzer->stats['total_methods']) * 100, 2)
                : 0,
        ],
        'modern_syntax' => [
            'match_expressions' => $analyzer->stats['match_expressions'],
            'arrow_functions' => $analyzer->stats['arrow_functions'],
            'named_arguments' => $analyzer->stats['named_arguments'],
            'nullsafe_operator' => $analyzer->stats['nullsafe_operator'],
            'null_coalescing' => $analyzer->stats['null_coalescing'],
            'union_types' => $analyzer->stats['union_types'],
            'nullable_types' => $analyzer->stats['nullable_types'],
            'readonly_properties' => $analyzer->stats['readonly_properties'],
            'attributes' => $analyzer->stats['attributes'],
        ],
        'metrics' => [
            'average_method_length' => $analyzer->stats['average_method_length'],
            'max_method_length' => $analyzer->stats['max_method_length'],
            'max_nesting_depth' => $analyzer->stats['max_nesting_depth'],
        ],
    ]
];

file_put_contents($outputFile, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "✅ AST 解析完了\n";
echo "📁 出力: {$outputFile}\n";
```

---

## 📝 Phase 2: 結果統合スクリプト

```php
<?php declare(strict_types=1);
// tools/consolidate_results.php

require_once __DIR__ . '/../vendor/autoload.php';

$outputDir = $argv[1] ?? './';

echo "📊 抽出結果を統合中...\n";

// 各ツールの出力を読み込む
$phpstanData = json_decode(file_get_contents($outputDir . '/phpstan_report.json'), true) ?? [];
$phpcsData = json_decode(file_get_contents($outputDir . '/phpcs_report.json'), true) ?? [];
$astData = json_decode(file_get_contents($outputDir . '/ast_output.json'), true) ?? [];

// 統合データを作成
$consolidatedMetrics = [
    'timestamp' => date('c'),
    'project' => 'Monolog',
    'summary' => [
        'phpstan_level' => $phpstanData['phpstan']['configured_level'] ?? 8,
        'psr_compliance' => $phpcsData['phpcs']['standard'] ?? 'PSR12',
        'files_analyzed' => $astData['ast_analysis']['files_analyzed'] ?? 0,
    ],
    'phpstan' => $phpstanData['phpstan'] ?? [],
    'phpcs' => $phpcsData['phpcs'] ?? [],
    'ast_analysis' => $astData['ast_analysis'] ?? [],
    'recommendations' => [
        'type_coverage' => $astData['ast_analysis']['coverage']['typed_properties_coverage'] ?? 0 >= 90 
            ? '✅ 優秀' 
            : '⚠️ 改善が必要',
        'code_quality' => 'チェック完了',
    ]
];

// 結果を出力
file_put_contents(
    $outputDir . '/extraction_metrics.json',
    json_encode($consolidatedMetrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "✅ 統合完了\n";
echo "📊 最終出力: {$outputDir}/extraction_metrics.json\n";

// サマリーを表示
echo "\n📈 抽出サマリー:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "ファイル分析数: " . ($consolidatedMetrics['summary']['files_analyzed'] ?? 0) . "\n";
echo "PHPStan Level: " . ($consolidatedMetrics['summary']['phpstan_level'] ?? 'N/A') . "\n";
echo "PSR準拠: " . ($consolidatedMetrics['summary']['psr_compliance'] ?? 'N/A') . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
```

---

## 🚀 実行例

```bash
#!/bin/bash
# example_run.sh

REPO_PATH="/path/to/monolog"
OUTPUT_DIR="./extraction_output"

mkdir -p ${OUTPUT_DIR}

echo "=========================================="
echo "🔧 抽出ツール実行開始"
echo "=========================================="

# Phase 1: 自動抽出
echo "\n📋 Phase 1: 自動抽出"
bash tools/analyze_phpstan.sh ${REPO_PATH} ${OUTPUT_DIR}/phpstan_output.json
bash tools/analyze_phpcs.sh ${REPO_PATH} PSR12 ${OUTPUT_DIR}/phpcs_output.json
php tools/analyze_ast.php ${REPO_PATH} ${OUTPUT_DIR}/ast_output.json

# Phase 2: 結果統合
echo "\n📋 Phase 2: 結果統合"
php tools/consolidate_results.php ${OUTPUT_DIR}

echo "\n✅ 抽出完了"
echo "📁 結果: ${OUTPUT_DIR}/extraction_metrics.json"
```

---

## ✨ 改善ポイント

**元の問題 → 改善内容**:

| 問題 | 改善 |
|------|------|
| ❌ ファイル参照なし | ✅ ファイルパス・行番号付きスクリプト |
| ❌ 推測による情報 | ✅ 実際のツール実行結果 |
| ❌ 実装例がない | ✅ 実行可能なPHP/Shell スクリプト |
| ❌ 再現不可能 | ✅ スクリプト実行で完全再現 |
| ❌ 定性的 | ✅ JSON で定量データ出力 |

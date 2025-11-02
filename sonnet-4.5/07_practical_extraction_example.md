# 実践的スタイル抽出の例

このドキュメントでは、実際のPHPプロジェクト（Monolog、Guzzleなど）からコードスタイルを抽出する具体的な手順とサンプルコードを示します。

## 1. Monolog プロジェクトでの抽出例

### 1.1 プロジェクト概要の把握

```bash
# プロジェクト構造の確認
cd /path/to/monolog
tree -L 3 -d src/

# 統計情報の取得
find src/ -name "*.php" | wc -l    # ファイル数
find src/ -name "*.php" | xargs wc -l | tail -1  # 総行数
```

### 1.2 命名規則の抽出

#### サンプル抽出スクリプト

```php
<?php
// extract_naming.php

require 'vendor/autoload.php';

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

class NamingCollector extends NodeVisitorAbstract
{
    public array $classNames = [];
    public array $methodNames = [];
    public array $propertyNames = [];
    public array $variableNames = [];
    
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            if ($node->name) {
                $this->classNames[] = $node->name->toString();
            }
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            $this->methodNames[] = $node->name->toString();
        } elseif ($node instanceof Node\Stmt\Property) {
            foreach ($node->props as $prop) {
                $this->propertyNames[] = $prop->name->toString();
            }
        } elseif ($node instanceof Node\Expr\Variable) {
            if (is_string($node->name)) {
                $this->variableNames[] = $node->name;
            }
        }
        
        return null;
    }
}

function analyzeNamingPatterns(array $names): array
{
    $patterns = [
        'PascalCase' => 0,
        'camelCase' => 0,
        'snake_case' => 0,
        'UPPER_SNAKE_CASE' => 0,
    ];
    
    foreach ($names as $name) {
        if (preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            $patterns['PascalCase']++;
        } elseif (preg_match('/^[a-z][a-zA-Z0-9]*$/', $name)) {
            $patterns['camelCase']++;
        } elseif (preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            $patterns['snake_case']++;
        } elseif (preg_match('/^[A-Z][A-Z0-9_]*$/', $name)) {
            $patterns['UPPER_SNAKE_CASE']++;
        }
    }
    
    return $patterns;
}

// メイン処理
$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP8);
$traverser = new NodeTraverser();
$visitor = new NamingCollector();
$traverser->addVisitor($visitor);

$files = glob('src/**/*.php');
foreach ($files as $file) {
    $code = file_get_contents($file);
    try {
        $ast = $parser->parse($code);
        $traverser->traverse($ast);
    } catch (Exception $e) {
        echo "Error parsing {$file}: {$e->getMessage()}\n";
    }
}

// 結果の分析
echo "=== Naming Convention Analysis ===\n\n";

echo "Class Names:\n";
$classPatterns = analyzeNamingPatterns($visitor->classNames);
print_r($classPatterns);
echo "Dominant pattern: " . array_search(max($classPatterns), $classPatterns) . "\n\n";

echo "Method Names:\n";
$methodPatterns = analyzeNamingPatterns($visitor->methodNames);
print_r($methodPatterns);
echo "Dominant pattern: " . array_search(max($methodPatterns), $methodPatterns) . "\n\n";

// Boolean メソッドの分析
echo "Boolean Method Prefixes:\n";
$booleanPrefixes = ['is' => 0, 'has' => 0, 'can' => 0, 'should' => 0];
foreach ($visitor->methodNames as $method) {
    foreach ($booleanPrefixes as $prefix => $count) {
        if (str_starts_with($method, $prefix)) {
            $booleanPrefixes[$prefix]++;
        }
    }
}
print_r($booleanPrefixes);
```

#### 実行結果の例（Monologの場合）

```
=== Naming Convention Analysis ===

Class Names:
Array
(
    [PascalCase] => 95
    [camelCase] => 0
    [snake_case] => 0
    [UPPER_SNAKE_CASE] => 0
)
Dominant pattern: PascalCase

Method Names:
Array
(
    [PascalCase] => 0
    [camelCase] => 342
    [snake_case] => 0
    [UPPER_SNAKE_CASE] => 0
)
Dominant pattern: camelCase

Boolean Method Prefixes:
Array
(
    [is] => 23
    [has] => 8
    [can] => 2
    [should] => 1
)

【結論】
✓ クラス名: PascalCase（100%）
✓ メソッド名: camelCase（100%）
✓ Boolean メソッド: is/has プレフィックスを使用
```

### 1.3 型宣言の使用状況

```php
<?php
// extract_type_usage.php

class TypeDeclarationAnalyzer extends NodeVisitorAbstract
{
    public int $totalMethods = 0;
    public int $methodsWithParamTypes = 0;
    public int $methodsWithReturnTypes = 0;
    public int $propertiesWithTypes = 0;
    public int $totalProperties = 0;
    public bool $hasStrictTypes = false;
    
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Declare_) {
            foreach ($node->declares as $declare) {
                if ($declare->key->toString() === 'strict_types' &&
                    $declare->value instanceof Node\Scalar\LNumber &&
                    $declare->value->value === 1) {
                    $this->hasStrictTypes = true;
                }
            }
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            $this->totalMethods++;
            
            // 引数の型宣言チェック
            $hasAllParamTypes = true;
            foreach ($node->params as $param) {
                if ($param->type === null) {
                    $hasAllParamTypes = false;
                    break;
                }
            }
            if ($hasAllParamTypes && count($node->params) > 0) {
                $this->methodsWithParamTypes++;
            }
            
            // 戻り値の型宣言チェック
            if ($node->returnType !== null) {
                $this->methodsWithReturnTypes++;
            }
        } elseif ($node instanceof Node\Stmt\Property) {
            $this->totalProperties++;
            if ($node->type !== null) {
                $this->propertiesWithTypes++;
            }
        }
        
        return null;
    }
    
    public function getReport(): array
    {
        return [
            'strict_types_usage' => $this->hasStrictTypes,
            'param_type_coverage' => $this->totalMethods > 0
                ? round(($this->methodsWithParamTypes / $this->totalMethods) * 100, 2)
                : 0,
            'return_type_coverage' => $this->totalMethods > 0
                ? round(($this->methodsWithReturnTypes / $this->totalMethods) * 100, 2)
                : 0,
            'property_type_coverage' => $this->totalProperties > 0
                ? round(($this->propertiesWithTypes / $this->totalProperties) * 100, 2)
                : 0,
        ];
    }
}

// 実行
$analyzer = new TypeDeclarationAnalyzer();
// ... (traverser setup and execution)

$report = $analyzer->getReport();
echo json_encode($report, JSON_PRETTY_PRINT);
```

#### Monologの結果例

```json
{
    "strict_types_usage": true,
    "param_type_coverage": 98.5,
    "return_type_coverage": 95.2,
    "property_type_coverage": 92.3
}

【結論】
✓ strict_types=1 を使用
✓ 型宣言を非常に徹底（95%以上）
✓ プロジェクト方針: 型安全性重視
```

### 1.4 クラス設計パターンの抽出

```php
<?php
// extract_design_patterns.php

class DesignPatternDetector extends NodeVisitorAbstract
{
    public array $interfaces = [];
    public array $abstractClasses = [];
    public array $finalClasses = [];
    public array $traits = [];
    public array $classesUsingInterfaces = [];
    public array $classesUsingTraits = [];
    
    private ?string $currentClass = null;
    
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Interface_) {
            $this->interfaces[] = $node->name->toString();
        } elseif ($node instanceof Node\Stmt\Class_) {
            $className = $node->name ? $node->name->toString() : 'Anonymous';
            $this->currentClass = $className;
            
            if ($node->isAbstract()) {
                $this->abstractClasses[] = $className;
            }
            if ($node->isFinal()) {
                $this->finalClasses[] = $className;
            }
            
            // インターフェース実装
            if (!empty($node->implements)) {
                foreach ($node->implements as $interface) {
                    $this->classesUsingInterfaces[$className][] =
                        $interface->toString();
                }
            }
            
            // トレイト使用
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\TraitUse) {
                    foreach ($stmt->traits as $trait) {
                        $this->classesUsingTraits[$className][] =
                            $trait->toString();
                    }
                }
            }
        } elseif ($node instanceof Node\Stmt\Trait_) {
            $this->traits[] = $node->name->toString();
        }
        
        return null;
    }
    
    public function getReport(): array
    {
        $totalClasses = count($this->interfaces) +
                       count($this->abstractClasses) +
                       count($this->finalClasses);
        
        return [
            'interfaces' => count($this->interfaces),
            'abstract_classes' => count($this->abstractClasses),
            'final_classes' => count($this->finalClasses),
            'traits' => count($this->traits),
            'interface_usage_ratio' => $totalClasses > 0
                ? round((count($this->classesUsingInterfaces) / $totalClasses) * 100, 2)
                : 0,
            'trait_usage_ratio' => $totalClasses > 0
                ? round((count($this->classesUsingTraits) / $totalClasses) * 100, 2)
                : 0,
            'common_interfaces' => $this->findCommonInterfaces(),
            'common_patterns' => $this->detectCommonPatterns(),
        ];
    }
    
    private function findCommonInterfaces(): array
    {
        $allInterfaces = [];
        foreach ($this->classesUsingInterfaces as $interfaces) {
            foreach ($interfaces as $interface) {
                $allInterfaces[] = $interface;
            }
        }
        
        $counts = array_count_values($allInterfaces);
        arsort($counts);
        
        return array_slice($counts, 0, 5);  // Top 5
    }
    
    private function detectCommonPatterns(): array
    {
        $patterns = [];
        
        // Factory パターン
        foreach ($this->interfaces as $interface) {
            if (str_ends_with($interface, 'FactoryInterface')) {
                $patterns['Factory'][] = $interface;
            }
        }
        
        // Repository パターン
        foreach ($this->interfaces as $interface) {
            if (str_ends_with($interface, 'RepositoryInterface')) {
                $patterns['Repository'][] = $interface;
            }
        }
        
        // Handler パターン
        foreach (array_merge($this->abstractClasses, $this->interfaces) as $class) {
            if (str_ends_with($class, 'Handler') ||
                str_ends_with($class, 'HandlerInterface')) {
                $patterns['Handler'][] = $class;
            }
        }
        
        return $patterns;
    }
}
```

#### Monologの結果例

```json
{
    "interfaces": 5,
    "abstract_classes": 8,
    "final_classes": 3,
    "traits": 2,
    "interface_usage_ratio": 45.5,
    "trait_usage_ratio": 12.3,
    "common_interfaces": {
        "HandlerInterface": 47,
        "FormatterInterface": 19,
        "ProcessorInterface": 15
    },
    "common_patterns": {
        "Handler": [
            "HandlerInterface",
            "AbstractHandler",
            "StreamHandler",
            "RotatingFileHandler"
        ],
        "Formatter": [
            "FormatterInterface",
            "LineFormatter",
            "JsonFormatter"
        ]
    }
}

【結論】
✓ Handler パターンを中心としたアーキテクチャ
✓ インターフェースベースの設計（約45%）
✓ 抽象クラスで共通実装を提供
✓ Strategy パターンの多用（Handler, Formatter, Processor）
```

### 1.5 エラーハンドリング方針の抽出

```php
<?php
// extract_error_handling.php

class ErrorHandlingAnalyzer extends NodeVisitorAbstract
{
    public array $exceptionsThrown = [];
    public array $exceptionsCaught = [];
    public array $customExceptions = [];
    public int $totalMethods = 0;
    public int $methodsWithThrows = 0;
    public int $tryBlocks = 0;
    
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            // カスタム例外クラスの検出
            if ($node->extends &&
                str_contains($node->extends->toString(), 'Exception')) {
                $this->customExceptions[] = $node->name->toString();
            }
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            $this->totalMethods++;
            
            // Docblock から @throws を抽出
            $docComment = $node->getDocComment();
            if ($docComment) {
                $text = $docComment->getText();
                if (preg_match_all('/@throws\s+(\S+)/', $text, $matches)) {
                    $this->methodsWithThrows++;
                    foreach ($matches[1] as $exception) {
                        $this->exceptionsThrown[$exception] =
                            ($this->exceptionsThrown[$exception] ?? 0) + 1;
                    }
                }
            }
        } elseif ($node instanceof Node\Stmt\Throw_) {
            if ($node->expr instanceof Node\Expr\New_ &&
                $node->expr->class instanceof Node\Name) {
                $exceptionName = $node->expr->class->toString();
                $this->exceptionsThrown[$exceptionName] =
                    ($this->exceptionsThrown[$exceptionName] ?? 0) + 1;
            }
        } elseif ($node instanceof Node\Stmt\TryCatch) {
            $this->tryBlocks++;
            foreach ($node->catches as $catch) {
                foreach ($catch->types as $type) {
                    $exceptionName = $type->toString();
                    $this->exceptionsCaught[$exceptionName] =
                        ($this->exceptionsCaught[$exceptionName] ?? 0) + 1;
                }
            }
        }
        
        return null;
    }
    
    public function getReport(): array
    {
        arsort($this->exceptionsThrown);
        arsort($this->exceptionsCaught);
        
        return [
            'custom_exceptions' => count($this->customExceptions),
            'throws_documentation_rate' => $this->totalMethods > 0
                ? round(($this->methodsWithThrows / $this->totalMethods) * 100, 2)
                : 0,
            'most_thrown_exceptions' => array_slice($this->exceptionsThrown, 0, 10),
            'most_caught_exceptions' => array_slice($this->exceptionsCaught, 0, 10),
            'exception_culture' => $this->determineExceptionCulture(),
        ];
    }
    
    private function determineExceptionCulture(): string
    {
        $thrownCount = array_sum($this->exceptionsThrown);
        $caughtCount = array_sum($this->exceptionsCaught);
        
        if ($thrownCount > $caughtCount * 2) {
            return '例外を積極的に使用（throw優勢）';
        } elseif ($caughtCount > $thrownCount * 2) {
            return '例外処理を重視（catch優勢）';
        } else {
            return '例外の投げる・受けるがバランス良い';
        }
    }
}
```

---

## 2. スタイルガイド文書の生成

### 2.1 生成スクリプト

```php
<?php
// generate_style_guide.php

class StyleGuideGenerator
{
    private array $analyses = [];
    
    public function addAnalysis(string $name, array $data): void
    {
        $this->analyses[$name] = $data;
    }
    
    public function generate(): string
    {
        $markdown = "# {$this->projectName} コーディングスタイルガイド\n\n";
        $markdown .= "抽出日: " . date('Y-m-d') . "\n\n";
        $markdown .= "---\n\n";
        
        // 命名規則
        $markdown .= $this->generateNamingSection();
        
        // 型宣言
        $markdown .= $this->generateTypeSection();
        
        // クラス設計
        $markdown .= $this->generateDesignSection();
        
        // エラーハンドリング
        $markdown .= $this->generateErrorHandlingSection();
        
        // 実例集
        $markdown .= $this->generateExamplesSection();
        
        return $markdown;
    }
    
    private function generateNamingSection(): string
    {
        $naming = $this->analyses['naming'];
        
        return <<<MD
        ## 1. 命名規則
        
        ### クラス名
        **ルール**: PascalCase を使用
        
        ```php
        ✓ class UserRepository { ... }
        ✓ class DatabaseConnection { ... }
        ✗ class user_repository { ... }
        ```
        
        ### メソッド名
        **ルール**: camelCase を使用
        
        ```php
        ✓ public function getUserById(int \$id): ?User
        ✓ public function isActive(): bool
        ✗ public function get_user_by_id(\$id)
        ```
        
        ### Boolean メソッド
        **ルール**: is/has/can/should で始める
        
        統計: is({$naming['boolean_prefixes']['is']}回), 
              has({$naming['boolean_prefixes']['has']}回)
        
        ```php
        ✓ public function isValid(): bool
        ✓ public function hasPermission(string \$permission): bool
        ✓ public function canExecute(): bool
        ```
        
        ---
        
        MD;
    }
    
    private function generateTypeSection(): string
    {
        $types = $this->analyses['types'];
        
        return <<<MD
        ## 2. 型宣言
        
        ### strict_types
        **必須**: すべてのファイルで declare(strict_types=1) を使用
        
        現在の使用率: {$types['strict_types_usage'] ? '100%' : '0%'}
        
        ```php
        <?php
        declare(strict_types=1);
        
        namespace App\\Domain;
        ```
        
        ### 引数と戻り値の型宣言
        **ルール**: すべての public/protected メソッドで型を宣言
        
        - 引数の型宣言カバレッジ: {$types['param_type_coverage']}%
        - 戻り値の型宣言カバレッジ: {$types['return_type_coverage']}%
        
        ```php
        ✓ public function process(string \$data, int \$flags = 0): Result
        ✗ public function process(\$data, \$flags = 0)
        ```
        
        ### nullable 型
        **ルール**: ? プレフィックスを使用
        
        ```php
        ✓ public function findUser(int \$id): ?User
        ✗ public function findUser(int \$id): User|null
        ```
        
        ---
        
        MD;
    }
    
    // ... 他のセクション生成メソッド
}

// 使用例
$generator = new StyleGuideGenerator('Monolog');
$generator->addAnalysis('naming', $namingAnalysis);
$generator->addAnalysis('types', $typeAnalysis);
$generator->addAnalysis('design', $designAnalysis);
$generator->addAnalysis('error_handling', $errorHandlingAnalysis);

$styleGuide = $generator->generate();
file_put_contents('STYLE_GUIDE.md', $styleGuide);
echo "スタイルガイドを生成しました: STYLE_GUIDE.md\n";
```

---

## 3. 抽出結果の検証

### 3.1 サンプルコードでの検証

```php
<?php
// validate_extraction.php

class StyleValidator
{
    private array $rules;
    
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }
    
    /**
     * 既存のプロジェクトコードがルールに準拠しているか検証
     */
    public function validate(string $projectPath): ValidationReport
    {
        $files = glob($projectPath . '/src/**/*.php');
        $totalViolations = 0;
        $fileReports = [];
        
        foreach ($files as $file) {
            $code = file_get_contents($file);
            $violations = $this->checkViolations($code);
            
            if (!empty($violations)) {
                $totalViolations += count($violations);
                $fileReports[$file] = $violations;
            }
        }
        
        $complianceRate = $totalViolations === 0
            ? 100
            : max(0, 100 - ($totalViolations / count($files)));
        
        return new ValidationReport(
            complianceRate: $complianceRate,
            totalViolations: $totalViolations,
            fileReports: $fileReports
        );
    }
    
    private function checkViolations(string $code): array
    {
        $violations = [];
        
        // 命名規則チェック
        if ($this->rules['naming']['class'] === 'PascalCase') {
            if (preg_match('/class\s+([a-z][a-zA-Z0-9_]*)/', $code, $matches)) {
                $violations[] = "クラス名がPascalCaseではありません: {$matches[1]}";
            }
        }
        
        // strict_types チェック
        if ($this->rules['type_declaration']['strict_types_required']) {
            if (!str_contains($code, 'declare(strict_types=1)')) {
                $violations[] = "strict_types=1 が宣言されていません";
            }
        }
        
        // ... 他のルールチェック
        
        return $violations;
    }
}

// 検証実行
$rules = [
    'naming' => [
        'class' => 'PascalCase',
        'method' => 'camelCase',
    ],
    'type_declaration' => [
        'strict_types_required' => true,
        'param_types_required' => true,
        'return_types_required' => true,
    ],
];

$validator = new StyleValidator($rules);
$report = $validator->validate('/path/to/monolog');

echo "スタイル準拠率: {$report->complianceRate}%\n";
echo "総違反数: {$report->totalViolations}\n";

if ($report->complianceRate >= 90) {
    echo "✓ 抽出されたルールは妥当です（既存コードの90%以上が準拠）\n";
} else {
    echo "⚠ ルールの見直しが必要です（準拠率が90%未満）\n";
}
```

---

## 4. 継続的な抽出の自動化

### 4.1 GitHub Actions ワークフロー

```yaml
# .github/workflows/extract-style.yml
name: Extract Coding Style

on:
  push:
    branches: [ main ]
  schedule:
    - cron: '0 0 * * 0'  # 毎週日曜日

jobs:
  extract:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer
      
      - name: Install dependencies
        run: composer install
      
      - name: Extract style
        run: php scripts/extract_style.php
      
      - name: Generate style guide
        run: php scripts/generate_style_guide.php
      
      - name: Commit changes
        run: |
          git config --local user.email "action@github.com"
          git config --local user.name "GitHub Action"
          git add STYLE_GUIDE.md style_rules.json
          git commit -m "Update style guide [skip ci]" || echo "No changes"
          git push
```

---

## 5. まとめと次のステップ

### 抽出プロセスのチェックリスト

```
Phase 1: 準備
☐ プロジェクトをクローン
☐ 依存関係をインストール
☐ プロジェクト概要を把握（README、構造）

Phase 2: 自動抽出
☐ 命名規則を抽出
☐ フォーマット規則を抽出
☐ 型宣言の使用状況を抽出
☐ クラス設計パターンを抽出
☐ エラーハンドリング方針を抽出

Phase 3: 分析と文書化
☐ 統計データを集計
☐ 支配的なパターンを特定
☐ スタイルガイドを生成
☐ 実例を収集

Phase 4: 検証
☐ 既存コードで準拠率を確認
☐ エッジケースを確認
☐ プロジェクトメンバーにレビュー依頼

Phase 5: 問題生成の準備
☐ 抽出したルールを構造化データに変換
☐ 問題テンプレートとのマッピング
☐ 評価基準の定義
```

### 実際のMonolog/Guzzleでの試行

```bash
# Monologでのスタイル抽出実行
cd /path/to/php-chatbot/monolog
php ../scripts/extract_naming.php > ../monolog_naming_analysis.txt
php ../scripts/extract_type_usage.php > ../monolog_type_analysis.json
php ../scripts/extract_design_patterns.php > ../monolog_design_analysis.json

# Guzzleでも同様に実行
cd ../guzzle
php ../scripts/extract_naming.php > ../guzzle_naming_analysis.txt
php ../scripts/extract_type_usage.php > ../guzzle_type_analysis.json
php ../scripts/extract_design_patterns.php > ../guzzle_design_analysis.json

# 比較分析
php ../scripts/compare_projects.php monolog guzzle
```

このアプローチにより、実際のプロジェクトから具体的で実用的なコーディングスタイルを抽出し、それを学習教材として活用できます。


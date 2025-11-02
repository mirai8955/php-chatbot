# 評価基準の設計

ユーザーの解答を多面的に評価し、具体的なフィードバックを提供するための評価システムを設計します。

## 1. 評価の基本構造

### 1.1 評価の4つの軸

```
1. スタイル準拠度（Style Compliance）
   → プロジェクトのコーディング規約にどれだけ従っているか

2. 機能正確性（Functional Correctness）
   → 要求仕様を満たしているか、バグはないか

3. 設計品質（Design Quality）
   → 可読性、保守性、拡張性は十分か

4. 実践的妥当性（Practical Viability）
   → 実務で使える品質か
```

### 1.2 配点の基本方針

```
問題の種類によって重み付けを変える：

【基礎レベル問題】
スタイル準拠度: 60%
機能正確性: 30%
設計品質: 10%
実践的妥当性: 0%

【中級レベル問題】
スタイル準拠度: 40%
機能正確性: 30%
設計品質: 25%
実践的妥当性: 5%

【上級レベル問題】
スタイル準拠度: 30%
機能正確性: 25%
設計品質: 30%
実践的妥当性: 15%

【エキスパート問題】
スタイル準拠度: 20%
機能正確性: 20%
設計品質: 35%
実践的妥当性: 25%
```

---

## 2. 軸1: スタイル準拠度の評価

### 2.1 ミクロレベルのチェック項目

#### 命名規則（20点）

```yaml
checks:
  class_naming:
    pattern: "^[A-Z][a-zA-Z0-9]*$"  # PascalCase
    points: 5
    error_message: "クラス名はPascalCaseで記述してください"
  
  method_naming:
    pattern: "^[a-z][a-zA-Z0-9]*$"  # camelCase
    points: 5
    error_message: "メソッド名はcamelCaseで記述してください"
  
  variable_naming:
    pattern: "^\\$[a-z][a-zA-Z0-9]*$"  # $camelCase
    points: 3
    error_message: "変数名はcamelCaseで記述してください"
  
  constant_naming:
    pattern: "^[A-Z][A-Z0-9_]*$"  # UPPER_SNAKE_CASE
    points: 3
    error_message: "定数名はUPPER_SNAKE_CASEで記述してください"
  
  boolean_prefix:
    pattern: "^(is|has|can|should)[A-Z]"  # is/has/can/should prefix
    points: 4
    error_message: "真偽値を返すメソッドはis/has/can/shouldで始めてください"
```

**評価実装例**:
```php
class NamingEvaluator {
    public function evaluate(string $code): array {
        $ast = $this->parser->parse($code);
        $violations = [];
        $score = 20; // 満点から開始
        
        foreach ($this->extractClassNames($ast) as $className) {
            if (!$this->isPascalCase($className)) {
                $violations[] = [
                    'type' => 'class_naming',
                    'name' => $className,
                    'deduction' => 5
                ];
                $score -= 5;
            }
        }
        
        // ... 他の項目も同様にチェック
        
        return [
            'score' => max(0, $score),
            'violations' => $violations
        ];
    }
}
```

#### 型宣言（15点）

```yaml
checks:
  parameter_types:
    required: true
    points: 5
    message: "引数には型宣言を付けてください"
  
  return_types:
    required: true
    points: 5
    message: "戻り値には型宣言を付けてください"
  
  property_types:
    required: true  # PHP 7.4+
    points: 3
    message: "プロパティには型宣言を付けてください"
  
  strict_types:
    required: true
    points: 2
    message: "ファイル先頭にdeclare(strict_types=1)を記述してください"
```

**評価ロジック**:
```php
public function evaluateTypeDeclarations(Node $ast): array {
    $methods = $this->extractMethods($ast);
    $totalMethods = count($methods);
    $typedParameters = 0;
    $typedReturns = 0;
    
    foreach ($methods as $method) {
        if ($this->hasParameterTypes($method)) {
            $typedParameters++;
        }
        if ($this->hasReturnType($method)) {
            $typedReturns++;
        }
    }
    
    $paramScore = ($typedParameters / $totalMethods) * 5;
    $returnScore = ($typedReturns / $totalMethods) * 5;
    
    return [
        'parameter_types' => round($paramScore, 1),
        'return_types' => round($returnScore, 1)
    ];
}
```

#### フォーマット（10点）

```yaml
checks:
  indentation:
    style: "spaces"  # or "tabs"
    size: 4
    points: 2
  
  brace_style:
    class: "same_line"  # or "next_line"
    method: "same_line"
    points: 2
  
  line_length:
    max: 120
    points: 2
  
  whitespace:
    after_comma: true
    around_operators: true
    points: 2
  
  array_syntax:
    style: "short"  # [] vs array()
    trailing_comma: true
    points: 2
```

**自動チェック**:
PHP_CodeSniffer や PHP-CS-Fixer のルールセットを活用

```php
public function evaluateFormatting(string $code): array {
    $report = $this->phpcs->processFile($code, $this->ruleset);
    
    $violations = $report->getViolations();
    $score = 10;
    
    foreach ($violations as $violation) {
        $severity = $violation['severity'];
        $score -= $this->getDeduction($severity);
    }
    
    return [
        'score' => max(0, $score),
        'violations' => $this->formatViolations($violations)
    ];
}
```

#### Docblock（10点）

```yaml
checks:
  public_method_docblock:
    required: true
    points: 4
    message: "publicメソッドにはDocblockを記述してください"
  
  param_tags:
    required: true
    points: 2
    message: "@paramタグを記述してください"
  
  return_tag:
    required: true
    points: 2
    message: "@returnタグを記述してください"
  
  description:
    required: true
    points: 2
    message: "メソッドの説明を記述してください"
```

**評価例**:
```php
public function evaluateDocblocks(Node $ast): array {
    $methods = $this->extractMethods($ast);
    $score = 0;
    $maxScore = 10;
    
    foreach ($methods as $method) {
        if ($method->isPublic()) {
            $docblock = $method->getDocComment();
            
            if ($docblock) {
                $score += 4;
                
                if ($this->hasParamTags($docblock)) $score += 2;
                if ($this->hasReturnTag($docblock)) $score += 2;
                if ($this->hasDescription($docblock)) $score += 2;
            }
        }
    }
    
    return [
        'score' => min($score, $maxScore),
        'coverage' => $this->calculateCoverage($methods)
    ];
}
```

### 2.2 メゾレベルのチェック項目

#### メソッド設計（15点）

```yaml
metrics:
  method_length:
    ideal: "< 20 lines"
    acceptable: "< 50 lines"
    deduction_per_10_lines: 1
    max_deduction: 5
  
  cyclomatic_complexity:
    ideal: "< 5"
    acceptable: "< 10"
    deduction_per_unit: 0.5
    max_deduction: 5
  
  nesting_depth:
    ideal: "< 3"
    acceptable: "< 5"
    deduction_per_level: 1
    max_deduction: 3
  
  parameter_count:
    ideal: "< 3"
    acceptable: "< 5"
    deduction: 2
```

**評価実装**:
```php
public function evaluateMethodDesign(Method $method): array {
    $scores = [];
    
    // 長さ
    $length = $method->getLineCount();
    if ($length < 20) {
        $scores['length'] = 5;
    } elseif ($length < 50) {
        $deduction = floor(($length - 20) / 10);
        $scores['length'] = max(0, 5 - $deduction);
    } else {
        $scores['length'] = 0;
    }
    
    // 複雑度
    $complexity = $this->calculateComplexity($method);
    if ($complexity < 5) {
        $scores['complexity'] = 5;
    } else {
        $deduction = ($complexity - 5) * 0.5;
        $scores['complexity'] = max(0, 5 - $deduction);
    }
    
    // ... 他の指標も同様
    
    return [
        'total' => array_sum($scores),
        'breakdown' => $scores,
        'metrics' => [
            'length' => $length,
            'complexity' => $complexity,
            // ...
        ]
    ];
}
```

#### クラス設計（10点）

```yaml
metrics:
  single_responsibility:
    check: "manual"  # AIまたは人間が判断
    points: 4
  
  cohesion:
    metric: "LCOM"  # Lack of Cohesion of Methods
    threshold: 0.5
    points: 3
  
  coupling:
    max_dependencies: 5
    points: 3
```

### 2.3 マクロレベルのチェック項目

#### アーキテクチャ準拠（10点）

```yaml
checks:
  layer_violation:
    description: "レイヤーの依存関係違反"
    examples:
      - "Domain層がInfrastructure層に依存"
      - "Controller が直接 Database にアクセス"
    deduction: 5
  
  pattern_usage:
    description: "プロジェクトの標準パターン使用"
    examples:
      - "Repository パターンの使用"
      - "Factory パターンの使用"
    points: 5
```

**評価方法**:
依存関係グラフを生成し、許可されていない依存を検出

```php
public function evaluateArchitecture(string $className, array $dependencies): array {
    $layer = $this->determineLayer($className);
    $violations = [];
    
    foreach ($dependencies as $dependency) {
        $depLayer = $this->determineLayer($dependency);
        
        if (!$this->isAllowedDependency($layer, $depLayer)) {
            $violations[] = [
                'from' => $className,
                'to' => $dependency,
                'reason' => "{$layer}は{$depLayer}に依存できません"
            ];
        }
    }
    
    $score = 10 - (count($violations) * 5);
    
    return [
        'score' => max(0, $score),
        'violations' => $violations
    ];
}
```

---

## 3. 軸2: 機能正確性の評価

### 3.1 基本的なテスト

```php
// テストケース定義
$testCases = [
    [
        'input' => ['id' => 1],
        'expected' => ['name' => 'John', 'email' => 'john@example.com'],
        'points' => 10
    ],
    [
        'input' => ['id' => 999],
        'expected' => null,  // 存在しないユーザー
        'points' => 5
    ],
    [
        'input' => ['id' => -1],
        'expected' => 'exception',  // 不正な入力
        'exception_type' => InvalidArgumentException::class,
        'points' => 5
    ]
];

// 実行と評価
public function evaluateFunctionality($userCode, array $testCases): array {
    $totalScore = 0;
    $results = [];
    
    foreach ($testCases as $test) {
        try {
            $actual = $this->executeUserCode($userCode, $test['input']);
            
            if ($test['expected'] === 'exception') {
                // 例外が投げられるべきだった
                $results[] = [
                    'passed' => false,
                    'message' => '例外が投げられませんでした',
                    'points' => 0
                ];
            } elseif ($this->assertEquals($actual, $test['expected'])) {
                $totalScore += $test['points'];
                $results[] = [
                    'passed' => true,
                    'points' => $test['points']
                ];
            } else {
                $results[] = [
                    'passed' => false,
                    'expected' => $test['expected'],
                    'actual' => $actual,
                    'points' => 0
                ];
            }
        } catch (Exception $e) {
            if ($test['expected'] === 'exception' &&
                get_class($e) === $test['exception_type']) {
                $totalScore += $test['points'];
                $results[] = [
                    'passed' => true,
                    'points' => $test['points']
                ];
            } else {
                $results[] = [
                    'passed' => false,
                    'message' => '予期しない例外: ' . $e->getMessage(),
                    'points' => 0
                ];
            }
        }
    }
    
    return [
        'score' => $totalScore,
        'results' => $results
    ];
}
```

### 3.2 エッジケースのカバレッジ

```php
// エッジケース定義
$edgeCases = [
    'empty_string' => '',
    'null_value' => null,
    'large_number' => PHP_INT_MAX,
    'special_characters' => "'; DROP TABLE users; --",
    'unicode' => '日本語テスト',
    'very_long_string' => str_repeat('a', 10000)
];
```

### 3.3 セキュリティチェック

```php
public function checkSecurity(string $code): array {
    $issues = [];
    
    // SQLインジェクション
    if ($this->hasSQLInjectionRisk($code)) {
        $issues[] = [
            'severity' => 'critical',
            'type' => 'sql_injection',
            'message' => 'SQLインジェクションの脆弱性があります',
            'deduction' => 20  // 大幅減点
        ];
    }
    
    // XSS
    if ($this->hasXSSRisk($code)) {
        $issues[] = [
            'severity' => 'high',
            'type' => 'xss',
            'message' => 'XSSの脆弱性があります',
            'deduction' => 15
        ];
    }
    
    // パスワード平文保存
    if ($this->hasPlainTextPassword($code)) {
        $issues[] = [
            'severity' => 'critical',
            'type' => 'plain_password',
            'message' => 'パスワードをハッシュ化してください',
            'deduction' => 20
        ];
    }
    
    return $issues;
}
```

---

## 4. 軸3: 設計品質の評価

### 4.1 可読性（Readability）

```php
public function evaluateReadability(string $code): array {
    $score = 10;
    $issues = [];
    
    // 変数名の意味性
    $meaningfulNameRatio = $this->checkMeaningfulNames($code);
    if ($meaningfulNameRatio < 0.8) {
        $deduction = (0.8 - $meaningfulNameRatio) * 10;
        $score -= $deduction;
        $issues[] = "意味のある変数名を使用してください";
    }
    
    // マジックナンバー
    $magicNumbers = $this->findMagicNumbers($code);
    if (count($magicNumbers) > 0) {
        $score -= count($magicNumbers) * 0.5;
        $issues[] = "マジックナンバーは定数にしてください: " .
                    implode(', ', $magicNumbers);
    }
    
    // コメント適切性
    $commentRatio = $this->calculateCommentRatio($code);
    if ($commentRatio < 0.05) {  // 5%未満
        $score -= 2;
        $issues[] = "複雑なロジックにはコメントを追加してください";
    } elseif ($commentRatio > 0.3) {  // 30%以上
        $score -= 1;
        $issues[] = "過度なコメントは避け、コードを自己説明的にしてください";
    }
    
    return [
        'score' => max(0, $score),
        'issues' => $issues,
        'metrics' => [
            'meaningful_names' => $meaningfulNameRatio,
            'comment_ratio' => $commentRatio
        ]
    ];
}
```

### 4.2 保守性（Maintainability）

```php
public function evaluateMaintainability(string $code): array {
    // Maintainability Index の計算
    // MI = 171 - 5.2 * ln(HV) - 0.23 * CC - 16.2 * ln(LOC)
    // HV: Halstead Volume
    // CC: Cyclomatic Complexity
    // LOC: Lines of Code
    
    $metrics = [
        'halstead_volume' => $this->calculateHalsteadVolume($code),
        'cyclomatic_complexity' => $this->calculateComplexity($code),
        'lines_of_code' => $this->countLines($code)
    ];
    
    $mi = 171 
        - 5.2 * log($metrics['halstead_volume'])
        - 0.23 * $metrics['cyclomatic_complexity']
        - 16.2 * log($metrics['lines_of_code']);
    
    // MIを0-10のスコアに変換
    // MI >= 85: 非常に良好（10点）
    // MI >= 65: 良好（7点）
    // MI >= 50: 普通（5点）
    // MI < 50: 改善必要（2点）
    
    if ($mi >= 85) {
        $score = 10;
        $rating = '非常に良好';
    } elseif ($mi >= 65) {
        $score = 7;
        $rating = '良好';
    } elseif ($mi >= 50) {
        $score = 5;
        $rating = '普通';
    } else {
        $score = 2;
        $rating = '改善が必要';
    }
    
    return [
        'score' => $score,
        'rating' => $rating,
        'maintainability_index' => round($mi, 2),
        'metrics' => $metrics
    ];
}
```

### 4.3 テスタビリティ（Testability）

```php
public function evaluateTestability(Node $ast): array {
    $score = 10;
    $issues = [];
    
    // 依存性注入の使用
    if (!$this->usesConstructorInjection($ast)) {
        $score -= 3;
        $issues[] = "コンストラクタインジェクションを使用してください";
    }
    
    // グローバル状態への依存
    $globalReferences = $this->findGlobalReferences($ast);
    if (count($globalReferences) > 0) {
        $score -= 4;
        $issues[] = "グローバル変数への依存を避けてください: " .
                    implode(', ', $globalReferences);
    }
    
    // staticメソッドの過度な使用
    $staticMethodCount = $this->countStaticMethods($ast);
    if ($staticMethodCount > 2) {
        $score -= 2;
        $issues[] = "staticメソッドの使用は最小限にしてください";
    }
    
    // 副作用のある関数
    $functionsWithSideEffects = $this->findFunctionsWithSideEffects($ast);
    if (count($functionsWithSideEffects) > 0) {
        $score -= 1;
        $issues[] = "純粋関数を目指してください";
    }
    
    return [
        'score' => max(0, $score),
        'issues' => $issues
    ];
}
```

---

## 5. 軸4: 実践的妥当性の評価

### 5.1 パフォーマンス考慮

```php
public function evaluatePerformance(string $code): array {
    $issues = [];
    $score = 10;
    
    // N+1問題
    if ($this->hasNPlusOneProblem($code)) {
        $score -= 5;
        $issues[] = [
            'type' => 'n_plus_one',
            'message' => 'N+1クエリ問題が検出されました',
            'suggestion' => 'Eager Loadingを検討してください'
        ];
    }
    
    // 非効率なループ
    if ($this->hasInefficientLoop($code)) {
        $score -= 2;
        $issues[] = [
            'type' => 'inefficient_loop',
            'message' => 'ループ内で重い処理をしています',
            'suggestion' => 'ループ外に移動できないか検討してください'
        ];
    }
    
    // 不要なデータベースクエリ
    $unnecessaryQueries = $this->findUnnecessaryQueries($code);
    if (count($unnecessaryQueries) > 0) {
        $score -= 2;
        $issues[] = [
            'type' => 'unnecessary_queries',
            'message' => '不要なクエリが検出されました',
            'count' => count($unnecessaryQueries)
        ];
    }
    
    return [
        'score' => max(0, $score),
        'issues' => $issues
    ];
}
```

### 5.2 エラーハンドリング

```php
public function evaluateErrorHandling(Node $ast): array {
    $score = 10;
    $feedback = [];
    
    // 例外の適切な使用
    $exceptionUsage = $this->analyzeExceptionUsage($ast);
    if ($exceptionUsage['custom_exceptions'] === 0) {
        $score -= 2;
        $feedback[] = "カスタム例外を定義してください";
    }
    
    // エラーの適切なログ記録
    $errorLogging = $this->checkErrorLogging($ast);
    if (!$errorLogging) {
        $score -= 3;
        $feedback[] = "エラーを適切にログに記録してください";
    }
    
    // try-catch の適切性
    $tryCatchBlocks = $this->analyzeTryCatchBlocks($ast);
    foreach ($tryCatchBlocks as $block) {
        if ($this->catchesTooBroad($block)) {
            $score -= 1;
            $feedback[] = "Exception をキャッチするのではなく、" .
                         "具体的な例外をキャッチしてください";
        }
        if ($this->emptyC atchBlock($block)) {
            $score -= 2;
            $feedback[] = "空の catch ブロックは避けてください";
        }
    }
    
    return [
        'score' => max(0, $score),
        'feedback' => $feedback
    ];
}
```

### 5.3 実務適用性

```php
public function evaluatePracticalViability(string $code, array $context): array {
    // プロジェクトメンバーによるレビュー（手動）
    // または、過去の類似コードとの比較（AI）
    
    $criteria = [
        'team_familiarity' => [
            'question' => 'チームメンバーが理解しやすいコードか？',
            'weight' => 0.3
        ],
        'maintenance_burden' => [
            'question' => '保守コストは許容範囲か？',
            'weight' => 0.3
        ],
        'integration_ease' => [
            'question' => '既存コードに統合しやすいか？',
            'weight' => 0.2
        ],
        'future_extensibility' => [
            'question' => '将来の拡張に対応できるか？',
            'weight' => 0.2
        ]
    ];
    
    // AIによる評価または人間による評価
    $ratings = $this->getRatings($code, $criteria);
    
    $totalScore = 0;
    foreach ($criteria as $key => $criterion) {
        $totalScore += $ratings[$key] * $criterion['weight'];
    }
    
    return [
        'score' => round($totalScore * 10, 1),  // 0-10スケール
        'ratings' => $ratings
    ];
}
```

---

## 6. 総合評価とフィードバック生成

### 6.1 スコア集計

```php
public function calculateFinalScore(array $evaluations, int $level): array {
    // レベルに応じた重み付け
    $weights = $this->getWeightsForLevel($level);
    
    $totalScore = 0;
    $maxScore = 100;
    
    foreach ($evaluations as $category => $result) {
        $weightedScore = $result['score'] * $weights[$category];
        $totalScore += $weightedScore;
    }
    
    // グレード判定
    $grade = $this->determineGrade($totalScore);
    
    return [
        'total_score' => round($totalScore, 1),
        'max_score' => $maxScore,
        'percentage' => round(($totalScore / $maxScore) * 100, 1),
        'grade' => $grade,
        'breakdown' => $this->formatBreakdown($evaluations, $weights)
    ];
}

private function determineGrade(float $score): string {
    if ($score >= 90) return 'S';  // 優秀
    if ($score >= 80) return 'A';  // 良好
    if ($score >= 70) return 'B';  // 合格
    if ($score >= 60) return 'C';  // ギリギリ合格
    return 'D';  // 不合格（再提出推奨）
}
```

### 6.2 フィードバックメッセージ生成

```php
public function generateFeedback(array $evaluations): string {
    $feedback = [];
    
    // 1. 全体評価
    $feedback[] = $this->generateOverallFeedback($evaluations['total_score']);
    
    // 2. 優れている点
    $strengths = $this->identifyStrengths($evaluations);
    if (!empty($strengths)) {
        $feedback[] = "\n【優れている点】";
        foreach ($strengths as $strength) {
            $feedback[] = "✓ " . $strength;
        }
    }
    
    // 3. 改善すべき点（重要度順）
    $improvements = $this->identifyImprovements($evaluations);
    if (!empty($improvements)) {
        $feedback[] = "\n【改善すべき点】";
        foreach ($improvements as $i => $improvement) {
            $priority = $i < 3 ? '【重要】' : '';
            $feedback[] = "{$priority}× " . $improvement['message'];
            $feedback[] = "  💡 " . $improvement['suggestion'];
        }
    }
    
    // 4. 次のステップ
    $feedback[] = "\n【次のステップ】";
    $feedback[] = $this->suggestNextSteps($evaluations);
    
    // 5. 参考リソース
    $resources = $this->suggestResources($evaluations);
    if (!empty($resources)) {
        $feedback[] = "\n【参考リソース】";
        foreach ($resources as $resource) {
            $feedback[] = "📚 " . $resource;
        }
    }
    
    return implode("\n", $feedback);
}

private function generateOverallFeedback(float $score): string {
    if ($score >= 90) {
        return "🌟 素晴らしい！プロジェクトのスタイルを完璧に理解しています。";
    } elseif ($score >= 80) {
        return "👍 とても良い出来です。細かい点を改善すれば完璧です。";
    } elseif ($score >= 70) {
        return "😊 良い方向です。いくつかの重要な点を改善しましょう。";
    } elseif ($score >= 60) {
        return "🤔 基本はできていますが、プロジェクトスタイルの理解を深めましょう。";
    } else {
        return "📝 もう一度プロジェクトのスタイルガイドを確認しましょう。";
    }
}
```

### 6.3 具体的改善案の生成

```php
public function generateImprovementSuggestions(array $violations): array {
    $suggestions = [];
    
    foreach ($violations as $violation) {
        $suggestion = [
            'issue' => $violation['message'],
            'current' => $violation['current_code'],
            'improved' => $this->generateImprovedCode($violation),
            'explanation' => $this->explainWhy($violation)
        ];
        
        $suggestions[] = $suggestion;
    }
    
    return $suggestions;
}

// 例
[
    'issue' => 'クラス名がPascalCaseではありません',
    'current' => 'class user_repository { ... }',
    'improved' => 'class UserRepository { ... }',
    'explanation' => 'プロジェクトではPascalCaseを使用することで、
                      クラスであることが一目で分かり、可読性が向上します。'
]
```

---

## 7. 評価結果の可視化

### 7.1 レーダーチャート

```php
public function generateRadarChartData(array $evaluations): array {
    return [
        'labels' => [
            'スタイル準拠',
            '機能正確性',
            '可読性',
            '保守性',
            'テスタビリティ',
            'パフォーマンス'
        ],
        'datasets' => [
            [
                'label' => 'あなたのスコア',
                'data' => [
                    $evaluations['style']['score'],
                    $evaluations['functionality']['score'],
                    $evaluations['readability']['score'],
                    $evaluations['maintainability']['score'],
                    $evaluations['testability']['score'],
                    $evaluations['performance']['score']
                ]
            ],
            [
                'label' => '目標スコア',
                'data' => [8, 8, 8, 8, 8, 8]  // レベルに応じて変動
            ]
        ]
    ];
}
```

### 7.2 進捗トラッキング

```php
public function trackProgress(int $userId, array $scores): void {
    $this->database->insert('user_progress', [
        'user_id' => $userId,
        'problem_id' => $this->problemId,
        'score' => $scores['total_score'],
        'style_score' => $scores['style'],
        'functionality_score' => $scores['functionality'],
        'design_score' => $scores['design'],
        'timestamp' => time()
    ]);
    
    // 成長曲線の可視化用データ
    $history = $this->getScoreHistory($userId);
    return $this->visualizeProgress($history);
}
```

---

## まとめ

効果的な評価システムには：

1. **多面的な評価**: 4つの軸でバランスよく評価
2. **自動化と柔軟性**: 機械的にチェックできる部分は自動化、判断が必要な部分は柔軟に
3. **建設的なフィードバック**: 単なる減点ではなく、改善の方向性を示す
4. **可視化**: 進捗が見えるとモチベーション向上
5. **段階的評価**: レベルに応じて重視するポイントを変える

次のステップ: [05_level_system.md](./05_level_system.md)で、レベルシステムとスキルツリーを設計します。


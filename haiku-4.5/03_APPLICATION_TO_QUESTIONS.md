# 抽出スタイルの問題出題・評価への応用

## 概要

このドキュメントは、抽出したコーディングスタイル情報を、実際の問題出題と解答評価に活用する方法を説明します。

---

## Part 1: 問題設計のフレームワーク

### 1.1 レベル分けの定義

プロジェクト固有のスタイルを習得するまでの段階を定義します：

```yaml
learning_levels:
  
  level_1_foundation:
    name: "基礎（ファンデーション）"
    duration: "1週間"
    focus: "マイクロレベルの理解"
    topics:
      - インデント・フォーマッティング
      - ネーミング規則（変数、メソッド）
      - スペーシング・クォート
      - PHPDoc基礎
    
    success_criteria:
      - PSR-12準拠のコード記述
      - 一貫性のある命名規則
      - 90%以上のフォーマッティング一致度
  
  level_2_intermediate:
    name: "中級（ミッドレベル）"
    duration: "2週間"
    focus: "メソッド・クラス設計パターン"
    topics:
      - メソッドサイズの適正化
      - パラメータ型ヒント
      - 戻り値型ヒント
      - visibility（private/public）の使い分け
      - 型ヒント85%以上の採用
    
    success_criteria:
      - 型ヒント率80%以上
      - メソッド平均15行以下
      - 適切なvisibility使い分け
  
  level_3_advanced:
    name: "上級（マクロレベル）"
    duration: "2週間"
    focus: "アーキテクチャ・設計パターン"
    topics:
      - プロジェクトのディレクトリ構造理解
      - インターフェース・実装の分離（85%）
      - Trait活用
      - エラーハンドリング戦略
      - 依存性注入パターン
    
    success_criteria:
      - インターフェース設計の理解
      - 適切な例外処理
      - DI原則に基づく実装
  
  level_4_expert:
    name: "エキスパート（統合）"
    duration: "1週間"
    focus: "全層の統合理解"
    topics:
      - プロジェクト全体の哲学理解
      - 新機能の実装提案
      - コードレビューの実施
      - パフォーマンス最適化提案
    
    success_criteria:
      - 総合スコア90%以上
      - 複合的な設計判断
      - 説明能力の高度性
```

### 1.2 問題タイプの定義

各レベルに対応した問題タイプを定義します：

```yaml
question_types:
  
  type_1_fix_formatting:
    name: "フォーマッティング修正"
    level: 1
    difficulty: easy
    time_limit: "5分"
    
    structure:
      - 不正なフォーマットのコード提示
      - 「プロジェクトのスタイルに合わせて修正」と指示
      - 評価: フォーマッティング一致度
    
    example: |
      「以下のコードをこのプロジェクトのスタイルに合わせて修正してください」
      ```php
      function getData($id){return $this->data[$id];}
      ```
  
  type_2_naming_convention:
    name: "命名規則の適用"
    level: 1
    difficulty: easy
    time_limit: "5分"
    
    structure:
      - 不適切な命名のコード
      - 「適切な命名に変更」と指示
      - 評価: 命名規則一致度
    
    example: |
      「このメソッドに適切な名前を付けてください」
      ```php
      public function check_if_valid($data) {
          // バリデーション処理
      }
      ```
      正解例: `isValid()`, `validate()`, `validateData()`
  
  type_3_type_hints:
    name: "型ヒント追加"
    level: 2
    difficulty: medium
    time_limit: "10分"
    
    structure:
      - 型ヒントのないメソッド
      - 「適切な型ヒントを追加」と指示
      - 評価: 型ヒント採用率、正確性
    
    example: |
      「以下のメソッドに型ヒントを追加してください」
      ```php
      public function request($method, $uri, $options = []) {
          // 実装
      }
      ```
      正解例:
      ```php
      public function request(
          string $method,
          string $uri,
          array $options = []
      ): ResponseInterface {
      }
      ```
  
  type_4_refactor_method:
    name: "メソッドのリファクタリング"
    level: 2
    difficulty: medium
    time_limit: "15分"
    
    structure:
      - 責任が大きすぎるメソッド
      - 「プロジェクトのスタイルに合わせてリファクタ」と指示
      - 評価: メソッドサイズ、責任分離、命名
    
    example: |
      「以下のメソッドを分割してください（平均15行を目安）」
      ```php
      public function processRequest($request) {
          // 30行のメソッド：複数の責任が混在
      }
      ```
  
  type_5_design_pattern:
    name: "デザインパターン実装"
    level: 3
    difficulty: hard
    time_limit: "20分"
    
    structure:
      - 「このプロジェクトのパターンに従って実装」と指示
      - 複数の責務（インターフェース、実装、テスト）
      - 評価: パターン理解度、実装品質
    
    example: |
      「以下の機能をHandlerパターンで実装してください」
      - JSONハンドラーの作成
      - 既存HandlerInterfaceを実装
      - 複数のコンテンツタイプをサポート
  
  type_6_error_handling:
    name: "エラーハンドリング設計"
    level: 3
    difficulty: hard
    time_limit: "20分"
    
    structure:
      - エラーハンドリングが必要な処理
      - 「プロジェクトの例外戦略に従って実装」と指示
      - 評価: 例外設計、キャッチ戦略
    
    example: |
      「ファイル操作で発生しうるエラーを適切に処理してください」
      正解方針:
      - 適切な例外クラスを選択/作成
      - メッセージにコンテキスト情報を含める
      - 呼び元での処理を容易にする
```

### 1.3 問題出題テンプレート

#### Template A: マイクロレベル問題

```markdown
## 問題: {{problem_id}}

**レベル**: {{level}}  
**難易度**: {{difficulty}}  
**時間**: {{time_limit}}

### 問題文
以下のコードを、このプロジェクトのコーディングスタイルに合わせて修正してください。

### 提示コード
```php
{{provided_code}}
```

### ガイドライン
- インデント: {{macro_level.indentation}}
- ブレーススタイル: {{macro_level.brace_style}}
- 行最大長: {{macro_level.line_length_max}}
- 命名規則: {{mid_level.naming_patterns}}

### 評価ポイント
- [ ] フォーマッティング一致度: {{micro_level.formatting_consistency}}%
- [ ] 命名規則: {{mid_level.naming_consistency}}%
- [ ] スペーシング: {{micro_level.spacing_consistency}}%

---

**ヒント**: {{hint}}

**参考例**: 
```php
{{reference_example}}
```
```

#### Template B: ミッドレベル問題

```markdown
## 問題: {{problem_id}}

**レベル**: ミッド  
**難易度**: {{difficulty}}  
**時間**: {{time_limit}}

### 問題文
以下のメソッドを、このプロジェクトの設計パターンに合わせてリファクタリングしてください。

### 提示メソッド
```php
{{provided_method}}
```

### 要件
- 型ヒント率: 85%以上
- メソッド平均サイズ: 15行以下
- visibility戦略: {{mid_level.visibility_strategy}}
- パラメータパターン: {{mid_level.parameter_patterns}}

### 評価ポイント
- [ ] 型ヒント採用率: {{type_hint_percentage}}%
- [ ] メソッドサイズの適正化
- [ ] 責任分離の明確性
- [ ] visibility使い分けの適切性

---

**参考**: このプロジェクトでは{{example_pattern}}パターンを使用しています。
```

#### Template C: マクロレベル問題

```markdown
## 問題: {{problem_id}}

**レベル**: 上級  
**難易度**: {{difficulty}}  
**時間**: {{time_limit}}

### 問題文
{{problem_description}}

### 実装要件
- アーキテクチャ: {{macro_level.architecture_type}}
- デザインパターン: {{macro_level.design_patterns}}
- DI方針: {{macro_level.dependency_management}}
- エラー戦略: {{macro_level.error_handling}}

### ファイル構成
```
{{required_file_structure}}
```

### 評価ポイント
- [ ] ディレクトリ構造の適切性
- [ ] インターフェース設計（採用率85%）
- [ ] 依存性注入パターンの正確性
- [ ] エラーハンドリング戦略の一貫性
- [ ] 全体的な設計の一貫性

---

**参考実装**: `src/{{reference_path}}`
```

---

## Part 2: 解答評価のフレームワーク

### 2.1 スコアリングモデル

```yaml
scoring_system:
  total_points: 100
  
  micro_level_score:
    weight: 25
    components:
      formatting:
        weight: 10
        criteria:
          - indentation_consistency: 5
          - brace_style_consistency: 3
          - line_length_compliance: 2
      
      spacing:
        weight: 8
        criteria:
          - operator_spacing: 3
          - comma_spacing: 2
          - function_call_spacing: 3
      
      naming:
        weight: 7
        criteria:
          - variable_naming: 3
          - method_naming: 3
          - constant_naming: 1
  
  mid_level_score:
    weight: 40
    components:
      type_hints:
        weight: 15
        criteria:
          - parameter_type_hints: 8
          - return_type_hints: 7
      
      method_design:
        weight: 15
        criteria:
          - method_size: 7
          - responsibility_separation: 5
          - parameter_count: 3
      
      visibility:
        weight: 10
        criteria:
          - public_methods: 3
          - private_methods: 4
          - protected_methods: 3
  
  macro_level_score:
    weight: 35
    components:
      architecture:
        weight: 12
        criteria:
          - directory_structure: 4
          - module_organization: 4
          - naming_consistency: 4
      
      design_patterns:
        weight: 12
        criteria:
          - interface_implementation: 6
          - pattern_application: 4
          - consistency_with_project: 2
      
      error_handling:
        weight: 11
        criteria:
          - exception_hierarchy: 5
          - error_cases_coverage: 4
          - context_information: 2
```

### 2.2 詳細評価ルール

#### マイクロレベル評価

```yaml
micro_level_evaluation:
  
  indentation:
    rule: "4スペース一貫性"
    checks:
      - "全メソッド内のインデント4スペースか"
      - "ネストが深い場合、8スペス（2段階）か"
      - "配列要素のインデント一貫性"
    scoring:
      100: "全て一貫"
      80: "1-2箇所の例外"
      60: "3-5箇所の例外"
      40: "6-10箇所の例外"
      0: "一貫性なし"
  
  brace_style:
    rule: "K&R style（開き括弧は同じ行）"
    checks:
      - "if/while/forの開き括弧は同じ行か"
      - "クラス/メソッドの開き括弧は同じ行か"
      - "閉じ括弧は常に新しい行か"
    scoring:
      100: "完全一致"
      90: "1-2箇所の例外"
      70: "3-5箇所の例外"
      50: "混在している"
      0: "全く異なるスタイル"
  
  spacing:
    rule: "演算子前後、カンマ後にスペース"
    checks:
      - "二項演算子前後にスペースがあるか"
      - "カンマの後に必ずスペースがあるか"
      - "関数呼び出しで( 前にスペースなしか"
    scoring:
      100: "完全一致"
      85: "1-2箇所の例外"
      70: "3-5箇所の例外"
      50: "不規則"
      0: "ルールなし"
  
  naming:
    rule: "camelCase（メソッド）、snake_case（変数）"
    checks:
      - "メソッドはcamelCaseか"
      - "クラス定数はUPPER_SNAKEか"
      - "プロパティはcamelCaseか"
    scoring:
      100: "全て一貫"
      90: "1つの例外"
      70: "2-3つの例外"
      50: "複数の例外"
      0: "ルール無視"
```

#### ミッドレベル評価

```yaml
mid_level_evaluation:
  
  type_hints:
    rule: "85%以上の型ヒント採用"
    checks:
      - parameter_type_hints:
          target: 85%
          calculation: "型ヒント付きパラメータ数 / 全パラメータ数"
      - return_type_hints:
          target: 75%
          calculation: "型ヒント付き戻り値 / 全メソッド数"
    scoring:
      100: "90%以上"
      80: "80-89%"
      60: "70-79%"
      40: "60-69%"
      0: "60%未満"
  
  method_size:
    rule: "平均15行以下"
    checks:
      - "最長メソッド50行以下か"
      - "平均メソッドサイズ15行以下か"
      - "小さなメソッドが多く、責任が小さいか"
    scoring:
      100: "平均 < 12行"
      80: "平均 12-15行"
      60: "平均 15-20行"
      40: "平均 20-30行"
      0: "平均 > 30行"
  
  responsibility_separation:
    rule: "メソッドは単一責任を持つ"
    checks:
      - "メソッドが複数の処理をしていないか"
      - "名前とメソッド内容が一致しているか"
      - "副作用を起こしていないか"
    scoring:
      100: "完全に単一責任"
      80: "主に単一責任（軽微な例外1つ）"
      60: "複数責任が混在しているが基本は守れている"
      40: "複数責任が混在"
      0: "責任が全く整理されていない"
  
  visibility:
    rule: "private 55%, public 30%, protected 15%"
    checks:
      - "privateメソッドが多いか（55%程度）"
      - "publicメソッドは最小限か（30%程度）"
      - "protectedは必要な場合のみか（15%程度）"
    scoring:
      100: "推奨比率 ±5%以内"
      80: "推奨比率 ±10%以内"
      60: "推奨比率 ±15%以内"
      40: "推奨比率 ±25%以内"
      0: "比率が大きく異なる"
```

#### マクロレベル評価

```yaml
macro_level_evaluation:
  
  architecture_consistency:
    rule: "プロジェクトのアーキテクチャに従っているか"
    checks:
      - "ディレクトリ構造が適切か"
      - "モジュール分割が適切か"
      - "責務が明確に分離されているか"
    scoring:
      100: "完全にプロジェクト方針に従っている"
      80: "主に従っている（軽微な例外1つ）"
      60: "基本は従っているが改善の余地あり"
      40: "いくつかの異なり"
      0: "プロジェクト方針と異なる"
  
  interface_implementation:
    rule: "インターフェース採用率85%"
    checks:
      - "public classが実装を提供しているか"
      - "インターフェースが存在するか"
      - "複数の実装が可能か"
    scoring:
      100: "インターフェース + 実装で完全に分離"
      80: "ほぼ分離されている"
      60: "部分的に分離"
      40: "分離されているが不完全"
      0: "インターフェースなし、具象クラスのみ"
  
  design_pattern_usage:
    rule: "プロジェクト特有のパターンを使用しているか"
    checks:
      - "Handlerパターンを使用しているか"
      - "Middlewareパターンを使用しているか"
      - "パターンが一貫しているか"
    scoring:
      100: "全て一貫して採用"
      80: "主に採用（軽微な例外1つ）"
      60: "部分的に採用"
      40: "採用が不十分"
      0: "採用なし"
  
  error_handling:
    rule: "例外ベースのエラーハンドリング（70%）"
    checks:
      - "適切な例外クラスを選択しているか"
      - "例外メッセージに十分な情報があるか"
      - "例外階層に従っているか"
    scoring:
      100: "70%以上が適切な例外処理"
      80: "60-69%が適切"
      60: "50-59%が適切"
      40: "40-49%が適切"
      0: "40%未満"
```

### 2.3 評価実行スクリプト（PHP）

```php
<?php
// evaluation_engine/CodeEvaluator.php

class CodeEvaluator {
    private $styleProfile;
    private $scores = [];
    
    public function __construct(array $styleProfile) {
        $this->styleProfile = $styleProfile;
    }
    
    public function evaluate(string $userCode): array {
        $this->scores = [];
        
        // 各レベルの評価実行
        $microScore = $this->evaluateMicroLevel($userCode);
        $midScore = $this->evaluateMidLevel($userCode);
        $macroScore = $this->evaluateMacroLevel($userCode);
        
        // 加重合計
        $totalScore = 
            ($microScore * 0.25) +
            ($midScore * 0.40) +
            ($macroScore * 0.35);
        
        return [
            'micro_level' => $microScore,
            'mid_level' => $midScore,
            'macro_level' => $macroScore,
            'total_score' => round($totalScore, 1),
            'feedback' => $this->generateFeedback(),
        ];
    }
    
    private function evaluateMicroLevel(string $code): float {
        $score = 0;
        $maxScore = 100;
        
        // インデント検査
        $indentScore = $this->checkIndentation($code);
        $score += $indentScore * 0.10;
        
        // ブレーススタイル検査
        $braceScore = $this->checkBraceStyle($code);
        $score += $braceScore * 0.10;
        
        // スペーシング検査
        $spacingScore = $this->checkSpacing($code);
        $score += $spacingScore * 0.08;
        
        // 命名規則検査
        $namingScore = $this->checkNaming($code);
        $score += $namingScore * 0.07;
        
        return min($score, $maxScore);
    }
    
    private function evaluateMidLevel(string $code): float {
        $score = 0;
        $maxScore = 100;
        
        // 型ヒント検査
        $typeHintScore = $this->checkTypeHints($code);
        $score += $typeHintScore * 0.15;
        
        // メソッドサイズ検査
        $methodSizeScore = $this->checkMethodSize($code);
        $score += $methodSizeScore * 0.15;
        
        // 責任分離検査
        $responsibilityScore = $this->checkResponsibilitySeparation($code);
        $score += $responsibilityScore * 0.10;
        
        return min($score, $maxScore);
    }
    
    private function evaluateMacroLevel(string $code): float {
        // マクロレベルは手作業評価が多いため、簡易版
        $score = 0;
        $maxScore = 100;
        
        // インターフェース検査
        if ($this->detectInterfaceUsage($code)) {
            $score += 50;
        }
        
        // 例外処理検査
        if ($this->detectProperErrorHandling($code)) {
            $score += 50;
        }
        
        return $score;
    }
    
    private function checkIndentation(string $code): float {
        $lines = explode("\n", $code);
        $indentCount = 0;
        $totalCount = 0;
        
        foreach ($lines as $line) {
            if (preg_match('/^(\s+)/', $line, $matches)) {
                $totalCount++;
                $indent = strlen($matches[1]);
                if ($indent % 4 === 0) {
                    $indentCount++;
                }
            }
        }
        
        return $totalCount > 0 
            ? ($indentCount / $totalCount) * 100 
            : 0;
    }
    
    private function checkTypeHints(string $code): float {
        $totalParams = 0;
        $typedParams = 0;
        
        // パラメータの型ヒント検査
        if (preg_match_all('/function\s+\w+\s*\((.*?)\)/', $code, $matches)) {
            foreach ($matches[1] as $params) {
                $paramList = explode(',', $params);
                foreach ($paramList as $param) {
                    if (trim($param) !== '') {
                        $totalParams++;
                        if (preg_match('/^[\s]*(string|int|array|bool|float|\\\\?\w+)/', $param)) {
                            $typedParams++;
                        }
                    }
                }
            }
        }
        
        return $totalParams > 0 
            ? ($typedParams / $totalParams) * 100 
            : 0;
    }
    
    private function checkMethodSize(string $code): float {
        $methods = [];
        
        if (preg_match_all('/function\s+\w+\s*\(.*?\)\s*{(.*?)^[\s]*}/ms', $code, $matches)) {
            foreach ($matches[1] as $body) {
                $lines = count(array_filter(explode("\n", $body)));
                $methods[] = $lines;
            }
        }
        
        if (empty($methods)) {
            return 100;
        }
        
        $avgSize = array_sum($methods) / count($methods);
        
        if ($avgSize < 12) return 100;
        elseif ($avgSize < 15) return 80;
        elseif ($avgSize < 20) return 60;
        else return 40;
    }
    
    private function generateFeedback(): string {
        // 各スコアに基づいてフィードバック生成
        return "詳細なフィードバック文";
    }
}
```

---

## Part 3: フィードバック生成

### 3.1 段階的フィードバックの構造

```yaml
feedback_structure:
  
  positive_feedback:
    excellent: |
      素晴らしい！このコードは{{aspect}}において、プロジェクトの方針を完璧に
      実装できています。特に{{specific_point}}の部分は参考になります。
    good: |
      良くできています。{{aspect}}は適切に対応できてますね。
      さらに改善するなら{{suggestion}}を検討してみてください。
  
  improvement_feedback:
    minor_issue: |
      {{aspect}}にちょっとした改善の余地があります。
      {{current_state}}をプロジェクト方針では{{expected_state}}としています。
      
      修正例：
      ```php
      {{before_code}}
      ↓
      {{after_code}}
      ```
    
    major_issue: |
      {{aspect}}において大きく改善が必要です。
      
      現在のアプローチ：{{current_approach}}
      プロジェクト方針：{{project_approach}}
      
      このプロジェクトではこのような場合、以下のパターンを使用しています：
      {{pattern_example}}
      
      参考実装：{{reference_code}}
  
  critical_issue: |
    {{aspect}}は根本的に見直しが必要です。
    
    問題点：{{problem}}
    
    理由：このプロジェクトでは{{reason}}だからです。
    
    正しいアプローチ：
    {{correct_approach}}
    
    詳しくは`{{reference_doc}}`を参照してください。
```

### 3.2 フィードバック生成例

```markdown
## あなたの解答評価

### 総合スコア: 78/100 ⭐⭐⭐⭐

---

### マイクロレベル: 85/100 ✓ 良好

**インデント** ✓ 完璧
- 4スペースインデントが完全に一貫しています。

**命名規則** ✓ 良好
- メソッド名は適切なcamelCaseが使われています。
- **改善ポイント**: `check_if_valid()` → `isValid()` の方が、プロジェクト慣例に合っています。

**スペーシング** ⚠️ 要改善（-5点）
- 演算子前後のスペーシングはほぼ良好です。
- 1箇所、カンマ後のスペースが漏れています（行12）:
  ```php
  // 現在:
  $params = array($a,$b,$c);
  
  // 修正:
  $params = array($a, $b, $c);
  ```

---

### ミッドレベル: 72/100 ⭐ 要改善

**型ヒント** ⚠️ 要改善（-8点）
- 現在の採用率: 65%
- プロジェクト目標: 85%以上
- パラメータのうち3つに型ヒントがありません。

  改善例：
  ```php
  // 現在:
  public function processRequest($request, $options = []) {
  
  // 修正:
  public function processRequest(
      RequestInterface $request,
      array $options = []
  ): ResponseInterface {
  ```

**メソッドサイズ** ⚠️ 軽微な問題（-3点）
- 平均: 18行（目標: 15行以下）
- `processRequest()` メソッドが28行で、やや大きめです。
  
  改善案：責任を2つのメソッドに分割
  ```php
  // 分割前: 28行
  private function processRequest() { /* ... */ }
  
  // 分割後:
  private function validateRequest(): void { /* ... */ }
  private function executeRequest(): ResponseInterface { /* ... */ }
  ```

**Visibility** ✓ 良好
- private/public の比率が適切（private 52%, public 32%）

---

### マクロレベル: 68/100 ⚠️ 要改善

**デザインパターン認識** ✓ 基本は理解
- インターフェースの実装を行えていますね。

**エラーハンドリング** ⚠️ 要改善（-10点）
- 現在のアプローチ: エラーを戻り値（false）で返している
- プロジェクト方針: エラーは例外で表現する（70%）
  
  修正例：
  ```php
  // 現在:
  public function request($method, $uri) {
      if ($error) {
          return false;
      }
      return $response;
  }
  
  // 修正:
  public function request(string $method, string $uri): ResponseInterface {
      if ($error) {
          throw new RequestException('...');
      }
      return $response;
  }
  ```

---

### 次のステップ

このプロジェクトは以下を意識してみてください：

1. **型ヒント** - 85%以上の採用を目指す
2. **メソッドサイズ** - 複雑な処理は責任で分割
3. **エラーハンドリング** - 例外ベースで統一

詳細なコーディングガイドは `STYLE_GUIDE.md` を参照してください。

---

### 参考資料

- [プロジェクトの例外戦略](../docs/error_handling.md)
- [Guzzleのソースコード例](../guzzle/src/)
- [レビュー記事](../docs/code_review_examples.md)
```

---

## まとめ

このフレームワークを活用することで：

1. **ユーザー**: プロジェクトのスタイルを段階的に習得
2. **チーム**: 一貫性のあるコードベースを維持
3. **評価**: 客観的で詳細なフィードバック提供

が可能になります。

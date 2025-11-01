# コーディングスタイル抽出ツール

## 📦 含まれるツール

### 1. extract_metrics.php

PHPプロジェクトから定量的メトリクスを自動抽出するツール

#### 使用方法

```bash
php extract_metrics.php /path/to/project [src_dir]
```

#### 例

```bash
# Monolog の分析
php extract_metrics.php /Users/mirai/projects/php-chatbot/monolog

# Laravel の分析（appディレクトリを指定）
php extract_metrics.php /path/to/laravel app

# Symfony の分析
php extract_metrics.php /path/to/symfony src
```

#### 出力

1. **コンソール出力**: 結果を見やすく表示
2. **metrics_output.yaml**: 構造化されたYAML形式

#### 抽出される情報

```yaml
project:
  name: プロジェクト名
  php_version: PHP最小バージョン
  type: プロジェクトタイプ

files:
  php_files: PHPファイル数
  test_files: テストファイル数

strict_types:
  total_files: 総ファイル数
  with_strict_types: strict_types使用ファイル数
  coverage_percent: 使用率
  conclusion: 結論

array_syntax:
  old_syntax_count: array()の使用回数
  new_syntax_count: []の使用回数
  short_ratio_percent: 短い構文の比率
  conclusion: 結論

type_system:
  typed_properties_count: 型付きプロパティ数
  fqn_function_files: 完全修飾関数使用ファイル数
  fqn_ratio_percent: 使用率
  conclusion: 結論

imports:
  total_use_statements: use文の総数
  files_with_use: use文を持つファイル数
  files_with_use_percent: 使用率

phpstan:
  found: 設定ファイルの有無
  file: ファイル名
  level: 解析レベル
  strict_rules: strictルールの有無
  deprecation_rules: deprecationルールの有無
  conclusion: 結論

php_cs_fixer:
  found: 設定ファイルの有無
  file: ファイル名
  has_psr2: PSR-2の使用
  has_psr12: PSR-12の使用
  has_strict_types: strict_typesルール
  has_array_syntax: 配列構文ルール
  rules_count: ルール数
  conclusion: 結論
```

---

## 🔧 実装予定のツール

### 2. analyze_phpcs_fixer.php（予定）

PHP-CS-Fixerの設定を詳細に解析し、各ルールの説明付きで出力

### 3. verify_psr_compliance.php（予定）

PSR-2/PSR-12への準拠度を検証

### 4. extract_ast.php（予定）

AST（抽象構文木）解析による構造的分析

### 5. generate_style_guide.php（予定）

抽出結果からスタイルガイドを自動生成

---

## 📊 Monologでの実行例

```bash
cd /Users/mirai/projects/php-chatbot/sonnet-4.5/revised/tools
php extract_metrics.php /Users/mirai/projects/php-chatbot/monolog
```

### 出力例

```
📊 メトリクス抽出開始: /Users/mirai/projects/php-chatbot/monolog

🔍 プロジェクト情報を取得中...
📁 ファイル数をカウント中...
🔒 strict_types 宣言をチェック中...
📦 配列構文を分析中...
🏷️  型システムを分析中...
📥 import文を分析中...
🔍 PHPStan 設定をチェック中...
🔧 PHP-CS-Fixer 設定をチェック中...

✅ 抽出完了！

============================================================
📊 抽出結果
============================================================

【プロジェクト情報】
  name: monolog/monolog
  php_version: >=8.1
  type: library

【ファイル数】
  php_files: 121
  test_files: 92

【strict_types】
  total_files: 121
  with_strict_types: 121
  coverage_percent: 100
  conclusion: 全ファイルで使用

【配列構文】
  old_syntax_count: 50
  new_syntax_count: 784
  short_ratio_percent: 94.02
  conclusion: 短い構文が主流

【型システム】
  typed_properties_count: 78
  fqn_function_files: 47
  fqn_ratio_percent: 38.84
  conclusion: 完全修飾関数を積極的に使用

【imports】
  total_use_statements: 345
  files_with_use: 115
  files_with_use_percent: 95.04

【PHPStan】
  found: ✅ あり
  file: phpstan.neon.dist
  level: 8
  strict_rules: ✅ あり
  deprecation_rules: ✅ あり
  conclusion: 最高レベルの静的解析

【PHP-CS-Fixer】
  found: ✅ あり
  file: .php-cs-fixer.php
  has_psr2: ✅ あり
  has_psr12: ❌ なし
  has_strict_types: ✅ あり
  has_array_syntax: ✅ あり
  rules_count: 30
  conclusion: カスタムルール設定あり

============================================================

💾 結果を保存しました: metrics_output.yaml

✅ 全ての処理が完了しました！
```

---

## 🚀 カスタマイズ

### 新しいメトリクスの追加

```php
private function analyzeYourMetric(): array
{
    echo "🔍 あなたのメトリクスを分析中...\n";
    
    $result = $this->exec("your command here");
    
    return [
        'your_data' => (int)trim($result),
        'conclusion' => '結論',
    ];
}

// extractAll() に追加
public function extractAll(): array
{
    // ...
    $this->metrics['your_metric'] = $this->analyzeYourMetric();
    // ...
}
```

---

## 📚 依存関係

### 必須
- PHP 8.1+（declare(strict_types=1) を使用）
- シェルコマンド: `find`, `grep`, `wc`

### オプション
- `yaml` PHP拡張（より良いYAML出力）

---

## 🎯 今後の改善

- [ ] AST解析の統合
- [ ] Git履歴分析
- [ ] 並列処理による高速化
- [ ] Webインターフェース
- [ ] 複数プロジェクトの比較機能

---

**作成者**: Claude Sonnet 4.5  
**最終更新**: 2025-11-01


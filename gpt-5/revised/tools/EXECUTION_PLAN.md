# 抽出ツール実行計画（AST + phpstan/PHPCS JSON 統合）

目的: 再現可能に style_rules.yaml を自動生成/更新し、rubric に接続する。

## 0. 前提
- PHP 8.1+
- 依存: nikic/php-parser, phpstan/phpstan, squizlabs/php_codesniffer（任意: phpmd, psalm, phpmetrics）
- 作業ディレクトリ: リポジトリルート

## 1. 収集と出力先
- 出力ディレクトリ: `.out/`
- 生成物: `discovery.json`, `phpstan.report.json`, `phpcs.report.json`, `ast_metrics.json`, `analysis_findings.json`, `style_rules.yaml`

## 2. コマンド例
```bash
# phpstan
vendor/bin/phpstan analyse --error-format=json > .out/phpstan.report.json || true

# phpcs (PSR-12 を仮ルールとして)
vendor/bin/phpcs -q --report=json --standard=PSR12 src > .out/phpcs.report.json || true
```

## 3. AST 抽出（擬似コード）
```php
$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
$metrics = [];
foreach (glob('src/**/*.php') as $file) {
    $ast = $parser->parse(file_get_contents($file));
    // 例: strict_types, typed properties, return types, visibility 分布
    $metrics[$file] = analyzeAst($ast);
}
file_put_contents('.out/ast_metrics.json', json_encode($metrics));
```

## 4. 統合・合成（擬似コード）
```php
$phpstan = json_decode(file_get_contents('.out/phpstan.report.json'), true);
$phpcs   = json_decode(file_get_contents('.out/phpcs.report.json'), true);
$ast     = json_decode(file_get_contents('.out/ast_metrics.json'), true);

$suggestions = suggestThresholds($ast, $phpstan, $phpcs);
$rules = synthesizeRules($suggestions, 'revised/style_rules.schema.yaml');
file_put_contents('revised/style_rules.yaml', yaml_emit($rules));
```

## 5. 証跡リンク
- 最頻出/代表違反を抽出し、`examples/good_bad_examples.md` のアンカーに紐付け
- 可能ならスニペットを `evidence.samples` に保存

## 6. rubric 接続
- rule_id → weight/penalty を抽出し、採点器に取り込む
- 致命ルール（required:true, severity:error）は減点上限を高める

## 7. チーム上書き
- `style_rules.override.yaml` をマージ（優先）
- CI では override 適用後の最終ルールで評価

## 8. 成功基準
- 同一リポジトリでの再実行で同一の style_rules.yaml が生成される（差分は override またはコード変更のときのみ）

# 最適なコーディングスタイル抽出パイプライン（改訂版）

本パイプラインは、再現性・機械可読性・教育適用性を柱に、プロジェクトのコーディングスタイルを抽出・定量化・ルール化します。

## フェーズ0: ディスカバリ（安全・前提確認）
- 入力: リポジトリパス
- 収集: composer.json / phpunit.xml(.dist) / phpstan.* / psalm.* / phpcs.* / .php-cs-fixer.* / rector.* / .editorconfig / README / CONTRIBUTING
- 出力: discovery.json（検出ファイル一覧とメタ）

## フェーズ1: 設定ハーベスト（事実ベース）
- 目的: 静的設定からプロジェクト意図を抽出
- コマンド例:
```bash
php -r 'echo json_encode(json_decode(file_get_contents("composer.json")), JSON_PRETTY_PRINT);'
phpstan analyse --error-format=json > .out/phpstan.report.json || true
phpcs -q --report=json --standard=PSR12 src > .out/phpcs.report.json || true
```
- 出力: config_harvest.json（PHPバージョン/PSR/静的解析レベル/除外/ベースライン 等）

## フェーズ2: AST 解析（構文・構造の定量化）
- 目的: 命名/型/可視性/例外/長さ/複雑度などを機械抽出
- 手段: nikic/php-parser で PHP→AST→統計
- 指標例:
  - strict_types 宣言率、プロパティ/引数/戻り値の型付与率
  - public/protected/private 比、final/abstract/trait/enum 使用率
  - 例外種別の分布、メソッド行数・循環的複雑度（外部メトリクス可）
- 出力: ast_metrics.json（ファイル/クラス/メソッド粒度の集計）

## フェーズ3: Lint/解析統合（現実の逸脱箇所）
- 目的: 実際の違反/警告を JSON で集約し、禁止/推奨候補に反映
- 入力: phpstan.report.json / phpcs.report.json（任意: psalm, phpmd）
- 出力: analysis_findings.json（重大度・件数・分布・代表例）

## フェーズ4: 経験則スコアリング（しきい値の自動提案）
- 目的: AST + Lint 指標から初期しきい値を提案
- 例: 「戻り値型付与率 < 0.95 → 重要ルール/減点大」「strict_types 率 < 1.0 → 必須ルール」
- 出力: scoring_suggestions.json

## フェーズ5: ルール合成（機械可読 YAML）
- 目的: ルールID/カテゴリ/必須/配点/減点/検出器/根拠を YAML に落とす
- スキーマ: style_rules.schema.yaml（本ディレクトリ）
- 出力: style_rules.yaml（初期版）

## フェーズ6: 証跡リンク（教育・レビュー用）
- 目的: 代表的違反/準拠例を良い/悪いコードで提示し、rule_id に紐付け
- 出力: examples/good_bad_examples.md（rule_id ごとの例）

## フェーズ7: チーム上書き（ローカル適用）
- 目的: チーム固有の override を別ファイルで差し替え
- 形式: style_rules.override.yaml（優先マージ）

## フェーズ8: 出力/連携
- 目的: 採点器・問題生成器・レビューBot への連携
- 出力:
  - rubric（rule_id→配点/減点）
  - evidence（rule_id→ファイル:行 の例）
  - summary（ダッシュボード用メトリクス）

---

## データモデル（抜粋）
- style_rules.schema.yaml に準拠
- 例（ルール条項・検出器・配点・根拠リンク）:
```yaml
- id: php.strict_types
  title: "declare(strict_types=1) を先頭に置く"
  category: syntax
  required: true
  weight: 10
  penalty:
    missing: 10
  detector:
    type: ast
    pattern: declare(strict_types=1)
    path_glob: "src/**/*.php"
  evidence:
    examples:
      good_ref: examples/good_bad_examples.md#php.strict_types-good
      bad_ref: examples/good_bad_examples.md#php.strict_types-bad
```

---

## 再現手順（最小）
1) 設定収集 → .out/ に JSON 出力
2) AST 解析 → ast_metrics.json 生成
3) 解析統合 → analysis_findings.json 生成
4) 合成 → style_rules.yaml を生成
5) 例追加 → examples に良い/悪い例

---

## 教育適用（要点）
- 各 rule_id に「なぜ重要か」「よくある誤り」「良い/悪い例」
- rubric と rule_id を1:1対応させ、自動フィードバックの定型文を生成
- レベル別（初・中・上・マスター）に、必須/推奨ルールセットを切替

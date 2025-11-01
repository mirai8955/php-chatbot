# gpt-5/revised - スタイル抽出 改訂版

本ディレクトリは、各モデルからの評価フィードバックを反映した「最適なコーディングスタイル抽出方法」の改訂版ドキュメントと成果物を格納します。

構成:
- style_extraction_method.md — 改訂版の抽出パイプライン（手順・コマンド・データモデル）
- style_rules.schema.yaml — 機械可読ルールのスキーマ
- style_rules.yaml — 代表ルールの初期セット（ルールID/配点/減点/根拠リンク）
- tools/EXECUTION_PLAN.md — AST + phpstan/PHPCS JSON 統合の実行計画
- examples/good_bad_examples.md — 主要ルールの良い/悪いコード例

目的:
- 再現性（手順化・コマンド化）
- 機械可読性（YAML スキーマ/ルール）
- 教育適用（良い/悪い例、採点rubricの基礎）


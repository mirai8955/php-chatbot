## Monolog コーディングスタイル抽出手法と所見

### 本実施の抽出手法（MVP）
- 設定ファイル読解: `composer.json`, `phpunit.xml.dist`, `phpstan.neon.dist`, `phpstan-baseline*.neon`, `phpstan-ignore-by-php-version.neon.php` から、PHP バージョン要件・テスト構成・静的解析レベル/ルール/除外を抽出。
- コードコーパス観測: `src/Monolog` 配下の代表ファイル（`Logger.php`, `Handler/StreamHandler.php` ほか）を走査し、
  - `declare(strict_types=1)` の徹底度（grep で 121/121）
  - 型宣言（引数/戻り/プロパティ）と `?T`/union の利用
  - DocBlock の役割（phpstan 用 generics 注釈）
  - 命名規則（StudlyCase/camelCase/UPPER_SNAKE_CASE）
  - 設計パターン（handlers の chain-of-responsibility, processors pipeline, value object `LogRecord`）
  - 例外ポリシー（`InvalidArgumentException`/`LogicException`/`UnexpectedValueException`）
  を定量的・定性的に記述。
- テスト慣習の確認: `phpunit.xml.dist` と `tests/bootstrap.php` から、ディレクトリ構成・ブートストラップ・旧 PHPUnit 互換 alias の有無を確認。
- 暗黙スタイルの推定: CS 設定ファイル（PHPCS/CS-Fixer）不在時でも、PSR-12 互換のコードフォーマットを観測ベースで推定。

### 抽出結果サマリ
- PHP 8.1+ 前提、`phpstan level: 8 + strict + deprecation` を採用。
- `strict_types=1` は src 全体で徹底。型付きプロパティ・戻り値必須の方針。
- Generics は PHPDoc (`@phpstan-var`, `@phpstan-return`) を用いて明示。
- 名前付け一貫（Class: StudlyCase, method/property: camelCase, const: UPPER_SNAKE_CASE）。
- 設計はハンドラとプロセッサのスタックで拡張可能かつ凝集度高く、`LogRecord` は値オブジェクトとして扱われる。
- IO 周りは chunked write・locking オプション・例外整備など、安全性と長期実行プロセスを意識。

詳細は `monolog_style_profile.yaml` を参照。

### より良い抽出方法（改良案）
1) AST ベース解析（php-parser）
   - 目的: 命名規則/可視性/型宣言/属性/継承関係/例外スロー箇所/循環参照などを機械抽出。
   - 成果物: `style_profile.json` に統計（例: "methods_with_return_types_ratio": 0.98）。

2) 静的解析ツールのメタ出力取り込み
   - `phpstan --error-format json` を併用し、実際の違反傾向をスナップショット化（型付け/デッドコード/未使用など）。
   - ベースライン差分で “許容されている例外的スタイル” を特定し、チーム流儀に昇華。

3) CS/Lint のドライラン集約
   - PHPCS/CS-Fixer（設定あれば）を `--report-json` で実行し、違反カテゴリ分布をデータ化。
   - 設定ファイルが無い場合は PSR-12 で一旦走らせ、実コードの観測差からルールカスタム候補を自動生成。

4) テスト設計メトリクス
   - PHPUnit の `--coverage-xml` や test discovery で、命名/データプロバイダ/境界ケースの網羅度を収集。
   - 例外テスト・失敗系テストの比率や、`@dataProvider` 使用率を指標化。

5) 差分学習（リポジトリ履歴のマイニング）
   - 直近 PR/コミットの lint/CI 失敗原因を集計 → 実態に即した “守るべきルールの重要度” を重み付け。

6) LLM による人手知見の構造化
   - CONTRIBUTING/README/Issue/PR コメントから暗黙知（レビュー用語、NGワード、命名癖）を抽出し、ルール化。

### 導入順（低コスト→高価値）
- Step 1: 本 MVP（設定読解 + コード観測 + 簡易 grep 指標）
- Step 2: php-parser AST で統計を JSON 化 → 可視化
- Step 3: phpstan/json 出力とテストカバレッジ指標の統合
- Step 4: CS ドライラン結果のルール化と自動同期（.editorconfig/PHPCS 設定自動生成）

### 本抽出での注意点
- リポジトリ直下に PHPCS/CS-Fixer の設定ファイルは見当たらず、PSR-12 互換を観測ベースで推定。
- `final class` は多用ではないため、"全面 final" 方針ではない（設計上の拡張性を保持）。

### 次アクション
- AST ベース抽出の PoC 実施（関数/クラス/メソッド統計を YAML/JSON 出力）。
- PSR-12 を初期ルールとして PHPCS をドライランし、観測差分から Monolog 流の追加ルール候補を列挙。

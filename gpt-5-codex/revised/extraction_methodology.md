# Monolog スタイル抽出・再構築手法（改訂版）

## 目的
- チームのコーチAIが参照できる **再現性の高いスタイル定義** を作る。
- 実運用で使われているルール（`.php-cs-fixer.php`, `phpstan.neon.dist` など）と、実際のソースコードを突き合わせて **事実ベースで検証** する。
- 抽出した知識を YAML / JSON へ構造化し、問題出題・採点・フィードバックに接続できる形に整える。

## インプットの棚卸し
| 種別 | 代表ファイル | 備考 |
| --- | --- | --- |
| フォーマッタ設定 | `monolog/.php-cs-fixer.php` | PSR-2 ベース＋プロジェクト固有ルールを明示。 |
| 静的解析 | `monolog/phpstan.neon.dist` | level 8、strict/deprecation ルールを含む。 |
| 型基準 | `monolog/src/**/*.php` | 121/121 ファイルで `declare(strict_types=1);` を確認。 |
| テスト規約 | `monolog/tests/**/*.php` | 94 ファイル、src と同様に `strict_types` を採用。 |
| ドキュメント | `monolog/doc/*.md` | 実装方針・拡張の仕方が記載されている。 |

> **定量確認ログ**
> - `find monolog/src -name "*.php" | wc -l` → 121
> - `grep -Rl "declare(strict_types=1);" monolog/src | wc -l` → 121
> - `find monolog/tests -name "*.php" | wc -l` → 94

## 手順概要（5 フェーズ）

### Phase 0. 初期設定
1. リポジトリクローン & 依存解決（必要なら Composer install）。
2. `php -v`, `phpstan --version`, `php-cs-fixer --version` でツールバージョンを記録。
3. 作業ログを `logs/` 配下に自動保存するシェルスクリプトを用意（コマンドと出力を履歴化）。

### Phase 1. 設定ファイルからのルール抽出
| 手順 | コマンド例 | 期待アウトプット |
| --- | --- | --- |
| 1-1 | `php -l monolog/.php-cs-fixer.php` | PHP-CS-Fixer 設定の構文検証。 |
| 1-2 | `php scripts/dump-cs-config.php` *(後述)* | ルールを JSON / YAML に変換。 |
| 1-3 | `cat monolog/phpstan.neon.dist` | 静的解析ルールの確認。 |
| 1-4 | `php scripts/dump-phpstan.php` | include 先（strict/deprecation ルール）も含めてマージ。 |

補助スクリプト例（擬コード）:
```php
$config = include '.php-cs-fixer.php';
file_put_contents('artifacts/php-cs-fixer.rules.yaml', yaml_emit($config->getRules()));
```

### Phase 2. ソースコードのリアル観測
1. **命名・宣言の統計**
   - `php scripts/ast-metrics.php --path=monolog/src --out=artifacts/ast_metrics.json`
   - AST からメソッド数、平均長、可視性、return 型比率を算出。

2. **型／宣言の遵守率**
   - `grep -R "declare(strict_types=1);" monolog/src | wc -l` → 121/121。
   - `php scripts/check-typed-properties.php` で、未型宣言プロパティが無いかを検査。

3. **例外と防御的コードの把握**
   - `grep -R "throw new" monolog/src | sort | uniq -c` → 例外クラス使用状況を把握。
   - `grep -R "try {" -n monolog/src` → `try/finally` の有無を確認。

4. **テスト構造の対応付け**
   - `php scripts/map-tests.php` で `src` ↔ `tests` のクラス名対応を抽出。
   - PHPUnit アトリビュート（`#[DataProvider]` 等）の使用率も集計。

### Phase 3. ルール × 実コードのクロスチェック
| 観点 | ルール出典 | 実コード検証方法 | 出力 |
| --- | --- | --- | --- |
| declare_strict_types | `.php-cs-fixer.php` | grep 結果を `rules/declare_strict_types.json` に保存 | 遵守：121/121 |
| array_syntax: short | `.php-cs-fixer.php` | `php scripts/check-array-syntax.php` で `array(...)` を検知 | 違反一覧（必要なら baseline） |
| native_function_invocation | `.php-cs-fixer.php` | `php scripts/report-native-fn.php` | 完全修飾率 100% を目指し差分検知 |
| phpstan level 8 | `phpstan.neon.dist` | `vendor/bin/phpstan analyse --error-format=json` | エラーゼロであることを証跡化 |

ここで得た違反・遵守状況を `artifacts/validation_report.json` にまとめる。

### Phase 4. 構造化成果物の生成
1. `style_rules_template.yaml` に沿って、実測値を埋める。
2. `metrics/dashboard.csv` を作成（例：`metric,value,source`）。
3. コーチAI用の問題テンプレートに変換しやすいよう、`rules` に `example_pass`, `example_fail` を添付。
4. ルールごとに採点ウェイトを定義し、`rubric.yaml` に出力。

### Phase 5. 継続評価と差分監視
- `git diff --stat main` で設定ファイルの変更を検知 → 自動再抽出。
- CI で `phpstan` / `php-cs-fixer --dry-run` を実行し、スタイル逸脱をブロック。
- 生成した YAML / JSON をバージョン管理し、変更時にはレビュー用サマリーを自動生成。

## 代表的な成果物フォーマット
`style_rules_template.yaml` の概要:
```yaml
project:
  name: monolog/monolog
  php_min_version: "8.1"
  source_counts:
    src_php: 121
    tests_php: 94
rules:
  - id: declare_strict_types
    source: .php-cs-fixer.php
    description: "全ファイルで strict_types を宣言する"
    verification:
      command: "grep -Rl 'declare(strict_types=1);' monolog/src"
      result:
        matches: 121
        total: 121
    severity: high
    scoring_weight: 10
    example_pass: "Monolog\\Logger"
    example_fail: null
```

## フィードバック対応状況
| フィードバック | 対応内容 |
| --- | --- |
| 一次情報の不足 | 設定ファイルの具体パスと抜粋、コマンドログを記載。 |
| 定量データ欠如 | ファイル数・遵守率などを取得し、テンプレートに組み込む。 |
| 構造化データ不足 | YAML テンプレートと JSON レポートの出力仕様を定義。 |
| 教育への接続 | 採点ウェイトや例示コードを成果物に含める指定を追加。 |

## 今後の拡張
- `scripts/` ディレクトリに PHP / Python で実行可能な抽出ツールを配置。
- CI 用に `make style-audit` コマンドを用意し、ドキュメント生成を自動化。
- 他プロジェクトへの適用時は、Phase 1〜4 を横展開し、差分だけをレポートする仕組みを導入。

---
この手法を基に、YAML 化されたスタイルルール・メトリクス・評価基準を整備し、AI コーチの問題生成・採点・フィードバックに接続していきます。


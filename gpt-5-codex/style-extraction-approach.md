## 目的とスコープ
- チーム固有のPHPコーディングスタイルを自動抽出し、生成AIチャットボットが問題出題と解答評価を通じて学習を支援するためのアプローチを整理する。
- 対象は任意のOSSリポジトリ（後述の候補）および社内リポジトリを想定。スタイル抽出・評価・問題生成をパイプライン化し、段階別スキル指標（レベル設計）まで含める。

## 全体アーキテクチャ
```
リポジトリ取得 → スタイルシグネチャ抽出 → 評価器/問題ジェネレータ構築 → ユーザー回答評価 → フィードバック・レベル判定
```

1. **リポジトリ解析**：コードと周辺メタ情報をクローンし、構造化ストア（例：PostgreSQL + pgvector, DuckDB, S3）へ投入。
2. **スタイルシグネチャ抽出**：フォーマット・命名・アーキテクチャ・依存関係を多層的に解析し「スタイルシグネチャ」を定義。
3. **評価器構築**：規約チェック、ヒューリスティック指標、LLM評価、統計的スコアリングを組み合わせる。
4. **問題生成**：スタイルシグネチャから課題テンプレートを生成し、難易度／カテゴリ／レベルを体系化。
5. **フィードバックループ**：ユーザー回答をスコアリングし、差分を解説、次課題への適応学習を行う。

## スタイルシグネチャ抽出パイプライン

### Step 0: 初期メタ情報収集
- `.editorconfig`, `phpcs.xml`, `phpcs.xml.dist`, `phpstan.neon`, `phpstan-baseline`, `php-cs-fixer.php`, `psalm.xml`, `pint.json` 等の設定ファイルを走査。
- `composer.json` から `autoload`, `scripts`, `require-dev` を抽出し、命名空間やテストフレームワーク、補助ツールを特定。
- CI定義（`.github/workflows`, `gitlab-ci.yml`, `azure-pipelines.yml` 等）を解析し、使用ツールと品質ゲートを推定。

### Step 1: フォーマット／構文レベル
- `phpcs` や `php-cs-fixer` の `dry-run` を実行して差分を解析し、採用規約（PSR-12, Symfony CS等）を推定。
- `nikic/php-parser` でASTを構築し、以下を統計化：
  - ブレース位置、インデント幅、スペース vs タブ。
  - 命名規則（PascalCase, camelCase, snake_case）の比率。
  - DocBlock必須度、`@var/@return` 記述率。
- `phpmetrics` でCyclomatic Complexity, Maintainability Index 等を取得し、品質ベンチマークを設定。

### Step 2: 命名・構造パターン
- ディレクトリ構造（`app/Http/Controllers`, `src/Domain`, `modules/*` 等）から役割分類。
- クラス役割ごとにフィンガープリントを抽出：
  - `Controller` のメソッド名パターン（`index`, `store`, `__invoke` 等）
  - `Entity/Model` のプロパティ宣言スタイル（型宣言有無、`readonly` 利用率）
  - `Service/Action` の依存注入方法（コンストラクタ vs メソッドインジェクション）
- コメント・DocBlockのテンプレート（`@phpstan-return`, `@template` 等）を頻度分析し、静的解析との連携度を推定。

### Step 3: コンポーネント特性抽出
- 代表的コンポーネント単位でコード断片をクラスタリング：
  - ASTを`vector`化し、`k-means`または`HDBSCAN`でグルーピング。
  - 各クラスタの代表スニペットを生成し、スタイル説明（LLM要約）を付与。
- パターン種別例：
  - **Controller**：`Request`→バリデーション→サービス呼び出し→レスポンス生成
  - **Command/Job**：`handle()` 内の例外処理・リトライ戦略
  - **Value Object**：不変プロパティ/ファクトリメソッド構成
- GFPGANなどのDiff生成ではなく、`git log --merges` からレビュー済みコミットを抽出し、良質スタイルの基準サンプルとする。

### Step 4: コーディングスタイルスキーマ化
- 上記特徴量を`YAML/JSON`で定義した「スタイルプロファイル」に落とし込む。
- サンプル構造：
```yaml
formatting:
  indent: 4_spaces
  brace: next_line
  imports: alphabetic_grouped
docblock:
  required: true
  tags:
    controller_methods: ["@param Request", "@return Response"]
component_patterns:
  controller:
    validation: form_request_preferred
    response: json_helper
  service:
    dependency_injection: constructor
```
- このプロファイルを問題生成・評価で再利用する。

## 評価器の設計

### ハードルール
- `phpcs`/`php-cs-fixer` のカスタムルールセットを生成し、自動採点（スタイル遵守率%）。
- 独自ルール（例：`Controller` の戻り値型宣言必須、`Repository` の命名規約）をASTで検証。

### ソフトメトリクス
- `phpmetrics`, `phan`, `phpstan` の結果を正規化し、保守性・安定性スコアへ。
- コメント密度、`enum`/`readonly` の活用度、`final` 指定率など、プロジェクトで推奨されるパターンの類似度を算出。

### LLM評価
- LLMへ「プロジェクトスタイルプロファイル」とユーザー回答を入力し、
  - **Pattern Match**：典型パターンとの一致度
  - **リファクタ提案数**：改善箇所数を評価
- ルーブリック例：`{formatting: 30, naming: 20, architecture: 25, robustness: 15, idioms: 10}`。

### スコア統合
- `weighted_sum = Σ (metric_i × weight_i)`
- 履歴ベースのElo/Glickoを導入し、ユーザーのスキル推移を可視化。
- 合格ラインはプロジェクトベンチマーク（代表サンプルの平均±σ）から動的決定。

## 問題生成フレーム

### タスクカテゴリ
- **フォーマット**：DocBlock補完、命名修正。
- **コンポーネント**：`Controller`, `Service`, `Repository`, `Value Object`, `Blade/Twig component` 等、役割別の穴埋め問題。
- **リファクタリング**：既存コードをスタイル準拠へ修正（Before/After評価）。
- **レビュー**：LLMが生成したレビューコメントの妥当性検証。

### 問題テンプレート作成手順
1. クラスタ代表スニペットを問題のベースにする。
2. 典型的規約違反を人工的に挿入し、修正問題を生成。
3. 難易度パラメータ：
   - `基礎`：フォーマットのみ、DocBlock補完。
   - `応用`：依存注入・エラーハンドリング・テスト追加。
   - `高度`：ドメイン固有パターン（例：`Action` クラス構造、`Policy` 作成）
4. 自動生成後、LLMによるレビューとメタデータ付与（解答例、採点基準）。

## レベル指標（PHPマスターへの道）

| レベル | 名称 | 主眼 | クリア条件例 |
|--------|------|------|---------------|
| L0 | Orientation | 規約理解 | スタイルプロファイルに基づくクイズ正答率80% |
| L1 | Foundation | 文法 + 基本コンポーネント | Controller/Model問題でスタイル遵守率70% |
| L2 | Project-Ready | サービス/テスト連携 | 実案件タスク（CRUD, FormRequest等）で80点以上 |
| L3 | Advanced | 最適化・保守性 | リファクタ問題、複雑度低減、静的解析ノーエラー |
| L4 | Master | リードエンジニア視点 | レビューフィードバック品質、LLM採点90点以上 |

## OSSプロジェクト候補と視点

| リポジトリ | 特徴 | 評価観点 |
|-------------|------|-----------|
| `laravel/framework` | PSR-12準拠かつ豊富なコンポーネント（Controller, Artisan, Queue）| Laravel流の依存注入・テスト文化を抽出可能 |
| `symfony/symfony` | モジュール志向・厳密なDI設計 | コンポーネント毎のベストプラクティス比較、命名一貫性 |
| `shopware/platform` | ECドメインに特化、プラグイン構造が明確 | ドメイン固有コントリビューション規約の抽出 |
| `matomo-org/matomo` | 大規模アナリティクス、レガシー + モダン混在 | レガシー移行支援タスク生成に適合 |
| `woocommerce/woocommerce` | WordPress系、フック文化 | `hook/action/filter` の典型パターン学習 |
| `phpstan/phpstan` | 型安全志向が強い | 静的解析を前提にしたDocBlock/テンプレート活用 |

### 候補分類
- **ベストプラクティス型**：`laravel/framework`, `symfony/symfony`
- **ドメイン特化型**：`shopware/platform`, `matomo-org/matomo`
- **エコシステム拡張型**：`woocommerce/woocommerce`
- **静的解析先進型**：`phpstan/phpstan`

## 実験計画（PoC）
1. 上記OSSから2～3本を選定し、スタイルプロファイル生成の自動化スクリプトを作成。
2. プロファイルを元に問題テンプレートを生成。手動レビューで品質確認。
3. 社内リポジトリに適用し、差分（規約違反検出率・フィードバック精度）を測定。
4. ユーザーテスト：各レベルから1問ずつ出題し、回答時間・再学習効果（追跡テスト）を計測。
5. メトリクス改善：誤検知箇所を分析し、ルール重みやLLMプロンプトを調整。

## 今後の発展
- CI/CDへの組み込み：PR時にチャットボットがリハーサル問題を自動提示。
- ユーザー個別ダッシュボード：レベル推移、弱点カテゴリ、代表的フィードバック一覧。
- マルチプロジェクト対応：プロジェクト間でスタイルプロファイルの共通部分を抽象化し、移行コストを削減。
- LLMファインチューニング：プロジェクト固有の良質レビューコメントを学習させ、より精度の高いフィードバックを生成。

---
上記アプローチを基に、AIチャットボットはプロジェクト固有のコーディングスタイルを短時間で把握・定着させる「コーチ」として機能させることができる。


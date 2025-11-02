## 目的

任意のPHPプロジェクトから「その組織・プロダクト特有のコーディングスタイル／設計慣習」を自動抽出し、AIコーチ型チャットボットが
1) 問題を自動生成、2) 解答を自動採点、3) 改善フィードバックを自動提示、4) レベル指標に基づく進捗管理、を行えるようにするための実装方針を定義する。

---

## 全体アーキテクチャ（モジュール）

- Style Extractor: スタイル・設計慣習の抽出（静的解析＋設定解析＋統計）
- Rule Compiler: 抽出結果を機械可読なルール集合（YAML/JSON）へ正規化
- Item Generator: ルールとプロジェクト文脈に基づく段階的な出題生成
- Grader: 静的解析・規約検査・テスト実行を統合した採点
- Feedback Engine: 違反ルールに対する具体的・行動可能な助言を生成
- Mastery Model: レベル指標（基礎→中級→上級→マスター）と到達度推定

---

## 抽出の基本戦略（優先度順）

1. 設定/メタ情報の抽出（確定情報）
   - `composer.json`（依存/オートロード/スクリプト/フレームワーク判定）
   - 規約系: `phpcs.xml(.dist)`, `.php-cs-fixer.php`, `.editorconfig`
   - 静的解析: `phpstan.neon`, `psalm.xml`, `phpmd.xml`
   - CI: `.github/workflows/*.yml`, `gitlab-ci.yml`（必須チェック・閾値）
   - テスト: `phpunit.xml`, 覆率閾値, フォルダ構成

2. コード本体のAST/トークン解析（実態の慣習）
   - 代表ライブラリ: nikic/php-parser（AST）, PHP_CodeSniffer（トークン）, PHPStan/Psalm（型）
   - 信号（Signals）例:
     - タイプ宣言（`strict_types`, 戻り値/引数型, `readonly`, `final`, `enum`, 属性）
     - 命名規則（クラス/メソッド/プロパティ/定数/テスト名）
     - 可視性・抽象化レベル（`private`/`protected`/`public`の傾向）
     - 例外・エラーハンドリング（独自例外、`throw`/`Result`型、`try`の境界）
     - 依存注入・サービスロケーション（コンストラクタ注入、サービスコンテナ）
     - 不変値/値オブジェクト/DTOの有無と置き場所
     - ファイル/クラス粒度（クラス当たり行数, 圧縮/分割の傾向）
     - コレクション・ユーティリティの使用（`array_map` vs ループ等）
     - フレームワーク特有構造（後述）

3. フレームワーク/プロダクト特有のコンポーネント規約（文脈）
   - Laravel: `app/Http/Controllers`, `FormRequest`, `ServiceProvider`, `Policy`, `Middleware`, `Eloquent`規約
   - Symfony: `Controller`継承, `services.yaml`定義, `EventSubscriber`, `Console\Command`
   - WordPress: Hook/Filter, テンプレート階層, プレフィックス命名
   - Drupal: サービス宣言（YAML）, プラグイン/エンティティ/フォームAPI
   - CMS/EC（Magento/Shopware/PrestaShop）: DI/モジュール構造/Observer/Plugin/Area

4. 統計・傾向スコアリング（決め打ちではなく“比率”で学習）
   - 例: 「strict_types宣言率 87%」「プロパティ昇格利用率 62%」「`final`クラス率 48%」
   - 閾値はCIや`phpcs.xml`のseverity/`phpstan` levelと合わせて初期重みを決定

---

## 出力（機械可読なルール）

抽出結果はルールID/説明/検出手段/重み/例/補正提案で管理。

```yaml
version: 1
project:
  name: auto-detected
  framework: laravel # symfony|wordpress|drupal|library|mixed
weights:
  style: 0.35
  readability: 0.25
  maintainability: 0.25
  efficiency: 0.15
rules:
  - id: php.strict_types.required
    category: style
    description: 全PHPファイルの先頭で declare(strict_types=1) を宣言する
    detection: ast
    threshold: 0.9  # コードベースでの採用率が90%以上なら必須扱い
    autofix: php-cs-fixer:declare_strict_types
    feedback: 'ファイル先頭へ declare(strict_types=1); を追加してください'
  - id: naming.class.PascalCase
    category: style
    detection: tokens
    threshold: 1.0
  - id: laravel.controller.structure
    category: framework
    detection: convention
    details:
      folder: app/Http/Controllers
      suffix: Controller
      request_validation: FormRequest preferred
```

---

## 採点（Grader）

採点は以下を合成した複合スコア。

- 規約適合: PHP_CodeSniffer（プロジェクト同梱の`phpcs.xml`優先）
- 型/静的解析: PHPStan/Psalm（プロジェクト設定準拠）
- 可読性/複雑度: ASTからのメトリクス（関数長、ネスト、条件分岐、早期return比率 など）
- 保守性: 依存方向（循環参照）、結合度（use/import密度）、凝集度（ファイル責務）
- 効率: 明白なアンチパターン（N+1、重複クエリ、不要ループ、不要I/O）検出の静的ルール
- テスト: 単体テスト合否・境界網羅、CIと同等の閾値

採点式（例）:

`total = 100 * ( 0.35*style + 0.25*readability + 0.25*maintainability + 0.15*efficiency )`

各部分スコアはルールID単位で集計し、重み付き平均を採用。CIで“必須”とされる項目は減点係数を強くする。

---

## 出題（Item Generator）の考え方

コンポーネント種別ごとに「そのプロジェクトらしい書き方」を試す問題を自動生成。

- Controller: 命名/ディレクトリ/依存注入/入力検証/レスポンス整形/例外変換
- Service/UseCase: 単一責務/トランザクション境界/ロギング/エラー戦略
- Repository/Model/Entity: 型/不変ルール/コレクション操作/クエリ方針
- Middleware/Event/Command: ログ/メトリクス/再試行/例外流儀
- Test: 命名/階層/Fixture/Mock/境界値

生成手順（簡易）:

1) 抽出ルールから「必須/推奨パターン」を選定
2) 同等の既存クラスをテンプレート（構文・依存の写像）として抽出
3) 仕様を最小化した課題文＋入出力（ダミー要件）を生成
4) 採点用の期待基準（規約＋ASTパターン＋ユニットテスト）を同時生成

---

## フレームワーク文脈の自動判定

- composer依存とディレクトリで判定（例: `laravel/framework`, `symfony/*`, `magento/*`）
- 文脈ごとの既定ルールバンドルを適用し、コード実態の統計で微調整
- 文脈特有の"禁じ手"（例: Laravelでの`DB::raw`乱用）を専用ルールに

---

## 実装に使う主要ツール（推奨）

- 抽出: nikic/php-parser, PHP_CodeSniffer, PHPStan/Psalm, PHPMD, Rector(dry-run)
- 整形: PHP-CS-Fixer（dry-run差分で傾向推定）
- テスト: PHPUnit, Pest
- CI連携: GitHub Actions読み取りで必須タスク/閾値を推定

---

## PoC（最小実装）手順

1) ルートで設定ファイル探索（phpcs/phpstan/psalm/editorconfig/phpunit/CI）
2) composerからフレームワーク推定
3) 100〜300ファイルをサンプリングしてAST/トークン統計を算出
4) 既定ルールバンドル＋統計→プロジェクト固有ルールをコンパイル
5) 1問出題（例えば Controller 作成）→ 回答を規約/AST/テストで採点
6) ルールID単位のフィードバックを提示

---

## レベル分け（Mastery Model）

- Beginner: PHP基礎（型/配列/制御/関数）+ PSR-12基本 + 単純クラス/テスト
- Junior: OOP/例外/DI/名前空間/Composer + 代表FWのController/Model/Test基本
- Mid: 設計（SRP, DIP, 集約）/静的解析レベル↑/CI整備/DBトランザクション
- Senior: アーキ層分割/トレーサビリティ/Observability/セキュリティ/性能
- Master: プロジェクト規約の策定/運用/レビュー指針/複雑変更の安全実装

各レベルごとに必須ルール集合（例: `strict_types`, `final class`の戦略, 例外方針）を紐づけ、到達度はルール達成率と問題合格率で推定。

---

## 典型的な評価フィードバック例

- ルール違反: `php.strict_types.required`
  - 影響: style -2.0pt（必須）
  - 修正: ファイル先頭へ `declare(strict_types=1);`
  - 参考: プロジェクト規約の抜粋/関連PRリンク

- ルール違反: `laravel.controller.structure`
  - 影響: maintainability -1.5pt
  - 修正: `FormRequest`導入、レスポンスのリソース化

---

## 注意点

- “一貫している事実”を重視（設定＋統計の両輪）。例外的ファイルに引っ張られないよう閾値で判断。
- ルールは常に可視化（プロジェクトへPR可能な`rules.yaml`）し、人間レビューで微調整可能にする。
- 採点は罰則だけでなく“できている点”の加点も重視（学習動機）。



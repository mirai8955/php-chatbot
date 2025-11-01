# Monolog コーディングスタイル抽出メモ（第1版）

## 背景と目的
- コーチAIの教材候補として `monolog/monolog` のコーディングスタイルを把握し、問題出題・採点指標の素材とする。
- チーム固有スタイル抽出の手始めとして、既存 OSS を対象に調査プロセスを整備する。

## 今回採用した抽出手法
1. **メタ情報調査**：`composer.json`・`phpstan` 設定ファイルを読み、サポート PHP バージョンや QA ツールを確認。
2. **代表ファイルの静的レビュー**：`src/Monolog/Logger.php` など主要クラスを読み、型定義・DocBlock・制御フロー・命名規則を記録。
3. **テスト・補助コードの構造確認**：`tests/` 階層の構成からテストパターンと依存ツール（PHPUnit）を把握。

## 観測したスタイル要素

### 言語レベルと基本宣言
- PHP 要件は `>=8.1`。全ファイルで `declare(strict_types=1);` を採用。
- `final` や `readonly`（今回は未確認）などモダン構文を積極的に活用。

```1:120:/Users/mirai/projects/php-chatbot/monolog/src/Monolog/Logger.php
<?php declare(strict_types=1);
/**
 * Monolog log channel
 *
 * It contains a stack of Handlers and a stack of Processors,
 * and uses them to store records that are added to it.
 *
 * @final
 */
class Logger implements LoggerInterface, ResettableInterface
{
    public const DEBUG = 100;
    // ... 途中略 ...
```

### 型・DocBlock の指針
- プロパティ／メソッドシグネチャでの厳格な型宣言、Union 型や `callable` 型注釈を多用。
- DocBlock では `@phpstan-var list<HandlerInterface>` のようにジェネリクス記法で補強。
- `@return $this`・`@phpstan-param` 等を明示してチェーン可能インターフェースを説明。

### コード構造と命名
- PSR-4 に準拠した名前空間構成。`Monolog\Handler\*` など役割ごとにフォルダ分割。
- メソッド名は命令形・説明的（`pushHandler`, `useLoggingLoopDetection`）。
- `private const`＋ enum 的クラス（`Level::Debug` 等）で定数管理。

### 例外・エラー処理
- `try/catch` 節で `Throwable` を補足し、例外発生時は専用メソッド `handleException` 経由で処理。
- ループ検出など防御的プログラミングを重視。

### 静的解析・QA ツール
- `phpstan` レベル 8、`strict-rules` と `deprecation-rules` を併用。`treatPhpDocTypesAsCertain: false` で過信を避け、`reportUnmatchedIgnoredErrors: true` で抑止リストを厳格運用。
- Composer スクリプトで `vendor/bin/phpunit` と `vendor/bin/phpstan analyse` を定義。

```1:24:/Users/mirai/projects/php-chatbot/monolog/phpstan.neon.dist
parameters:
    level: 8
    treatPhpDocTypesAsCertain: false
    reportUnmatchedIgnoredErrors: true
    paths:
        - src/
    include:
        - vendor/phpstan/phpstan-strict-rules/rules.neon
        - vendor/phpstan/phpstan-deprecation-rules/rules.neon
```

### テスト戦略
- `tests/Monolog/` 以下にハンドラ単位の細分化テストが多数。`@dataProvider` を活用したケース分解が多い。
- Bootstrap は `tests/bootstrap.php` に集約し、PSR-4 autoload-dev と連動。

### コメント・ドキュメント
- ファイル冒頭に著作権コメント・概要を配置。
- メソッド単位 DocBlock で用途・戻り値・例外を記述、長文では段落構成を保つ。

## 調査中に浮かんだ改善案・追加抽出アイデア
- **AST 解析による統計化**：`nikic/php-parser` などを用い、メソッド長・引数数・例外の種類をメトリクス化し、スタイル指針を定量化。
- **コーディング規約の外部化探索**：現状 `.php-cs-fixer` や `phpcs.xml` は未発見。コミットログや Issue から暗黙ルールを掘ると補完できる可能性。
- **DocBlock パターン収集**：`@phpstan-` 系アノテーションの種類や使用頻度を抽出し、弊チーム用のジェネリクス表現ルールを整備。
- **自動課題生成プロトタイプ**：代表的なハンドラのテストと本体をセットで取り出し、テストを壊して修正させる演習テンプレート化を検討。

## 次のステップ候補
- 追加で代表的なハンドラ／フォーマッタを読み、スタイルのバリエーションを比較。
- `git blame` でスタイル変更の経緯を確認し、安定ルールと移行中ルールを切り分ける。
- 抽出手法をスクリプト化（設定ファイル探索＋AST メトリクス）し、他プロジェクトへ横展開できるようテンプレート化。



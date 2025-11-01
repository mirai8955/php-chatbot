# レベル別問題サンプル集

**作成日**: 2025-11-01  
**目的**: 各レベルで出題される問題の具体例を示す

---

## 📚 レベル構成の概要

| レベル | 名称 | 対象スキル | 目標期間 |
|-------|------|----------|---------|
| 1 | Beginner | 基本構文 | 1-2週間 |
| 2 | Elementary | 配列・文字列 | 2-3週間 |
| 3 | Pre-intermediate | 関数・エラー処理 | 3-4週間 |
| 4 | Intermediate | OOP基礎 | 1-2ヶ月 |
| 5 | Upper-intermediate | デザインパターン | 2-3ヶ月 |
| 6 | Advanced-intermediate | PSR・テスト | 3-4ヶ月 |
| 7 | Pre-advanced | アーキテクチャ | 4-6ヶ月 |
| 8 | Advanced | フレームワーク | 6-9ヶ月 |
| 9 | Expert | 上級設計 | 9-12ヶ月 |
| 10 | Master | チーム固有 | 12ヶ月+ |

---

## Level 1: Beginner（入門）

### 問題 1.1: strict_types宣言

**カテゴリ**: 構文  
**難易度**: ★☆☆☆☆  
**学習目標**: strict_types宣言の重要性を理解する

#### 問題文
以下のコードに不足している重要な宣言を追加してください。

```php
<?php

namespace App;

class Calculator
{
    public function add($a, $b)
    {
        return $a + $b;
    }
}
```

#### 期待される解答
```php
<?php declare(strict_types=1);

namespace App;

class Calculator
{
    public function add($a, $b)
    {
        return $a + $b;
    }
}
```

#### 評価基準（100点満点）
- `declare(strict_types=1)` の追加: 50点
- 正しい位置（<?php の直後）: 30点
- セミコロンの配置: 20点

#### フィードバック例
```
✅ 良い点:
- strict_types宣言が追加されました！

📝 説明:
declare(strict_types=1) は、型の厳密性を保証します。
これにより、意図しない型変換によるバグを防ぐことができます。

例えば:
function add(int $a, int $b): int { return $a + $b; }

// strict_types=1 なし
add("5", "10"); // OK（自動的に数値に変換）

// strict_types=1 あり
add("5", "10"); // TypeError!

💡 次のステップ:
型ヒントについて学びましょう（次の問題へ）
```

---

### 問題 1.2: 配列構文の修正

**カテゴリ**: 構文  
**難易度**: ★☆☆☆☆  
**学習目標**: 現代的な配列構文を使用する

#### 問題文
以下のコードを現代的なPHP スタイルに修正してください。

```php
<?php declare(strict_types=1);

$fruits = array('apple', 'banana', 'orange');
$prices = array(
    'apple' => 100,
    'banana' => 80,
    'orange' => 120
);
```

#### 期待される解答
```php
<?php declare(strict_types=1);

$fruits = ['apple', 'banana', 'orange'];
$prices = [
    'apple' => 100,
    'banana' => 80,
    'orange' => 120,  // 末尾カンマ追加
];
```

#### 評価基準
- `array()` を `[]` に変更: 40点
- 複数行配列の末尾カンマ: 30点
- インデント正しい: 15点
- strict_types維持: 15点

---

## Level 2: Elementary（基礎）

### 問題 2.1: 型ヒントの追加

**カテゴリ**: 型システム  
**難易度**: ★★☆☆☆  
**学習目標**: 基本的な型ヒントを使用する

#### 問題文
以下の関数に適切な型ヒントを追加してください。

```php
<?php declare(strict_types=1);

function calculateTotal($prices, $taxRate)
{
    $subtotal = 0;
    foreach ($prices as $price) {
        $subtotal += $price;
    }
    return $subtotal * (1 + $taxRate);
}
```

#### 期待される解答
```php
<?php declare(strict_types=1);

function calculateTotal(array $prices, float $taxRate): float
{
    $subtotal = 0;
    foreach ($prices as $price) {
        $subtotal += $price;
    }
    return $subtotal * (1 + $taxRate);
}
```

#### 評価基準
- 引数の型ヒント（各20点）: 40点
- 戻り値の型ヒント: 40点
- 適切な型の選択: 20点

#### 発展課題
```php
// さらに厳密にするには？
function calculateTotal(array $prices, float $taxRate): float
{
    $subtotal = 0.0;  // 明示的にfloat
    foreach ($prices as $price) {
        if (!\is_numeric($price)) {
            throw new \InvalidArgumentException('Price must be numeric');
        }
        $subtotal += (float) $price;
    }
    
    if ($taxRate < 0 || $taxRate > 1) {
        throw new \InvalidArgumentException('Tax rate must be between 0 and 1');
    }
    
    return $subtotal * (1 + $taxRate);
}
```

---

## Level 3: Pre-intermediate（初級）

### 問題 3.1: エラーハンドリングの実装

**カテゴリ**: エラーハンドリング  
**難易度**: ★★★☆☆  
**学習目標**: 適切な例外処理を実装する

#### 問題文
以下のファイル読み込み関数に、適切なエラーハンドリングを追加してください。

```php
<?php declare(strict_types=1);

function readConfigFile(string $path): array
{
    $content = file_get_contents($path);
    return json_decode($content, true);
}
```

#### 期待される解答
```php
<?php declare(strict_types=1);

function readConfigFile(string $path): array
{
    if (!\file_exists($path)) {
        throw new \InvalidArgumentException(
            "Config file not found: {$path}"
        );
    }
    
    if (!\is_readable($path)) {
        throw new \RuntimeException(
            "Config file is not readable: {$path}"
        );
    }
    
    $content = \file_get_contents($path);
    if ($content === false) {
        throw new \RuntimeException(
            "Failed to read config file: {$path}"
        );
    }
    
    $data = \json_decode($content, true);
    if ($data === null && \json_last_error() !== JSON_ERROR_NONE) {
        throw new \RuntimeException(
            "Invalid JSON in config file: " . \json_last_error_msg()
        );
    }
    
    return $data;
}
```

#### 評価基準（100点満点）

**コーディング規約（25点）**
- strict_types宣言: 5点
- 型ヒント完備: 10点
- ネイティブ関数の完全修飾: 5点
- インデント・フォーマット: 5点

**可読性（25点）**
- 明確なエラーメッセージ: 10点
- 適切な変数名: 5点
- 段階的なチェック: 10点

**保守性（25点）**
- 例外の使い分け: 15点
- 拡張性: 10点

**効率性（25点）**
- 早期リターン/早期エラー: 10点
- 無駄な処理なし: 10点
- リソース管理: 5点

---

## Level 4: Intermediate（中級前期）

### 問題 4.1: クラス設計の改善

**カテゴリ**: OOP  
**難易度**: ★★★☆☆  
**学習目標**: クラスベースの設計を理解する

#### 問題文
以下の手続き型コードをオブジェクト指向に書き直してください。

```php
<?php declare(strict_types=1);

function sendEmail($to, $subject, $body) {
    // メール送信処理
    return mail($to, $subject, $body);
}

function logEmail($to, $subject) {
    $log = date('Y-m-d H:i:s') . " - Sent to: {$to}, Subject: {$subject}\n";
    file_put_contents('email.log', $log, FILE_APPEND);
}

function sendAndLog($to, $subject, $body) {
    $result = sendEmail($to, $subject, $body);
    if ($result) {
        logEmail($to, $subject);
    }
    return $result;
}
```

#### 期待される解答
```php
<?php declare(strict_types=1);

namespace App\Mail;

class EmailSender
{
    private string $logFile;
    
    public function __construct(string $logFile = 'email.log')
    {
        $this->logFile = $logFile;
    }
    
    public function send(string $to, string $subject, string $body): bool
    {
        $result = $this->sendEmail($to, $subject, $body);
        
        if ($result) {
            $this->logEmail($to, $subject);
        }
        
        return $result;
    }
    
    private function sendEmail(string $to, string $subject, string $body): bool
    {
        return \mail($to, $subject, $body);
    }
    
    private function logEmail(string $to, string $subject): void
    {
        $timestamp = \date('Y-m-d H:i:s');
        $log = "{$timestamp} - Sent to: {$to}, Subject: {$subject}\n";
        \file_put_contents($this->logFile, $log, FILE_APPEND);
    }
}
```

#### 評価基準

**コーディング規約（25点）**
- 名前空間の使用: 5点
- 型ヒント完備: 10点
- visibilityキーワード: 5点
- フォーマット: 5点

**可読性（25点）**
- クラス名・メソッド名: 10点
- 責任の明確化: 10点
- PHPDoc（あれば）: 5点

**保守性（25点）**
- カプセル化: 10点
- 依存性注入（logFile）: 10点
- 拡張性: 5点

**効率性（25点）**
- 適切な処理フロー: 15点
- 無駄なオーバーヘッドなし: 10点

#### 発展課題（Level 5への準備）
```php
// インターフェースを使った設計
interface EmailSenderInterface
{
    public function send(string $to, string $subject, string $body): bool;
}

interface LoggerInterface
{
    public function log(string $message): void;
}

class EmailSender implements EmailSenderInterface
{
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    public function send(string $to, string $subject, string $body): bool
    {
        $result = \mail($to, $subject, $body);
        
        if ($result) {
            $this->logger->log("Email sent to {$to}: {$subject}");
        }
        
        return $result;
    }
}
```

---

## Level 5: Upper-intermediate（中級）

### 問題 5.1: Strategy パターンの実装

**カテゴリ**: デザインパターン  
**難易度**: ★★★★☆  
**学習目標**: Strategy パターンを理解し実装する

#### 問題文
支払い処理システムを実装してください。クレジットカード、銀行振込、PayPalの3種類の決済方法に対応し、
新しい決済方法を簡単に追加できる設計にしてください。

#### 期待される解答

```php
<?php declare(strict_types=1);

namespace App\Payment;

/**
 * 決済方法のインターフェース
 */
interface PaymentStrategyInterface
{
    /**
     * 決済を実行
     *
     * @param float $amount 決済金額
     * @return bool 決済成功時true
     * @throws PaymentException 決済失敗時
     */
    public function pay(float $amount): bool;
    
    /**
     * 決済方法名を取得
     */
    public function getName(): string;
}

/**
 * クレジットカード決済
 */
class CreditCardPayment implements PaymentStrategyInterface
{
    private string $cardNumber;
    private string $cvv;
    
    public function __construct(string $cardNumber, string $cvv)
    {
        $this->cardNumber = $cardNumber;
        $this->cvv = $cvv;
    }
    
    public function pay(float $amount): bool
    {
        // クレジットカード決済処理
        echo "Processing credit card payment: {$amount}\n";
        return true;
    }
    
    public function getName(): string
    {
        return 'Credit Card';
    }
}

/**
 * 銀行振込決済
 */
class BankTransferPayment implements PaymentStrategyInterface
{
    private string $accountNumber;
    
    public function __construct(string $accountNumber)
    {
        $this->accountNumber = $accountNumber;
    }
    
    public function pay(float $amount): bool
    {
        // 銀行振込処理
        echo "Processing bank transfer: {$amount}\n";
        return true;
    }
    
    public function getName(): string
    {
        return 'Bank Transfer';
    }
}

/**
 * PayPal決済
 */
class PayPalPayment implements PaymentStrategyInterface
{
    private string $email;
    
    public function __construct(string $email)
    {
        $this->email = $email;
    }
    
    public function pay(float $amount): bool
    {
        // PayPal決済処理
        echo "Processing PayPal payment: {$amount}\n";
        return true;
    }
    
    public function getName(): string
    {
        return 'PayPal';
    }
}

/**
 * 決済コンテキスト
 */
class PaymentProcessor
{
    private PaymentStrategyInterface $strategy;
    
    public function __construct(PaymentStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }
    
    /**
     * 決済方法を変更
     */
    public function setStrategy(PaymentStrategyInterface $strategy): void
    {
        $this->strategy = $strategy;
    }
    
    /**
     * 決済を実行
     */
    public function processPayment(float $amount): bool
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        
        echo "Processing payment via {$this->strategy->getName()}\n";
        return $this->strategy->pay($amount);
    }
}

// 使用例
$processor = new PaymentProcessor(new CreditCardPayment('1234-5678', '123'));
$processor->processPayment(1000.00);

$processor->setStrategy(new PayPalPayment('user@example.com'));
$processor->processPayment(500.00);
```

#### 評価基準

**コーディング規約（25点）**
- 完全な型ヒント: 10点
- PHPDocの質: 10点
- PSR準拠: 5点

**可読性（25点）**
- インターフェース設計: 10点
- クラス名・メソッド名: 10点
- コメント: 5点

**保守性（25点）**
- Strategy パターンの正確な実装: 15点
- 新規戦略の追加が容易: 10点

**効率性（25点）**
- 適切な抽象化: 15点
- 無駄な複雑さなし: 10点

---

## Level 7: Pre-advanced（上級前期）

### 問題 7.1: Monolog スタイルのログハンドラ実装

**カテゴリ**: アーキテクチャ  
**難易度**: ★★★★★☆  
**学習目標**: 既存プロジェクトのアーキテクチャを理解し、拡張する

#### 問題文
Monologプロジェクトのスタイルに従って、Slackにログを送信するカスタムハンドラを実装してください。

要件:
1. `AbstractProcessingHandler` を継承
2. ログレベルがERROR以上の場合のみSlack通知
3. 適切な型ヒント、PHPDoc、エラーハンドリング
4. PHPStan Level 8をパス
5. ユニットテストを含む

#### 期待される解答

```php
<?php declare(strict_types=1);

/*
 * This file is part of the Monolog package.
 *
 * (c) Your Name <your.email@example.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog\Handler;

use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Utils;

/**
 * Slack通知ハンドラ
 *
 * ERROR以上のログをSlackのWebhookに送信します
 *
 * @author Your Name <your.email@example.com>
 */
class SlackNotificationHandler extends AbstractProcessingHandler
{
    private string $webhookUrl;
    private string $channel;
    private string $username;
    private int $timeout;
    
    /**
     * @param string           $webhookUrl Slack Webhook URL
     * @param string           $channel    送信先チャンネル（例: #alerts）
     * @param string           $username   ボット名
     * @param int|string|Level $level      最小ログレベル
     * @param bool             $bubble     次のハンドラに伝播するか
     * @param int              $timeout    タイムアウト秒数
     *
     * @throws \InvalidArgumentException Webhook URLが不正な場合
     */
    public function __construct(
        string $webhookUrl,
        string $channel = '#general',
        string $username = 'Monolog',
        int|string|Level $level = Level::Error,
        bool $bubble = true,
        int $timeout = 10
    ) {
        if (!\filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(
                'Invalid webhook URL provided'
            );
        }
        
        parent::__construct($level, $bubble);
        
        $this->webhookUrl = $webhookUrl;
        $this->channel = $channel;
        $this->username = $username;
        $this->timeout = $timeout;
    }
    
    /**
     * @inheritDoc
     */
    protected function write(LogRecord $record): void
    {
        $payload = $this->buildPayload($record);
        
        try {
            $this->sendToSlack($payload);
        } catch (\Throwable $e) {
            // ログハンドラのエラーは黙って処理（無限ループ防止）
            \error_log(
                'Failed to send log to Slack: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Slack用のペイロードを構築
     *
     * @param LogRecord $record ログレコード
     * @return array<string, mixed> Slackペイロード
     */
    private function buildPayload(LogRecord $record): array
    {
        $color = $this->getColorForLevel($record->level);
        
        return [
            'channel' => $this->channel,
            'username' => $this->username,
            'attachments' => [
                [
                    'color' => $color,
                    'title' => $record->level->getName() . ' Log',
                    'text' => $record->message,
                    'fields' => [
                        [
                            'title' => 'Channel',
                            'value' => $record->channel,
                            'short' => true,
                        ],
                        [
                            'title' => 'DateTime',
                            'value' => $record->datetime->format('Y-m-d H:i:s'),
                            'short' => true,
                        ],
                    ],
                    'footer' => 'Monolog',
                    'ts' => $record->datetime->getTimestamp(),
                ],
            ],
        ];
    }
    
    /**
     * ログレベルに対応する色を取得
     *
     * @phpstan-return 'danger'|'warning'|'good'|'#cccccc'
     */
    private function getColorForLevel(Level $level): string
    {
        return match ($level) {
            Level::Emergency, Level::Alert, Level::Critical, Level::Error 
                => 'danger',
            Level::Warning 
                => 'warning',
            Level::Notice, Level::Info 
                => 'good',
            Level::Debug 
                => '#cccccc',
        };
    }
    
    /**
     * Slackにペイロードを送信
     *
     * @param array<string, mixed> $payload
     * @throws \RuntimeException 送信失敗時
     */
    private function sendToSlack(array $payload): void
    {
        $json = \json_encode($payload);
        if ($json === false) {
            throw new \RuntimeException(
                'Failed to encode payload: ' . \json_last_error_msg()
            );
        }
        
        $ch = \curl_init($this->webhookUrl);
        if ($ch === false) {
            throw new \RuntimeException('Failed to initialize cURL');
        }
        
        \curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . \strlen($json),
            ],
        ]);
        
        $result = \curl_exec($ch);
        $httpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = \curl_error($ch);
        
        \curl_close($ch);
        
        if ($result === false || $httpCode !== 200) {
            throw new \RuntimeException(
                "Slack API returned error: {$error} (HTTP {$httpCode})"
            );
        }
    }
}
```

#### テストコード
```php
<?php declare(strict_types=1);

namespace Monolog\Handler;

use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Test\TestCase;

class SlackNotificationHandlerTest extends TestCase
{
    /**
     * @covers SlackNotificationHandler::__construct
     */
    public function testConstructor(): void
    {
        $handler = new SlackNotificationHandler(
            'https://hooks.slack.com/services/TEST',
            '#test',
            'TestBot'
        );
        
        $this->assertInstanceOf(SlackNotificationHandler::class, $handler);
    }
    
    /**
     * @covers SlackNotificationHandler::__construct
     */
    public function testConstructorThrowsExceptionForInvalidUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid webhook URL provided');
        
        new SlackNotificationHandler('not-a-url');
    }
    
    /**
     * @covers SlackNotificationHandler::write
     */
    public function testHandleErrorLog(): void
    {
        // モックやスタブを使って実際の通信なしでテスト
        $handler = new SlackNotificationHandler(
            'https://hooks.slack.com/services/TEST'
        );
        
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Error,
            message: 'Test error message',
            context: [],
            extra: []
        );
        
        // 実際にはモックしてテストするが、ここでは省略
        $this->assertTrue($handler->handle($record));
    }
}
```

#### 評価基準（100点満点）

**コーディング規約（25点）**
- Monologスタイル完全準拠: 15点
- PHPStan Level 8パス: 10点

**可読性（25点）**
- PHPDocの完全性: 10点
- メソッド分割の適切さ: 10点
- 命名の明確さ: 5点

**保守性（25点）**
- エラーハンドリング: 10点
- テストコード: 10点
- 拡張性: 5点

**効率性（25点）**
- パフォーマンス: 10点
- リソース管理: 10点
- エラー処理の効率性: 5点

---

## Level 10: Master（マスター）

### 問題 10.1: チーム固有のアーキテクチャ課題

**カテゴリ**: チーム固有  
**難易度**: ★★★★★★  
**学習目標**: チームのコードベースを完全に理解し、改善提案ができる

#### 問題文
あなたのチームの実際のプロジェクトから、以下を行ってください：

1. **コードベース分析**
   - 3つ以上のアンチパターンを特定
   - それぞれの問題点を説明
   
2. **リファクタリング提案**
   - 各アンチパターンの改善案を提示
   - コストと効果を見積もる
   
3. **実装**
   - 1つ以上のアンチパターンを実際にリファクタリング
   - ビフォー・アフターのコード提示
   - テストコードを含む

4. **チームへの提案**
   - 改善案のドキュメント作成
   - コードレビュー観点の提案

#### 評価基準（100点満点）

**分析力（30点）**
- アンチパターンの特定精度: 15点
- 問題の本質理解: 15点

**設計力（30点）**
- 改善案の妥当性: 15点
- 実現可能性: 15点

**実装力（30点）**
- リファクタリングの質: 20点
- テストの充実度: 10点

**コミュニケーション力（10点）**
- ドキュメントの質: 5点
- チームへの提案方法: 5点

---

## 📊 問題生成の自動化

### テンプレートエンジン

各レベルの問題は、以下のテンプレートから自動生成可能です：

```yaml
# question_template.yaml
template_id: "refactoring_basic"
level: 3
category: "refactoring"
title: "{feature}のリファクタリング"

bad_code_pattern: |
  <?php
  function {function_name}($param1, $param2) {
      {bad_implementation}
  }

good_code_pattern: |
  <?php declare(strict_types=1);
  
  function {function_name}({type1} $param1, {type2} $param2): {return_type} {
      {good_implementation}
  }

evaluation_criteria:
  coding_standards:
    - strict_types: 10
    - type_hints: 15
  readability:
    - naming: 10
    - structure: 15
  maintainability:
    - error_handling: 15
    - documentation: 10
  efficiency:
    - performance: 15
    - resource_usage: 10
```

### AI生成プロンプト

```python
def generate_question_prompt(level: int, category: str, codebase_style: dict) -> str:
    return f"""
あなたはPHPプログラミングの教育者です。
以下の条件で問題を生成してください。

【条件】
- レベル: {level}/10
- カテゴリ: {category}
- コーディングスタイル: {json.dumps(codebase_style)}

【出力形式】
1. 問題文（日本語）
2. 悪いコード例（修正前）
3. 良いコード例（期待される解答）
4. 評価基準（100点満点の配分）
5. フィードバック例

【重要】
- チームのコーディングスタイルに厳密に従うこと
- 実務で役立つ実践的な問題であること
- 学習者が「なぜ」を理解できる説明を含めること
"""
```

---

## 📈 学習効果の測定

各問題には、以下のメタデータを付与：

```yaml
question_metadata:
  id: "Q_L3_001"
  level: 3
  category: "error_handling"
  skills:
    - "exception_handling"
    - "input_validation"
    - "error_messages"
  difficulty_score: 45  # 0-100
  average_completion_time: 15  # 分
  average_score: 72  # 過去の受験者平均
  success_rate: 0.68  # 一発合格率
```

これにより、AIが各ユーザーに最適な問題を推薦できます。

---

**次のステップ**: この問題サンプルを基に、実際の問題生成エンジンを実装していきます。


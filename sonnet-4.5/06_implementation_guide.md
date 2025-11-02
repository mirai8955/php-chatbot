# 実装ガイド

コードスタイル抽出に基づくPHPコーチングAIを実際に構築するための実装ガイドです。

## 1. システムアーキテクチャ

### 1.1 全体構成

```
┌─────────────────────────────────────────────────────┐
│                   Web Interface                      │
│              (React / Vue.js / PHP)                  │
└────────────────┬────────────────────────────────────┘
                 │
┌────────────────┴────────────────────────────────────┐
│                  API Layer (REST)                    │
│              (Symfony / Laravel / Slim)              │
└─┬─────┬──────┬──────┬──────────┬────────────────────┘
  │     │      │      │          │
  │     │      │      │          │
  v     v      v      v          v
┌───┐ ┌───┐ ┌───┐ ┌───┐  ┌───────────┐
│St │ │Pr │ │Ev │ │Us │  │    AI     │
│yl │ │ob │ │al │ │er │  │  Engine   │
│e  │ │le │ │ua │ │   │  │ (GPT API) │
│   │ │m  │ │ti │ │Ma │  └───────────┘
│Ex │ │Ge │ │on │ │na │
│tr │ │ne │ │   │ │ge │
│ct │ │ra │ │En │ │me │
│or │ │to │ │gi │ │nt │
└───┘ └───┘ └─┬─┘ └───┘
              │
              v
        ┌──────────┐
        │ Database │
        │ (MySQL/  │
        │  Postgre)│
        └──────────┘
```

### 1.2 主要コンポーネント

#### Style Extractor（スタイル抽出器）
```php
// 責務: プロジェクトからコードスタイルを抽出
interface StyleExtractorInterface {
    public function extractFromProject(string $projectPath): StyleGuide;
    public function analyzeFile(string $filePath): FileAnalysis;
    public function generateReport(): StyleReport;
}
```

#### Problem Generator（問題生成器）
```php
// 責務: 抽出したスタイルから問題を生成
interface ProblemGeneratorInterface {
    public function generateProblems(
        StyleGuide $guide,
        int $level,
        int $count
    ): array;
    
    public function validateProblem(Problem $problem): bool;
}
```

#### Evaluation Engine（評価エンジン）
```php
// 責務: ユーザーの解答を評価
interface EvaluationEngineInterface {
    public function evaluate(
        Problem $problem,
        string $userCode
    ): EvaluationResult;
    
    public function generateFeedback(EvaluationResult $result): Feedback;
}
```

#### User Management（ユーザー管理）
```php
// 責務: ユーザー進捗の管理
interface UserManagementInterface {
    public function getUserProgress(int $userId): UserProgress;
    public function updateProgress(int $userId, ProblemResult $result): void;
    public function checkLevelUp(int $userId): ?int;
}
```

---

## 2. 技術スタック

### 2.1 推奨構成

#### バックエンド
```yaml
言語: PHP 8.2+
理由: 
  - 型システムの充実（readonly, union types）
  - パフォーマンス向上
  - 最新機能の活用

フレームワーク: Symfony 6.x または Laravel 10.x
理由:
  - 堅牢なDI container
  - ORM（Doctrine / Eloquent）
  - テスト環境充実
  - コミュニティ活発

コード解析:
  - nikic/PHP-Parser: AST解析
  - squizlabs/PHP_CodeSniffer: スタイルチェック
  - phpstan/phpstan: 静的解析
```

#### フロントエンド
```yaml
フレームワーク: React 18+ または Vue 3+
理由:
  - 豊富なコンポーネント
  - コードハイライト（Prism.js, Monaco Editor）
  - グラフ描画（Chart.js, D3.js）

エディタ: Monaco Editor
理由:
  - VS Code のエディタエンジン
  - シンタックスハイライト
  - インテリセンス

UI: Tailwind CSS + shadcn/ui
理由:
  - モダンな見た目
  - カスタマイズ容易
```

#### データベース
```yaml
RDBMS: PostgreSQL 15+
理由:
  - JSON型サポート（スタイルルール保存）
  - 高度なクエリ機能
  - 信頼性高い

キャッシュ: Redis
理由:
  - セッション管理
  - 頻繁にアクセスするデータ
  - ランキング計算
```

#### AI / ML
```yaml
API: OpenAI GPT-4 API
用途:
  - コード評価の補助
  - フィードバック生成
  - 類似コード検索

代替: Claude API (Anthropic)
理由:
  - 長いコンテキスト
  - コード理解に強い
```

---

## 3. データベース設計

### 3.1 主要テーブル

```sql
-- プロジェクト
CREATE TABLE projects (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    repository_url VARCHAR(500),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- スタイルガイド
CREATE TABLE style_guides (
    id SERIAL PRIMARY KEY,
    project_id INT REFERENCES projects(id),
    version VARCHAR(50) NOT NULL,
    rules JSONB NOT NULL,  -- 抽出されたルール
    extracted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(project_id, version)
);

-- スタイルルール詳細
CREATE TABLE style_rules (
    id SERIAL PRIMARY KEY,
    style_guide_id INT REFERENCES style_guides(id),
    category VARCHAR(100) NOT NULL,  -- naming, formatting, etc.
    subcategory VARCHAR(100),
    rule_name VARCHAR(255) NOT NULL,
    rule_pattern TEXT,  -- 正規表現など
    importance INT DEFAULT 5,  -- 1-10
    examples JSONB,  -- 良い例・悪い例
    
    INDEX idx_category (category),
    INDEX idx_rule_name (rule_name)
);

-- ユーザー
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    current_level INT DEFAULT 1,
    total_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP
);

-- 問題
CREATE TABLE problems (
    id SERIAL PRIMARY KEY,
    style_guide_id INT REFERENCES style_guides(id),
    level INT NOT NULL,  -- 1-5
    type VARCHAR(50) NOT NULL,  -- write_code, fix_code, review, etc.
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    starter_code TEXT,
    test_cases JSONB,
    evaluation_criteria JSONB NOT NULL,
    estimated_time INT,  -- 分
    difficulty_score DECIMAL(3, 1),
    tags JSONB,  -- ['naming', 'class_design']
    
    INDEX idx_level (level),
    INDEX idx_type (type),
    INDEX idx_tags USING GIN (tags)
);

-- ユーザー進捗
CREATE TABLE user_progress (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id),
    problem_id INT REFERENCES problems(id),
    submitted_code TEXT,
    evaluation_result JSONB,  -- スコア、フィードバックなど
    score INT NOT NULL,
    time_spent INT,  -- 秒
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_problem_id (problem_id),
    INDEX idx_submitted_at (submitted_at)
);

-- スキル習得状況
CREATE TABLE user_skills (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id),
    skill_name VARCHAR(100) NOT NULL,
    level INT NOT NULL,  -- 対応するレベル
    mastery_percentage DECIMAL(5, 2) DEFAULT 0.00,  -- 0-100
    problems_completed INT DEFAULT 0,
    average_score DECIMAL(5, 2) DEFAULT 0.00,
    last_practiced_at TIMESTAMP,
    
    UNIQUE(user_id, skill_name, level),
    INDEX idx_user_skill (user_id, skill_name)
);

-- バッジ
CREATE TABLE badges (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    criteria JSONB NOT NULL  -- 獲得条件
);

CREATE TABLE user_badges (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id),
    badge_id INT REFERENCES badges(id),
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(user_id, badge_id),
    INDEX idx_user_id (user_id)
);

-- ランキング（キャッシュ）
CREATE TABLE leaderboard (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) UNIQUE,
    rank INT NOT NULL,
    total_score INT NOT NULL,
    problems_solved INT NOT NULL,
    average_score DECIMAL(5, 2),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_rank (rank)
);
```

---

## 4. コア機能の実装

### 4.1 スタイル抽出器

```php
<?php

declare(strict_types=1);

namespace App\StyleExtraction;

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use App\StyleExtraction\Visitor\NamingVisitor;
use App\StyleExtraction\Visitor\FormattingVisitor;
use App\StyleExtraction\Visitor\TypeHintVisitor;

class StyleExtractor implements StyleExtractorInterface
{
    public function __construct(
        private ParserFactory $parserFactory,
        private array $visitors = []
    ) {
        $this->initializeVisitors();
    }
    
    private function initializeVisitors(): void
    {
        $this->visitors = [
            new NamingVisitor(),
            new FormattingVisitor(),
            new TypeHintVisitor(),
            // ... 他のVisitor
        ];
    }
    
    public function extractFromProject(string $projectPath): StyleGuide
    {
        $files = $this->findPhpFiles($projectPath);
        $analyses = [];
        
        foreach ($files as $file) {
            $analyses[] = $this->analyzeFile($file);
        }
        
        return $this->aggregateAnalyses($analyses);
    }
    
    public function analyzeFile(string $filePath): FileAnalysis
    {
        $code = file_get_contents($filePath);
        $parser = $this->parserFactory->create(ParserFactory::PREFER_PHP8);
        
        try {
            $ast = $parser->parse($code);
        } catch (\Exception $e) {
            return FileAnalysis::error($filePath, $e->getMessage());
        }
        
        $traverser = new NodeTraverser();
        foreach ($this->visitors as $visitor) {
            $traverser->addVisitor($visitor);
        }
        
        $traverser->traverse($ast);
        
        return $this->buildFileAnalysis($filePath, $this->visitors);
    }
    
    private function aggregateAnalyses(array $analyses): StyleGuide
    {
        $aggregator = new StyleAggregator();
        
        foreach ($analyses as $analysis) {
            $aggregator->addAnalysis($analysis);
        }
        
        return $aggregator->generateStyleGuide();
    }
    
    private function findPhpFiles(string $path): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );
        
        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                // vendor やテストを除外
                $filePath = $file->getPathname();
                if (!$this->shouldExclude($filePath)) {
                    $phpFiles[] = $filePath;
                }
            }
        }
        
        return $phpFiles;
    }
    
    private function shouldExclude(string $path): bool
    {
        $excludePatterns = [
            '/vendor/',
            '/tests/',
            '/node_modules/',
        ];
        
        foreach ($excludePatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }
        
        return false;
    }
}
```

### 4.2 問題生成器

```php
<?php

declare(strict_types=1);

namespace App\ProblemGeneration;

class ProblemGenerator implements ProblemGeneratorInterface
{
    public function __construct(
        private TemplateRepository $templateRepo,
        private StyleGuide $styleGuide,
        private AIClient $aiClient
    ) {}
    
    public function generateProblems(
        StyleGuide $guide,
        int $level,
        int $count
    ): array {
        $templates = $this->templateRepo->findByLevel($level);
        $problems = [];
        
        foreach ($templates as $template) {
            if (count($problems) >= $count) {
                break;
            }
            
            $problem = $this->generateFromTemplate($template, $guide);
            
            if ($this->validateProblem($problem)) {
                $problems[] = $problem;
            }
        }
        
        // 不足分はAIで生成
        if (count($problems) < $count) {
            $needed = $count - count($problems);
            $aiProblems = $this->generateWithAI($guide, $level, $needed);
            $problems = array_merge($problems, $aiProblems);
        }
        
        return $problems;
    }
    
    private function generateFromTemplate(
        ProblemTemplate $template,
        StyleGuide $guide
    ): Problem {
        $variables = $this->extractVariables($guide, $template);
        
        return new Problem(
            level: $template->getLevel(),
            type: $template->getType(),
            title: $this->interpolate($template->getTitle(), $variables),
            description: $this->interpolate($template->getDescription(), $variables),
            starterCode: $this->generateStarterCode($template, $guide),
            evaluationCriteria: $this->buildEvaluationCriteria($template, $guide)
        );
    }
    
    private function generateWithAI(
        StyleGuide $guide,
        int $level,
        int $count
    ): array {
        $prompt = $this->buildPrompt($guide, $level);
        
        $response = $this->aiClient->generate([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'あなたはPHPプログラミングの問題を作る専門家です。'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7
        ]);
        
        return $this->parseAIResponse($response, $count);
    }
    
    private function buildPrompt(StyleGuide $guide, int $level): string
    {
        $rules = $guide->getRulesForLevel($level);
        
        return <<<PROMPT
        以下のコーディングスタイルルールに基づいて、
        レベル{$level}の問題を生成してください。
        
        【スタイルルール】
        {$this->formatRules($rules)}
        
        【要件】
        - 実践的なシナリオ
        - 明確な評価基準
        - 模範解答付き
        
        JSON形式で出力してください。
        PROMPT;
    }
    
    public function validateProblem(Problem $problem): bool
    {
        // 必須フィールドチェック
        if (empty($problem->getTitle()) || empty($problem->getDescription())) {
            return false;
        }
        
        // 評価基準の妥当性チェック
        $criteria = $problem->getEvaluationCriteria();
        if (empty($criteria) || array_sum($criteria->getWeights()) !== 100) {
            return false;
        }
        
        // 難易度の適切性チェック
        $estimatedDifficulty = $this->estimateDifficulty($problem);
        $expectedRange = $this->getExpectedDifficultyRange($problem->getLevel());
        
        if ($estimatedDifficulty < $expectedRange['min'] ||
            $estimatedDifficulty > $expectedRange['max']) {
            return false;
        }
        
        return true;
    }
}
```

### 4.3 評価エンジン

```php
<?php

declare(strict_types=1);

namespace App\Evaluation;

class EvaluationEngine implements EvaluationEngineInterface
{
    public function __construct(
        private Parser $parser,
        private array $evaluators,
        private AIClient $aiClient,
        private LoggerInterface $logger
    ) {}
    
    public function evaluate(
        Problem $problem,
        string $userCode
    ): EvaluationResult {
        // 1. 構文チェック
        if (!$this->isSyntacticallyValid($userCode)) {
            return EvaluationResult::syntaxError(
                '構文エラーがあります。コードを修正してください。'
            );
        }
        
        // 2. セキュリティチェック
        $securityIssues = $this->checkSecurity($userCode);
        if (!empty($securityIssues)) {
            return EvaluationResult::securityError($securityIssues);
        }
        
        // 3. 機能テスト
        $functionalResult = $this->runFunctionalTests($problem, $userCode);
        
        // 4. スタイルチェック
        $styleResult = $this->evaluateStyle($problem, $userCode);
        
        // 5. 設計品質評価
        $designResult = $this->evaluateDesign($userCode);
        
        // 6. 総合評価
        $totalScore = $this->calculateTotalScore(
            $problem,
            $functionalResult,
            $styleResult,
            $designResult
        );
        
        // 7. フィードバック生成
        $feedback = $this->generateFeedback(
            $totalScore,
            $functionalResult,
            $styleResult,
            $designResult
        );
        
        return new EvaluationResult(
            score: $totalScore,
            functional: $functionalResult,
            style: $styleResult,
            design: $designResult,
            feedback: $feedback
        );
    }
    
    private function runFunctionalTests(
        Problem $problem,
        string $userCode
    ): FunctionalResult {
        $testRunner = new TestRunner();
        $results = [];
        
        foreach ($problem->getTestCases() as $testCase) {
            try {
                $output = $testRunner->run($userCode, $testCase->getInput());
                $passed = $this->compareOutput(
                    $output,
                    $testCase->getExpectedOutput()
                );
                
                $results[] = [
                    'test' => $testCase->getName(),
                    'passed' => $passed,
                    'expected' => $testCase->getExpectedOutput(),
                    'actual' => $output
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'test' => $testCase->getName(),
                    'passed' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return new FunctionalResult($results);
    }
    
    private function evaluateStyle(
        Problem $problem,
        string $userCode
    ): StyleResult {
        $ast = $this->parser->parse($userCode);
        $scores = [];
        
        foreach ($this->evaluators as $evaluator) {
            $result = $evaluator->evaluate($ast, $problem->getStyleGuide());
            $scores[$evaluator->getName()] = $result;
        }
        
        return new StyleResult($scores);
    }
    
    public function generateFeedback(EvaluationResult $result): Feedback
    {
        // AIを活用したパーソナライズされたフィードバック
        $prompt = $this->buildFeedbackPrompt($result);
        
        $aiResponse = $this->aiClient->generate([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'あなたは優秀なプログラミングコーチです。' .
                                '建設的で具体的なフィードバックを提供してください。'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ]);
        
        return new Feedback(
            overall: $this->generateOverallComment($result->getScore()),
            strengths: $this->identifyStrengths($result),
            improvements: $this->identifyImprovements($result),
            aiSuggestions: $aiResponse->getContent(),
            nextSteps: $this->suggestNextSteps($result)
        );
    }
}
```

---

## 5. API エンドポイント設計

### 5.1 RESTful API

```yaml
# プロジェクト管理
POST   /api/v1/projects
GET    /api/v1/projects/{id}
POST   /api/v1/projects/{id}/extract-style

# 問題
GET    /api/v1/problems?level={level}&type={type}
GET    /api/v1/problems/{id}
POST   /api/v1/problems/{id}/submit

# ユーザー進捗
GET    /api/v1/users/{id}/progress
GET    /api/v1/users/{id}/skills
GET    /api/v1/users/{id}/badges

# 評価
POST   /api/v1/evaluate
GET    /api/v1/problems/{id}/submissions/{submissionId}

# ランキング
GET    /api/v1/leaderboard?period={week|month|all}
```

### 5.2 API 実装例（Laravel）

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EvaluationService;
use Illuminate\Http\Request;

class ProblemController extends Controller
{
    public function __construct(
        private EvaluationService $evaluationService
    ) {}
    
    public function submit(Request $request, int $problemId)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'time_spent' => 'integer|min:0'
        ]);
        
        $problem = Problem::findOrFail($problemId);
        $user = $request->user();
        
        // 評価実行
        $result = $this->evaluationService->evaluate(
            $problem,
            $validated['code'],
            $user
        );
        
        // 進捗保存
        $submission = UserProgress::create([
            'user_id' => $user->id,
            'problem_id' => $problemId,
            'submitted_code' => $validated['code'],
            'evaluation_result' => $result->toArray(),
            'score' => $result->getTotalScore(),
            'time_spent' => $validated['time_spent'] ?? null
        ]);
        
        // スキル更新
        $this->updateUserSkills($user, $problem, $result);
        
        // レベルアップチェック
        $levelUp = $this->checkLevelUp($user);
        
        return response()->json([
            'success' => true,
            'submission_id' => $submission->id,
            'result' => $result->toArray(),
            'level_up' => $levelUp
        ]);
    }
    
    private function updateUserSkills($user, $problem, $result): void
    {
        foreach ($problem->tags as $skill) {
            $userSkill = UserSkill::firstOrCreate([
                'user_id' => $user->id,
                'skill_name' => $skill,
                'level' => $problem->level
            ]);
            
            $userSkill->problems_completed++;
            $userSkill->average_score = (
                ($userSkill->average_score * ($userSkill->problems_completed - 1))
                + $result->getTotalScore()
            ) / $userSkill->problems_completed;
            
            $userSkill->mastery_percentage = min(100,
                ($userSkill->problems_completed / 10) * 100
            );
            
            $userSkill->last_practiced_at = now();
            $userSkill->save();
        }
    }
}
```

---

## 6. フロントエンド実装

### 6.1 問題解答画面（React）

```tsx
import React, { useState } from 'react';
import MonacoEditor from '@monaco-editor/react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

interface Problem {
  id: number;
  title: string;
  description: string;
  starterCode: string;
  level: number;
}

export const ProblemSolvePage: React.FC<{ problem: Problem }> = ({ problem }) => {
  const [code, setCode] = useState(problem.starterCode);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [result, setResult] = useState(null);
  
  const handleSubmit = async () => {
    setIsSubmitting(true);
    
    try {
      const response = await fetch(`/api/v1/problems/${problem.id}/submit`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify({ code })
      });
      
      const data = await response.json();
      setResult(data.result);
      
      if (data.level_up) {
        // レベルアップ通知
        showLevelUpNotification(data.level_up);
      }
    } catch (error) {
      console.error('Submission failed:', error);
    } finally {
      setIsSubmitting(false);
    }
  };
  
  return (
    <div className="grid grid-cols-2 gap-4 h-screen p-4">
      {/* 左側: 問題説明 */}
      <Card className="p-6 overflow-y-auto">
        <h1 className="text-2xl font-bold mb-4">{problem.title}</h1>
        <div className="prose" dangerouslySetInnerHTML={{ __html: problem.description }} />
      </Card>
      
      {/* 右側: エディタ */}
      <div className="flex flex-col">
        <MonacoEditor
          height="70%"
          language="php"
          theme="vs-dark"
          value={code}
          onChange={(value) => setCode(value || '')}
          options={{
            minimap: { enabled: false },
            fontSize: 14,
            lineNumbers: 'on',
            scrollBeyondLastLine: false
          }}
        />
        
        <div className="mt-4">
          <Button
            onClick={handleSubmit}
            disabled={isSubmitting}
            className="w-full"
          >
            {isSubmitting ? '評価中...' : '提出する'}
          </Button>
        </div>
        
        {/* 評価結果 */}
        {result && (
          <ResultDisplay result={result} className="mt-4" />
        )}
      </div>
    </div>
  );
};
```

---

## 7. デプロイとインフラ

### 7.1 Docker構成

```dockerfile
# Dockerfile
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader

CMD ["php-fpm"]
```

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "9000:9000"
    volumes:
      - .:/app
    environment:
      DB_HOST: db
      REDIS_HOST: redis
  
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - .:/app
    depends_on:
      - app
  
  db:
    image: postgres:15
    environment:
      POSTGRES_DB: phpcoach
      POSTGRES_USER: phpcoach
      POSTGRES_PASSWORD: secret
    volumes:
      - db-data:/var/lib/postgresql/data
  
  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

volumes:
  db-data:
```

---

## 8. テスト戦略

### 8.1 ユニットテスト

```php
<?php

namespace Tests\Unit\Evaluation;

use PHPUnit\Framework\TestCase;
use App\Evaluation\StyleEvaluator;

class StyleEvaluatorTest extends TestCase
{
    public function test_evaluates_naming_conventions_correctly(): void
    {
        $evaluator = new StyleEvaluator();
        
        $code = <<<'PHP'
        class UserRepository {
            public function getUserById(int $id): ?User {
                return null;
            }
        }
        PHP;
        
        $result = $evaluator->evaluateNaming($code);
        
        $this->assertTrue($result->isPass());
        $this->assertEquals(20, $result->getScore());
    }
}
```

---

## まとめ

実装の優先順位:

1. **Phase 1**: スタイル抽出器の実装（コア機能）
2. **Phase 2**: 問題生成器の基本実装
3. **Phase 3**: 評価エンジンの実装
4. **Phase 4**: ユーザーインターフェース
5. **Phase 5**: AI統合とフィードバック強化
6. **Phase 6**: ゲーミフィケーション要素

次のアクション:
実際のPHPプロジェクト（Guzzle、Monolog等）でスタイル抽出を試し、有効性を検証しましょう。


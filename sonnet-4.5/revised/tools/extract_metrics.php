<?php declare(strict_types=1);

/**
 * PHPプロジェクトのコーディングスタイルメトリクス自動抽出ツール
 * 
 * 使用方法:
 *   php extract_metrics.php /path/to/project
 * 
 * 出力:
 *   - コンソールに結果を表示
 *   - metrics_output.yaml にYAML形式で出力
 * 
 * @author Claude Sonnet 4.5
 * @date 2025-11-01
 */

class MetricsExtractor
{
    private string $projectRoot;
    private string $srcDir;
    private array $metrics = [];
    
    public function __construct(string $projectRoot, string $srcDir = 'src')
    {
        $this->projectRoot = rtrim($projectRoot, '/');
        $this->srcDir = $srcDir;
        
        if (!is_dir($this->projectRoot)) {
            throw new \InvalidArgumentException("プロジェクトルートが存在しません: {$this->projectRoot}");
        }
        
        if (!is_dir("{$this->projectRoot}/{$this->srcDir}")) {
            throw new \InvalidArgumentException("ソースディレクトリが存在しません: {$this->projectRoot}/{$this->srcDir}");
        }
    }
    
    /**
     * 全メトリクスを抽出
     */
    public function extractAll(): array
    {
        echo "📊 メトリクス抽出開始: {$this->projectRoot}\n\n";
        
        $this->metrics['project'] = $this->getProjectInfo();
        $this->metrics['files'] = $this->countFiles();
        $this->metrics['strict_types'] = $this->checkStrictTypes();
        $this->metrics['array_syntax'] = $this->analyzeArraySyntax();
        $this->metrics['type_system'] = $this->analyzeTypeSystem();
        $this->metrics['imports'] = $this->analyzeImports();
        $this->metrics['phpstan'] = $this->checkPhpStan();
        $this->metrics['php_cs_fixer'] = $this->checkPhpCsFixer();
        
        echo "\n✅ 抽出完了！\n";
        
        return $this->metrics;
    }
    
    /**
     * プロジェクト情報の取得
     */
    private function getProjectInfo(): array
    {
        echo "🔍 プロジェクト情報を取得中...\n";
        
        $composerFile = "{$this->projectRoot}/composer.json";
        
        if (!file_exists($composerFile)) {
            return ['composer_json' => 'not_found'];
        }
        
        $composer = json_decode(file_get_contents($composerFile), true);
        
        return [
            'name' => $composer['name'] ?? 'unknown',
            'php_version' => $composer['require']['php'] ?? 'unknown',
            'type' => $composer['type'] ?? 'unknown',
        ];
    }
    
    /**
     * ファイル数のカウント
     */
    private function countFiles(): array
    {
        echo "📁 ファイル数をカウント中...\n";
        
        $phpFiles = $this->exec("find {$this->projectRoot}/{$this->srcDir} -name '*.php' 2>/dev/null | wc -l");
        $testFiles = $this->exec("find {$this->projectRoot}/tests -name '*Test.php' 2>/dev/null | wc -l");
        
        return [
            'php_files' => (int)trim($phpFiles),
            'test_files' => (int)trim($testFiles),
        ];
    }
    
    /**
     * strict_types宣言のチェック
     */
    private function checkStrictTypes(): array
    {
        echo "🔒 strict_types 宣言をチェック中...\n";
        
        $total = $this->metrics['files']['php_files'];
        
        if ($total === 0) {
            return ['error' => 'no_php_files'];
        }
        
        $withStrict = $this->exec("grep -r 'declare(strict_types=1)' {$this->projectRoot}/{$this->srcDir} --include='*.php' 2>/dev/null | wc -l");
        $count = (int)trim($withStrict);
        
        return [
            'total_files' => $total,
            'with_strict_types' => $count,
            'coverage_percent' => round($count / $total * 100, 2),
            'conclusion' => $count === $total ? '全ファイルで使用' : '部分的に使用',
        ];
    }
    
    /**
     * 配列構文の分析
     */
    private function analyzeArraySyntax(): array
    {
        echo "📦 配列構文を分析中...\n";
        
        $oldSyntax = $this->exec("grep -ro 'array(' {$this->projectRoot}/{$this->srcDir} --include='*.php' 2>/dev/null | wc -l");
        $newSyntaxLines = $this->exec("grep -r '\\[' {$this->projectRoot}/{$this->srcDir} --include='*.php' 2>/dev/null | grep -v '^[[:space:]]*\\*' | grep -v '^[[:space:]]*//' | wc -l");
        
        $oldCount = (int)trim($oldSyntax);
        $newCount = (int)trim($newSyntaxLines);
        $total = $oldCount + $newCount;
        
        if ($total === 0) {
            return ['error' => 'no_arrays_found'];
        }
        
        return [
            'old_syntax_count' => $oldCount,
            'new_syntax_count' => $newCount,
            'short_ratio_percent' => round($newCount / $total * 100, 2),
            'conclusion' => $newCount > $oldCount * 2 ? '短い構文が主流' : '混在している',
        ];
    }
    
    /**
     * 型システムの分析
     */
    private function analyzeTypeSystem(): array
    {
        echo "🏷️  型システムを分析中...\n";
        
        // 型付きプロパティのカウント（string, int, bool, array, object などを検出）
        $typedProps = $this->exec("grep -rE '(protected|private|public)\\s+(string|int|bool|array|object|float|\\\\?[A-Z][a-zA-Z0-9\\\\]+)\\s+\\$' {$this->projectRoot}/{$this->srcDir} --include='*.php' 2>/dev/null | wc -l");
        
        // 完全修飾関数呼び出し（\count, \is_* など）
        $fqnFiles = $this->exec("find {$this->projectRoot}/{$this->srcDir} -name '*.php' -exec grep -l '\\\\\\\\count\\|\\\\\\\\is_\\|\\\\\\\\array_' {} \\; 2>/dev/null | wc -l");
        
        $total = $this->metrics['files']['php_files'];
        $fqnCount = (int)trim($fqnFiles);
        
        return [
            'typed_properties_count' => (int)trim($typedProps),
            'fqn_function_files' => $fqnCount,
            'fqn_ratio_percent' => $total > 0 ? round($fqnCount / $total * 100, 2) : 0,
            'conclusion' => $fqnCount > $total / 3 ? '完全修飾関数を積極的に使用' : '限定的に使用',
        ];
    }
    
    /**
     * import文の分析
     */
    private function analyzeImports(): array
    {
        echo "📥 import文を分析中...\n";
        
        $useStatements = $this->exec("grep -r '^use ' {$this->projectRoot}/{$this->srcDir} --include='*.php' 2>/dev/null | wc -l");
        $filesWithUse = $this->exec("grep -rl '^use ' {$this->projectRoot}/{$this->srcDir} --include='*.php' 2>/dev/null | wc -l");
        
        $total = $this->metrics['files']['php_files'];
        $filesCount = (int)trim($filesWithUse);
        
        return [
            'total_use_statements' => (int)trim($useStatements),
            'files_with_use' => $filesCount,
            'files_with_use_percent' => $total > 0 ? round($filesCount / $total * 100, 2) : 0,
        ];
    }
    
    /**
     * PHPStan設定のチェック
     */
    private function checkPhpStan(): array
    {
        echo "🔍 PHPStan 設定をチェック中...\n";
        
        $neonFile = "{$this->projectRoot}/phpstan.neon.dist";
        $neonAlt = "{$this->projectRoot}/phpstan.neon";
        
        $file = null;
        if (file_exists($neonFile)) {
            $file = $neonFile;
        } elseif (file_exists($neonAlt)) {
            $file = $neonAlt;
        }
        
        if (!$file) {
            return ['found' => false];
        }
        
        $content = file_get_contents($file);
        
        // levelを抽出
        preg_match('/level:\s*(\d+)/', $content, $matches);
        $level = $matches[1] ?? 'unknown';
        
        return [
            'found' => true,
            'file' => basename($file),
            'level' => $level,
            'strict_rules' => str_contains($content, 'phpstan-strict-rules'),
            'deprecation_rules' => str_contains($content, 'phpstan-deprecation-rules'),
            'conclusion' => $level === '8' ? '最高レベルの静的解析' : "レベル{$level}の静的解析",
        ];
    }
    
    /**
     * PHP-CS-Fixer設定のチェック
     */
    private function checkPhpCsFixer(): array
    {
        echo "🔧 PHP-CS-Fixer 設定をチェック中...\n";
        
        $csFixerFile = "{$this->projectRoot}/.php-cs-fixer.php";
        $csFixerDist = "{$this->projectRoot}/.php-cs-fixer.dist.php";
        
        $file = null;
        if (file_exists($csFixerFile)) {
            $file = $csFixerFile;
        } elseif (file_exists($csFixerDist)) {
            $file = $csFixerDist;
        }
        
        if (!$file) {
            return ['found' => false];
        }
        
        $content = file_get_contents($file);
        
        return [
            'found' => true,
            'file' => basename($file),
            'has_psr2' => str_contains($content, '@PSR2'),
            'has_psr12' => str_contains($content, '@PSR12'),
            'has_strict_types' => str_contains($content, 'declare_strict_types'),
            'has_array_syntax' => str_contains($content, 'array_syntax'),
            'rules_count' => substr_count($content, '=>'),
            'conclusion' => 'カスタムルール設定あり',
        ];
    }
    
    /**
     * コマンド実行
     */
    private function exec(string $command): string
    {
        return shell_exec($command) ?? '';
    }
    
    /**
     * 結果を表示
     */
    public function display(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 抽出結果\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $this->displaySection("プロジェクト情報", $this->metrics['project'] ?? []);
        $this->displaySection("ファイル数", $this->metrics['files'] ?? []);
        $this->displaySection("strict_types", $this->metrics['strict_types'] ?? []);
        $this->displaySection("配列構文", $this->metrics['array_syntax'] ?? []);
        $this->displaySection("型システム", $this->metrics['type_system'] ?? []);
        $this->displaySection("imports", $this->metrics['imports'] ?? []);
        $this->displaySection("PHPStan", $this->metrics['phpstan'] ?? []);
        $this->displaySection("PHP-CS-Fixer", $this->metrics['php_cs_fixer'] ?? []);
        
        echo "\n" . str_repeat("=", 60) . "\n";
    }
    
    /**
     * セクション表示
     */
    private function displaySection(string $title, array $data): void
    {
        echo "【{$title}】\n";
        
        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '✅ あり' : '❌ なし';
            }
            echo "  {$key}: {$value}\n";
        }
        
        echo "\n";
    }
    
    /**
     * YAML形式でエクスポート
     */
    public function exportToYaml(string $outputFile = 'metrics_output.yaml'): void
    {
        $yaml = $this->arrayToYaml($this->metrics);
        
        file_put_contents($outputFile, $yaml);
        
        echo "💾 結果を保存しました: {$outputFile}\n";
    }
    
    /**
     * 配列をYAML形式に変換（簡易版）
     */
    private function arrayToYaml(array $data, int $indent = 0): string
    {
        $yaml = '';
        $indentStr = str_repeat('  ', $indent);
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $yaml .= "{$indentStr}{$key}:\n";
                $yaml .= $this->arrayToYaml($value, $indent + 1);
            } elseif (is_bool($value)) {
                $yaml .= "{$indentStr}{$key}: " . ($value ? 'true' : 'false') . "\n";
            } elseif (is_string($value)) {
                $yaml .= "{$indentStr}{$key}: \"{$value}\"\n";
            } else {
                $yaml .= "{$indentStr}{$key}: {$value}\n";
            }
        }
        
        return $yaml;
    }
}

// ===== メイン処理 =====

if (php_sapi_name() !== 'cli') {
    die("このスクリプトはCLIでのみ実行できます\n");
}

// 引数チェック
if ($argc < 2) {
    echo "使用方法: php extract_metrics.php /path/to/project [src_dir]\n";
    echo "\n";
    echo "例:\n";
    echo "  php extract_metrics.php /path/to/monolog\n";
    echo "  php extract_metrics.php /path/to/laravel app\n";
    exit(1);
}

$projectRoot = $argv[1];
$srcDir = $argv[2] ?? 'src';

try {
    $extractor = new MetricsExtractor($projectRoot, $srcDir);
    $extractor->extractAll();
    $extractor->display();
    $extractor->exportToYaml("metrics_output.yaml");
    
    echo "\n✅ 全ての処理が完了しました！\n";
    
} catch (\Exception $e) {
    echo "❌ エラー: {$e->getMessage()}\n";
    exit(1);
}


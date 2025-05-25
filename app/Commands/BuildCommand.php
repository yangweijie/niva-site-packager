<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use ZipArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class BuildCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'build
                            {dir : 要打包的目录路径}
                            {--output= : 输出ZIP文件的目录，默认为命令同级目录}
                            {--level=6 : 压缩等级 (0-9)}
                            {--exclude= : 额外排除的目录或文件，用逗号分隔}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = '将PHP项目打包为www.zip文件';

    /**
     * 常见PHP框架的忽略目录
     */
    private array $commonExcludes = [
        '.git',
        '.svn',
        '.hg',
        'node_modules',
        'vendor',
        '.idea',
        '.vscode',
        '.DS_Store',
        'Thumbs.db',
        '*.log',
        'logs',
        'cache',
        'storage/logs',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'bootstrap/cache',
        '.env.local',
        '.env.*.local',
        'composer.lock',
        'package-lock.json',
        'yarn.lock',
        'tests',
        'phpunit.xml',
        'phpunit.xml.dist',
        '.phpunit.result.cache',
        'coverage',
        '.nyc_output',
        'build',
        'dist',
        'tmp',
        'temp',
    ];

    /**
     * PHP框架特定的排除规则
     */
    private array $frameworkExcludes = [
        'laravel' => [
            'storage/app/public',
            'storage/debugbar',
            'storage/logs',
            'bootstrap/cache',
            'public/storage',
        ],
        'lumen' => [
            'storage/logs',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'bootstrap/cache',
        ],
        'symfony' => [
            'var/cache',
            'var/log',
            'var/sessions',
        ],
        'codeigniter' => [
            'application/cache',
            'application/logs',
        ],
        'yii' => [
            'runtime',
            'assets',
        ],
        'thinkphp' => [
            'runtime',
            'data',
        ],
        'webman' => [
            'runtime',
            'storage/logs',
        ],
        'laminas' => [
            'data/cache',
            'data/logs',
            'data/sessions',
        ],
        'slim' => [
            'logs',
            'cache',
        ],
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sourceDir = $this->argument('dir');
        $outputDir = $this->option('output') ?: getcwd();
        $compressionLevel = (int) $this->option('level');
        $additionalExcludes = $this->option('exclude') ? explode(',', $this->option('exclude')) : [];

        // 验证源目录
        if (!is_dir($sourceDir)) {
            $this->error("目录不存在: {$sourceDir}");
            return 1;
        }

        // 获取绝对路径
        $sourceDir = realpath($sourceDir);
        $outputDir = realpath($outputDir) ?: $outputDir;

        $this->info("开始打包项目...");
        $this->info("源目录: {$sourceDir}");
        $this->info("输出目录: {$outputDir}");

        // 检测PHP框架
        $framework = $this->detectFramework($sourceDir);
        if ($framework) {
            $this->info("检测到框架: {$framework}");
        }

        // 获取排除列表
        $excludes = $this->getExcludeList($sourceDir, $framework, $additionalExcludes);

        // 创建ZIP文件
        $zipPath = $outputDir . DIRECTORY_SEPARATOR . 'www.zip';
        $result = $this->createZip($sourceDir, $zipPath, $excludes, $compressionLevel);

        if ($result) {
            $this->info("打包完成: {$zipPath}");
            $this->info("文件大小: " . $this->formatBytes(filesize($zipPath)));
            return 0;
        } else {
            $this->error("打包失败");
            return 1;
        }
    }

    /**
     * 检测PHP框架类型
     */
    private function detectFramework(string $dir): ?string
    {
        // 检查 composer.json 文件
        $composerFile = $dir . '/composer.json';
        $composer = null;
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
        }

        // Laravel
        if (file_exists($dir . '/artisan') && $composer) {
            if (isset($composer['require']['laravel/framework'])) {
                return 'laravel';
            }
        }

        // Lumen
        if (file_exists($dir . '/artisan') && $composer) {
            if (isset($composer['require']['laravel/lumen-framework'])) {
                return 'lumen';
            }
        }

        // Symfony
        if (file_exists($dir . '/bin/console') && $composer) {
            if (isset($composer['require']['symfony/framework-bundle']) ||
                isset($composer['require']['symfony/symfony'])) {
                return 'symfony';
            }
        }

        // Webman
        if (file_exists($dir . '/start.php') && $composer) {
            if (isset($composer['require']['workerman/webman']) ||
                isset($composer['require']['workerman/webman-framework'])) {
                return 'webman';
            }
        }

        // Laminas (Zend Framework)
        if ($composer) {
            if (isset($composer['require']['laminas/laminas-mvc']) ||
                isset($composer['require']['zendframework/zend-mvc'])) {
                return 'laminas';
            }
        }

        // Slim Framework
        if ($composer) {
            if (isset($composer['require']['slim/slim'])) {
                return 'slim';
            }
        }

        // CodeIgniter
        if (file_exists($dir . '/index.php') && file_exists($dir . '/application/config/config.php')) {
            return 'codeigniter';
        }

        // Yii
        if (file_exists($dir . '/yii') || file_exists($dir . '/protected/yiic.php')) {
            return 'yii';
        }

        // ThinkPHP
        if (file_exists($dir . '/think') || file_exists($dir . '/ThinkPHP')) {
            return 'thinkphp';
        }

        return null;
    }

    /**
     * 获取完整的排除列表
     */
    private function getExcludeList(string $dir, ?string $framework, array $additionalExcludes): array
    {
        $excludes = $this->commonExcludes;

        // 添加框架特定的排除规则
        if ($framework && isset($this->frameworkExcludes[$framework])) {
            $excludes = array_merge($excludes, $this->frameworkExcludes[$framework]);
        }

        // 从.env文件读取排除配置
        $envExcludes = $this->getEnvExcludes($dir);
        if (!empty($envExcludes)) {
            $excludes = array_merge($excludes, $envExcludes);
        }

        // 添加用户指定的排除项
        $excludes = array_merge($excludes, $additionalExcludes);

        return array_unique($excludes);
    }

    /**
     * 从.env文件读取exclude配置
     */
    private function getEnvExcludes(string $dir): array
    {
        $envFile = $dir . '/.env';
        if (!file_exists($envFile)) {
            return [];
        }

        $content = file_get_contents($envFile);
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'EXCLUDE=') === 0) {
                $value = substr($line, 8);
                $value = trim($value, '"\'');

                // 尝试解析为JSON数组
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }

                // 如果不是JSON，按逗号分割
                return array_map('trim', explode(',', $value));
            }
        }

        return [];
    }

    /**
     * 创建ZIP文件
     */
    private function createZip(string $sourceDir, string $zipPath, array $excludes, int $compressionLevel): bool
    {
        $zip = new ZipArchive();
        $result = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== TRUE) {
            $this->error("无法创建ZIP文件: {$zipPath}");
            return false;
        }

        // 设置压缩等级
        $zip->setCompressionIndex(0, ZipArchive::CM_DEFLATE, $compressionLevel);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $fileCount = 0;
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($sourceDir) + 1);

            // 检查是否应该排除此文件
            if ($this->shouldExclude($relativePath, $excludes)) {
                continue;
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } elseif ($file->isFile()) {
                $zip->addFile($filePath, $relativePath);
                $fileCount++;
            }
        }

        $zip->close();

        $this->info("已添加 {$fileCount} 个文件到压缩包");
        return true;
    }

    /**
     * 检查文件是否应该被排除
     */
    private function shouldExclude(string $path, array $excludes): bool
    {
        foreach ($excludes as $exclude) {
            $exclude = trim($exclude);
            if (empty($exclude)) {
                continue;
            }

            // 通配符匹配
            if (strpos($exclude, '*') !== false) {
                if (fnmatch($exclude, $path) || fnmatch($exclude, basename($path))) {
                    return true;
                }
            } else {
                // 精确匹配或路径匹配
                if ($path === $exclude ||
                    strpos($path, $exclude . '/') === 0 ||
                    basename($path) === $exclude) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 格式化文件大小
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}

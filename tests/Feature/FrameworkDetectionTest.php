<?php

use App\Commands\BuildCommand;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->testDir = sys_get_temp_dir() . '/niva_test_' . uniqid();
    mkdir($this->testDir, 0755, true);
});

afterEach(function () {
    if (is_dir($this->testDir)) {
        exec("rm -rf {$this->testDir}");
    }
});

test('detects Laravel framework', function () {
    // 创建 Laravel 项目结构
    file_put_contents($this->testDir . '/artisan', '#!/usr/bin/env php');
    file_put_contents($this->testDir . '/composer.json', json_encode([
        'require' => ['laravel/framework' => '^10.0']
    ]));

    $command = new BuildCommand();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('detectFramework');
    $method->setAccessible(true);

    $result = $method->invoke($command, $this->testDir);
    expect($result)->toBe('laravel');
});

test('detects Lumen framework', function () {
    // 创建 Lumen 项目结构
    file_put_contents($this->testDir . '/artisan', '#!/usr/bin/env php');
    file_put_contents($this->testDir . '/composer.json', json_encode([
        'require' => ['laravel/lumen-framework' => '^10.0']
    ]));

    $command = new BuildCommand();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('detectFramework');
    $method->setAccessible(true);

    $result = $method->invoke($command, $this->testDir);
    expect($result)->toBe('lumen');
});

test('detects Symfony framework', function () {
    // 创建 Symfony 项目结构
    mkdir($this->testDir . '/bin', 0755, true);
    file_put_contents($this->testDir . '/bin/console', '#!/usr/bin/env php');
    file_put_contents($this->testDir . '/composer.json', json_encode([
        'require' => ['symfony/framework-bundle' => '^6.0']
    ]));

    $command = new BuildCommand();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('detectFramework');
    $method->setAccessible(true);

    $result = $method->invoke($command, $this->testDir);
    expect($result)->toBe('symfony');
});

test('detects Webman framework', function () {
    // 创建 Webman 项目结构
    file_put_contents($this->testDir . '/start.php', '<?php require_once __DIR__ . \'/vendor/autoload.php\';');
    file_put_contents($this->testDir . '/composer.json', json_encode([
        'require' => ['workerman/webman' => '^1.5']
    ]));

    $command = new BuildCommand();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('detectFramework');
    $method->setAccessible(true);

    $result = $method->invoke($command, $this->testDir);
    expect($result)->toBe('webman');
});

test('detects Laminas framework', function () {
    // 创建 Laminas 项目结构
    file_put_contents($this->testDir . '/composer.json', json_encode([
        'require' => ['laminas/laminas-mvc' => '^3.0']
    ]));

    $command = new BuildCommand();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('detectFramework');
    $method->setAccessible(true);

    $result = $method->invoke($command, $this->testDir);
    expect($result)->toBe('laminas');
});

test('detects Slim framework', function () {
    // 创建 Slim 项目结构
    file_put_contents($this->testDir . '/composer.json', json_encode([
        'require' => ['slim/slim' => '^4.0']
    ]));

    $command = new BuildCommand();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('detectFramework');
    $method->setAccessible(true);

    $result = $method->invoke($command, $this->testDir);
    expect($result)->toBe('slim');
});

test('detects CodeIgniter framework', function () {
    // 创建 CodeIgniter 项目结构
    file_put_contents($this->testDir . '/index.php', '<?php');
    mkdir($this->testDir . '/application/config', 0755, true);
    file_put_contents($this->testDir . '/application/config/config.php', '<?php');

    $command = new BuildCommand();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('detectFramework');
    $method->setAccessible(true);

    $result = $method->invoke($command, $this->testDir);
    expect($result)->toBe('codeigniter');
});

test('detects Yii framework', function () {
    // 创建 Yii 项目结构
    file_put_contents($this->testDir . '/yii', '#!/usr/bin/env php');

    $command = new BuildCommand();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('detectFramework');
    $method->setAccessible(true);

    $result = $method->invoke($command, $this->testDir);
    expect($result)->toBe('yii');
});

test('detects ThinkPHP framework', function () {
    // 创建 ThinkPHP 项目结构
    file_put_contents($this->testDir . '/think', '#!/usr/bin/env php');

    $command = new BuildCommand();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('detectFramework');
    $method->setAccessible(true);

    $result = $method->invoke($command, $this->testDir);
    expect($result)->toBe('thinkphp');
});

test('returns null for unknown framework', function () {
    // 创建一个没有框架特征的项目
    file_put_contents($this->testDir . '/index.php', '<?php echo "Hello World";');

    $command = new BuildCommand();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('detectFramework');
    $method->setAccessible(true);

    $result = $method->invoke($command, $this->testDir);
    expect($result)->toBeNull();
});

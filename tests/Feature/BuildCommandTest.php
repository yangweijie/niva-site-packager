<?php

use App\Commands\BuildCommand;

test('build command can be instantiated', function () {
    $command = new BuildCommand();
    expect($command)->toBeInstanceOf(BuildCommand::class);
});

test('build command has correct signature', function () {
    $command = new BuildCommand();
    $reflection = new ReflectionClass($command);
    $property = $reflection->getProperty('signature');
    $property->setAccessible(true);
    $signature = $property->getValue($command);

    expect($signature)->toContain('build');
    expect($signature)->toContain('{dir : 要打包的目录路径}');
    expect($signature)->toContain('{--output= : 输出ZIP文件的目录，默认为命令同级目录}');
    expect($signature)->toContain('{--level=6 : 压缩等级 (0-9)}');
    expect($signature)->toContain('{--exclude= : 额外排除的目录或文件，用逗号分隔}');
});

test('build command has correct description', function () {
    $command = new BuildCommand();
    expect($command->getDescription())->toBe('将PHP项目打包为www.zip文件');
});

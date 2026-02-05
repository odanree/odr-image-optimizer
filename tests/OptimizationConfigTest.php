<?php
declare(strict_types=1);

namespace ImageOptimizer\Tests;

use ImageOptimizer\Configuration\OptimizationConfig;
use PHPUnit\Framework\TestCase;

class OptimizationConfigTest extends TestCase
{
    public function testConfigInstantiationWithDefaults(): void
    {
        $config = new OptimizationConfig();

        $this->assertFalse($config->autoOptimize);
        $this->assertFalse($config->enableWebp);
        $this->assertEquals('medium', $config->compressionLevel);
        $this->assertEquals(70, $config->jpegQuality);
    }

    public function testConfigInstantiationWithCustomValues(): void
    {
        $config = new OptimizationConfig(
            autoOptimize: true,
            enableWebp: true,
            compressionLevel: 'high',
            jpegQuality: 80,
        );

        $this->assertTrue($config->autoOptimize);
        $this->assertTrue($config->enableWebp);
        $this->assertEquals('high', $config->compressionLevel);
        $this->assertEquals(80, $config->jpegQuality);
    }

    public function testConfigFromArray(): void
    {
        $data = [
            'autoOptimize' => true,
            'enableWebp' => false,
            'compressionLevel' => 'low',
            'jpegQuality' => 60,
        ];

        $config = OptimizationConfig::fromArray($data);

        $this->assertTrue($config->autoOptimize);
        $this->assertFalse($config->enableWebp);
        $this->assertEquals('low', $config->compressionLevel);
        $this->assertEquals(60, $config->jpegQuality);
    }
}

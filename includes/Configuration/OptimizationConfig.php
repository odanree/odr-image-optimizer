<?php
declare(strict_types=1);

/**
 * Configuration object for the Optimization Engine
 *
 * @package ImageOptimizer\Configuration
 */

namespace ImageOptimizer\Configuration;

readonly class OptimizationConfig
{
    public function __construct(
        public bool $autoOptimize = false,
        public bool $enableWebp = false,
        public string $compressionLevel = 'medium',
        public int $jpegQuality = 70,
        public int $pngCompressionLevel = 8,
        public int $webpQuality = 60,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            autoOptimize: (bool) ($data['auto_optimize'] ?? false),
            enableWebp: (bool) ($data['enable_webp'] ?? false),
            compressionLevel: (string) ($data['compression_level'] ?? 'medium'),
            jpegQuality: (int) ($data['jpeg_quality'] ?? 70),
            pngCompressionLevel: (int) ($data['png_compression_level'] ?? 8),
            webpQuality: (int) ($data['webp_quality'] ?? 60),
        );
    }
}

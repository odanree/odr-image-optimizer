<?php

declare(strict_types=1);




if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

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

    /**
     * Create from associative array
     *
     * @param array<string, mixed> $data Configuration data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        /** @var int $jpegQuality */
        $jpegQuality = (int) ($data['jpeg_quality'] ?? 70);
        /** @var int $pngCompressionLevel */
        $pngCompressionLevel = (int) ($data['png_compression_level'] ?? 8);
        /** @var int $webpQuality */
        $webpQuality = (int) ($data['webp_quality'] ?? 60);

        return new self(
            autoOptimize: (bool) ($data['auto_optimize'] ?? false),
            enableWebp: (bool) ($data['enable_webp'] ?? false),
            compressionLevel: (string) ($data['compression_level'] ?? 'medium'),
            jpegQuality: $jpegQuality,
            pngCompressionLevel: $pngCompressionLevel,
            webpQuality: $webpQuality,
        );
    }
}

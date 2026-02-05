<?php
declare(strict_types=1);

/**
 * Processor Registry - Maps MIME types to Processors (Morph Map Pattern)
 *
 * @package ImageOptimizer\Processor
 */

namespace ImageOptimizer\Processor;

readonly class ProcessorRegistry
{
    /** @var array<string, class-string<ImageProcessorInterface>> */
    private array $map;

    /**
     * @param array<string, class-string<ImageProcessorInterface>> $map MIME type → Processor class mapping
     */
    public function __construct(array $map = [])
    {
        $this->map = $map ?: self::getDefaultMap();
    }

    /**
     * Get default MIME type → Processor mapping
     *
     * @return array<string, class-string<ImageProcessorInterface>>
     */
    public static function getDefaultMap(): array
    {
        return [
            'image/jpeg' => JpegProcessor::class,
            'image/png' => PngProcessor::class,
            'image/webp' => WebpProcessor::class,
        ];
    }

    /**
     * Create a processor for the given MIME type
     *
     * @param string $mimeType
     * @return ImageProcessorInterface|null
     *
     * @throws \InvalidArgumentException
     */
    public function create(string $mimeType): ?ImageProcessorInterface
    {
        if (!isset($this->map[$mimeType])) {
            return null;
        }

        $processorClass = $this->map[$mimeType];

        if (!class_exists($processorClass)) {
            throw new \InvalidArgumentException("Processor class not found: {$processorClass}");
        }

        if (!is_subclass_of($processorClass, ImageProcessorInterface::class)) {
            throw new \InvalidArgumentException(
                "Processor must implement ImageProcessorInterface: {$processorClass}"
            );
        }

        return new $processorClass();
    }

    /**
     * Get all registered MIME types
     *
     * @return string[]
     */
    public function getSupportedMimeTypes(): array
    {
        return array_keys($this->map);
    }

    /**
     * Check if a MIME type is supported
     *
     * @param string $mimeType
     * @return bool
     */
    public function supports(string $mimeType): bool
    {
        return isset($this->map[$mimeType]);
    }

    /**
     * Add or override a processor mapping
     *
     * @param string $mimeType
     * @param class-string<ImageProcessorInterface> $processorClass
     * @return self
     */
    public function register(string $mimeType, string $processorClass): self
    {
        $newMap = $this->map;
        $newMap[$mimeType] = $processorClass;

        return new self($newMap);
    }
}

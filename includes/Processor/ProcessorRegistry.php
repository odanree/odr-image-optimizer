<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access denied.' );
}

declare(strict_types=1);

/**
 * ProcessorRegistry - Morph Map equivalent with Collection behavior
 *
 * A veteran's approach: single responsibility but two well-defined concerns:
 * 1. MIME type discovery (registry/morph map)
 * 2. Collection behavior (iteration, counting)
 *
 * @package ImageOptimizer\Processor
 */

namespace ImageOptimizer\Processor;

use Countable;
use Iterator;

/**
 * @implements Iterator<string, ImageProcessorInterface>
 */
class ProcessorRegistry implements Iterator, Countable
{
    /**
     * @var array<string, ImageProcessorInterface>
     */
    private array $processors;

    private int $position = 0;

    /**
     * Create from array of processor instances
     *
     * @param ImageProcessorInterface ...$processors
     * @return self
     */
    public static function fromProcessors(ImageProcessorInterface ...$processors): self
    {
        $registry = [];
        foreach ($processors as $processor) {
            $registry[$processor->getMimeType()] = $processor;
        }

        return new self($registry);
    }

    /**
     * Create from Morph Map (MIME type → class mapping) with lazy instantiation
     *
     * @param array<string, class-string<ImageProcessorInterface>> $map
     * @return self
     */
    public static function fromMorphMap(array $map): self
    {
        $processors = [];
        foreach ($map as $mimeType => $processorClass) {
            if (!class_exists($processorClass)) {
                throw new \InvalidArgumentException("Processor class not found: {$processorClass}");
            }

            if (!is_subclass_of($processorClass, ImageProcessorInterface::class)) {
                throw new \InvalidArgumentException(
                    "Processor must implement ImageProcessorInterface: {$processorClass}",
                );
            }

            $processors[$mimeType] = new $processorClass();
        }

        return new self($processors);
    }

    /**
     * Create default registry with standard processors
     *
     * @return self
     */
    public static function default(): self
    {
        return self::fromProcessors(
            new JpegProcessor(),
            new PngProcessor(),
            new WebpProcessor(),
        );
    }

    /**
     * @param array<string, ImageProcessorInterface> $processors Keyed by MIME type
     */
    private function __construct(array $processors)
    {
        $this->processors = $processors;
        $this->position = 0;
    }

    /**
     * Find processor by file path
     *
     * @param string $filePath
     * @return ImageProcessorInterface|null
     */
    public function findByFile(string $filePath): ?ImageProcessorInterface
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($filePath)) {
                return $processor;
            }
        }

        return null;
    }

    /**
     * Find processor by MIME type
     *
     * @param string $mimeType
     * @return ImageProcessorInterface|null
     */
    public function findByMimeType(string $mimeType): ?ImageProcessorInterface
    {
        return $this->processors[$mimeType] ?? null;
    }

    /**
     * Check if MIME type is supported
     *
     * @param string $mimeType
     * @return bool
     */
    public function supports(string $mimeType): bool
    {
        return isset($this->processors[$mimeType]);
    }

    /**
     * Get all supported MIME types
     *
     * @return string[]
     */
    public function supportedMimeTypes(): array
    {
        return array_keys($this->processors);
    }

    /**
     * Get all processors
     *
     * @return ImageProcessorInterface[]
     */
    public function all(): array
    {
        return array_values($this->processors);
    }

    /**
     * Register additional processor
     *
     * @param ImageProcessorInterface $processor
     * @return self New registry with added processor
     */
    public function register(ImageProcessorInterface $processor): self
    {
        $newProcessors = $this->processors;
        $newProcessors[$processor->getMimeType()] = $processor;

        return new self($newProcessors);
    }

    // Iterator implementation

    public function current(): ImageProcessorInterface
    {
        $values = array_values($this->processors);
        return $values[$this->position];
    }

    public function key(): string
    {
        $keys = array_keys($this->processors);
        return $keys[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        $values = array_values($this->processors);
        return isset($values[$this->position]);
    }

    // Countable implementation

    public function count(): int
    {
        return count($this->processors);
    }
}

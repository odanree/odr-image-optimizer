<?php
declare(strict_types=1);

/**
 * Immutable Collection of Image Processors
 *
 * @package ImageOptimizer\Processor
 */

namespace ImageOptimizer\Processor;

use Countable;
use Iterator;

/**
 * @implements Iterator<int, ImageProcessorInterface>
 */
class ProcessorCollection implements Iterator, Countable
{
    /** @var ImageProcessorInterface[] */
    private array $processors = [];
    private int $position = 0;

    /**
     * @param ImageProcessorInterface[] $processors
     */
    public function __construct(ImageProcessorInterface ...$processors)
    {
        $this->processors = array_values($processors);
    }

    /**
     * Find a processor that supports the given file
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
     * Find a processor by MIME type
     *
     * @param string $mimeType
     * @return ImageProcessorInterface|null
     */
    public function findByMimeType(string $mimeType): ?ImageProcessorInterface
    {
        foreach ($this->processors as $processor) {
            if ($processor->getMimeType() === $mimeType) {
                return $processor;
            }
        }

        return null;
    }

    /**
     * Get all processors
     *
     * @return ImageProcessorInterface[]
     */
    public function all(): array
    {
        return $this->processors;
    }

    /**
     * Iterator implementation
     */
    public function current(): ImageProcessorInterface
    {
        return $this->processors[$this->position];
    }

    public function key(): int
    {
        return $this->position;
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
        return isset($this->processors[$this->position]);
    }

    /**
     * Countable implementation
     */
    public function count(): int
    {
        return count($this->processors);
    }
}

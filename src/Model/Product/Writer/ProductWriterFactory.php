<?php

declare(strict_types=1);

namespace App\Model\Product\Writer;

use App\Entity\Product;
use App\Model\Product\Writer\Enum\ProductWriteSource;

class ProductWriterFactory
{
    public function __construct(
        private ProductWriterMySQL $productWriterMySQL,
        private ProductWriterCSV $productWriterCSV,
    ) {
    }

    public function create(ProductWriteSource $productWriteSource): ProductWriterInterface
    {
        return match ($productWriteSource) {
            ProductWriteSource::MySQL => $this->productWriterMySQL,
            ProductWriteSource::CSV => $this->productWriterCSV,
            default => throw new \InvalidArgumentException(sprintf('Unsupported product write source "%s"', $productWriteSource->name)),
        };
    }
}

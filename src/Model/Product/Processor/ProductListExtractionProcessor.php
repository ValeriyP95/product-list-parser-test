<?php

declare(strict_types=1);

namespace App\Model\Product\Processor;

use App\Model\Product\Parser\ProductListParserInterface;
use App\Model\Product\Writer\Enum\ProductWriteSource;
use App\Model\Product\Writer\ProductWriterFactory;

class ProductListExtractionProcessor
{
    public function __construct(
        private ProductListParserInterface $productListParser,
        private ProductWriterFactory $productWriterFactory,
    ) {
    }

    public function process(bool $isDryRun = false): void
    {
        if ($productDTOs = $this->productListParser->parse()) {
            foreach (ProductWriteSource::allowedCases() as $productWriteSource) {
                $productWriter = $this->productWriterFactory->create($productWriteSource);

                if (!$isDryRun) {
                    $productWriter->write($productDTOs);
                }
            }
        }
    }
}

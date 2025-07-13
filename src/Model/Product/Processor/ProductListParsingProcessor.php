<?php

declare(strict_types=1);

namespace App\Model\Product\Processor;

use App\Model\Product\Parser\ProductListParserInterface;
use App\Model\Product\Writer\Enum\ProductWriteSource;
use App\Model\Product\Writer\ProductWriterFactory;

class ProductListParsingProcessor
{
    public function __construct(
        private ProductListParserInterface $productListParser,
        private ProductWriterFactory $productWriterFactory,
    ) {
    }

    public function process(bool $isDryRun = false): void
    {
        if ($productDTOs = $this->productListParser->parse()) {
            foreach (ProductWriteSource::cases() as $productWriteSource) {
                $productWriter = $this->productWriterFactory->create($productWriteSource);

                if (!$isDryRun) {
                    $productWriter->write($productDTOs);
                }
            }
        }
    }
}

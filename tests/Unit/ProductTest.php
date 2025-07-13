<?php

declare(strict_types=1);

namespace Test\Unit;

use App\Model\Product\DTO\ProductDTO;
use App\Model\Product\Parser\TelemartProductListParser;
use App\Model\Product\Processor\ProductListParsingProcessor;
use App\Model\Product\Writer\ProductWriterCSV;
use App\Model\Product\Writer\ProductWriterFactory;
use App\Model\Product\Writer\ProductWriterMySQL;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function test_product_writers_are_called_while_processing_product_list_parsing(): void
    {
        $productWriterMySQL = $this->createMock(ProductWriterMySQL::class);
        $productWriterCSV = $this->createMock(ProductWriterCSV::class);
        $productWriterFactory = new ProductWriterFactory(
            $productWriterMySQL,
            $productWriterCSV,
        );

        $productDTOs = [new ProductDTO('test', 100, 'test', 'imageUrl')];
        $productListParser = $this->createMock(TelemartProductListParser::class);
        $productListParser->method('parse')->willReturn($productDTOs);
        $productListParsingProcessor = new ProductListParsingProcessor(
            $productListParser,
            $productWriterFactory
        );

        // Dry run: OFF
        $productWriterMySQL->expects($this->once())->method('write')->with($productDTOs);
        $productWriterCSV->expects($this->once())->method('write')->with($productDTOs);
        $productListParsingProcessor->process();

        // Dry run: ON
        $productWriterMySQL->expects($this->never())->method('write');
        $productWriterCSV->expects($this->never())->method('write');
        $productListParsingProcessor->process(true);
    }
}

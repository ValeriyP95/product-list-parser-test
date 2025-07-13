<?php

declare(strict_types=1);

namespace App\Model\Product\Parser;

use App\Model\Product\DTO\ProductDTO;

interface ProductListParserInterface
{
    /**
     * @return ProductDTO[]
     */
    public function parse(): array;
}

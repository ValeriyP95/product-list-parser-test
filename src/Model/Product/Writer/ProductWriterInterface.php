<?php

declare(strict_types=1);

namespace App\Model\Product\Writer;

use App\Model\Product\DTO\ProductDTO;

interface ProductWriterInterface
{
    /**
     * @param ProductDTO[] $productDTOs
     */
    public function write(array $productDTOs): void;
}

<?php

declare(strict_types=1);

namespace App\Model\Product\DTO;

class ProductDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $price,
        public readonly string $url,
        public readonly string $imageUrl,
    ) {
    }
}

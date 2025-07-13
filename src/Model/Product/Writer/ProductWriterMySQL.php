<?php

declare(strict_types=1);

namespace App\Model\Product\Writer;

use App\Model\Product\DTO\ProductDTO;
use App\Repository\ProductRepository;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

class ProductWriterMySQL implements ProductWriterInterface
{
    public function __construct(
        private ProductRepository $productRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function write(array $productDTOs): void
    {
        Assert::allIsInstanceOf($productDTOs, ProductDTO::class);

        try {
            $products = [];
            foreach ($productDTOs as $productDTO) {
                $product = $this->productRepository->create();

                $product->setName($productDTO->name);
                $product->setPrice($productDTO->price);
                $product->setUrl($productDTO->url);
                $product->setImageUrl($productDTO->imageUrl);

                $products[] = $product;
            }

            if ($products) {
                $this->productRepository->saveMany($products);
            }
        } catch (\Throwable $exception) {
            $this->logger->critical('Error while writing product data to MySQL database.', [
                'exception' => (string) $exception,
            ]);

            throw $exception;
        }
    }
}

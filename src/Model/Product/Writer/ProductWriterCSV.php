<?php

declare(strict_types=1);

namespace App\Model\Product\Writer;

use App\Model\Product\DTO\ProductDTO;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

class ProductWriterCSV implements ProductWriterInterface
{
    public function __construct(
        private $projectDirectory,
        private LoggerInterface $logger,
    ) {
    }

    public function write(array $productDTOs): void
    {
        Assert::allIsInstanceOf($productDTOs, ProductDTO::class);

        $csvData = [];
        $csvData[] = ['Name', 'Price', 'URL', 'Image URL', ];
        foreach ($productDTOs as $productDTO) {
            $csvData[] = [
                $productDTO->name,
                $productDTO->price,
                $productDTO->url,
                $productDTO->imageUrl,
            ];
        }

        $storageDirectoryPath = $this->projectDirectory . '/var/storage/';

        if (!file_exists($storageDirectoryPath)) {
            mkdir($storageDirectoryPath, 0777, true);
        }

        $file = fopen($storageDirectoryPath . 'products.csv', "w");

        try {
            foreach ($csvData as $line) {
                fputcsv($file, $line);
            }
        } catch (\Throwable $exception) {
            $this->logger->critical('Error while writing product data to CSV file.', [
                'exception' => (string) $exception,
            ]);

            throw $exception;
        } finally {
            fclose($file);
        }
    }
}

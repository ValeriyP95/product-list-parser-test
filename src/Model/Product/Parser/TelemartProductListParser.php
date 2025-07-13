<?php

declare(strict_types=1);

namespace App\Model\Product\Parser;

use App\Model\Product\DTO\ProductDTO;
use Psr\Log\LoggerInterface;

class TelemartProductListParser implements ProductListParserInterface
{
    private const PRODUCT_LIST_URL = 'https://telemart.ua/ua/mouse/';
    private const PAGE_QUERY_PARAM = 'page';
    private const PAGES_TO_PARSE_COUNT = 3;

    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return ProductDTO[]
     */
    public function parse(): array
    {
        try {
            $productDTOs = [];

            $doc = new \DOMDocument();

            for ($i = 1; $i <= self::PAGES_TO_PARSE_COUNT; $i++) {
                $productListUrl = self::PRODUCT_LIST_URL;
                if ($i > 1) {
                    $productListUrl .= '?' . self::PAGE_QUERY_PARAM . '=' . $i;
                }

                $htmlContent = @file_get_contents($productListUrl);
                if ($htmlContent === false) {
                    throw new \RuntimeException(sprintf('Could not fetch content from "%s"', $productListUrl));
                }

                libxml_use_internal_errors(true);
                $doc->loadHTML($htmlContent);
                libxml_use_internal_errors(false);
                $xpath = new \DOMXPath($doc);

                $productItems = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " product-item ")]');
                if ($productItems->length > 0) {
                    foreach ($productItems as $productItem) {
                        // Find product name and URL
                        $name = null;
                        $url = null;
                        $titleLink = $xpath->query(".//div[contains(concat(' ', normalize-space(@class), ' '), ' product-item__title ')]/a", $productItem);
                        if ($titleLink->length > 0) {
                            $name = trim($titleLink->item(0)->nodeValue);
                            $url = $titleLink->item(0)->getAttribute('href');
                        }

                        if ($name === null) {
                            throw new \RuntimeException('Product name was not found.');
                        }

                        if ($url === null) {
                            throw new \RuntimeException('Product url was not found.');
                        }

                        // Find product price
                        $price = null;
                        $priceNode = $xpath->query("
                            .//div[
                                contains(concat(' ', normalize-space(@class), ' '), ' product-cost ') and
                                not(contains(concat(' ', normalize-space(@class), ' '), ' product-cost_old ')) and
                                not(contains(concat(' ', normalize-space(@class), ' '), ' product-cost_discount '))
                            ]
                        ", $productItem);
                        if ($priceNode->length > 0) {
                            $price = trim($priceNode->item(0)->nodeValue);
                            $price = preg_replace(['/ /', '/<span>₴<\/span>/'], '', $price);
                            $price = (int) $price;
                            $price *= 100;
                        }

                        if ($price === null) {
                            throw new \RuntimeException('Product price was not found.');
                        }

                        // Find product image URL
                        $imageUrl = null;
                        $imageNode = $xpath->query('(.//div[@class="swiper-slide"])[1]//a/img/@src', $productItem);
                        if ($imageNode->length > 0) {
                            $imageUrl = $imageNode->item(0)->nodeValue;
                        }

                        if ($imageUrl === null) {
                            throw new \RuntimeException('Product image url was not found.');
                        }

                        $productDTOs[] = new ProductDTO(
                            $name,
                            $price,
                            $url,
                            $imageUrl,
                        );
                    }
                }
            }

            return $productDTOs;
        } catch (\Throwable $exception) {
            $this->logger->critical('Error while parsing product list', [
                'exception' => (string) $exception,
            ]);

            throw $exception;
        }
    }
}

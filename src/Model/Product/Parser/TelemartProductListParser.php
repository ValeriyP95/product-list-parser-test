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
    private const TIMEOUT_SECONDS = 10;
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 1000;

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

            for ($i = 1; $i <= self::PAGES_TO_PARSE_COUNT; $i++) {
                $this->logger->info('Parsing page {page}', ['page' => $i]);

                $pageProducts = $this->parsePage($i);
                $productDTOs = array_merge($productDTOs, $pageProducts);

                // Add delay between requests to be respectful
                if ($i < self::PAGES_TO_PARSE_COUNT) {
                    usleep(500000); // 0.5 second delay
                }
            }

            $this->logger->info('Successfully parsed {count} products', ['count' => count($productDTOs)]);
            return $productDTOs;

        } catch (\Throwable $exception) {
            $this->logger->critical('Error while parsing product list', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }

    /**
     * @return ProductDTO[]
     */
    private function parsePage(int $pageNumber): array
    {
        $productListUrl = $this->buildPageUrl($pageNumber);
        $htmlContent = $this->fetchPageContent($productListUrl);

        return $this->parseProductsFromHtml($htmlContent, $pageNumber);
    }

    private function buildPageUrl(int $pageNumber): string
    {
        if ($pageNumber === 1) {
            return self::PRODUCT_LIST_URL;
        }

        return self::PRODUCT_LIST_URL . '?' . self::PAGE_QUERY_PARAM . '=' . $pageNumber;
    }

    private function fetchPageContent(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => self::TIMEOUT_SECONDS,
                'user_agent' => 'Mozilla/5.0 (compatible; ProductParser/1.0)',
                'header' => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: uk-UA,uk;q=0.8,en-US;q=0.5,en;q=0.3',
                    'Accept-Encoding: gzip, deflate',
                    'Connection: keep-alive',
                ]
            ]
        ]);

        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $this->logger->debug('Fetching URL (attempt {attempt}): {url}', [
                    'attempt' => $attempt,
                    'url' => $url
                ]);

                $htmlContent = @file_get_contents($url, false, $context);

                if ($htmlContent === false) {
                    throw new \RuntimeException(sprintf('Failed to fetch content from "%s"', $url));
                }

                if (empty($htmlContent)) {
                    throw new \RuntimeException(sprintf('Empty response from "%s"', $url));
                }

                return $htmlContent;

            } catch (\RuntimeException $e) {
                $lastException = $e;
                $this->logger->warning('Failed to fetch page content (attempt {attempt}): {error}', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'url' => $url
                ]);

                if ($attempt < self::MAX_RETRIES) {
                    usleep(self::RETRY_DELAY_MS * 1000 * $attempt); // Exponential backoff
                }
            }
        }

        throw new \RuntimeException(
            sprintf('Failed to fetch content after %d attempts', self::MAX_RETRIES),
            0,
            $lastException
        );
    }

    /**
     * @return ProductDTO[]
     */
    private function parseProductsFromHtml(string $htmlContent, int $pageNumber): array
    {
        $doc = new \DOMDocument();

        // Suppress HTML parsing warnings
        $previousValue = libxml_use_internal_errors(true);
        $doc->loadHTML($htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousValue);

        $xpath = new \DOMXPath($doc);
        $productItems = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " product-item ")]');

        if ($productItems->length === 0) {
            $this->logger->warning('No products found on page {page}', ['page' => $pageNumber]);
            return [];
        }

        $products = [];
        foreach ($productItems as $index => $productItem) {
            try {
                $product = $this->parseProductItem($xpath, $productItem);
                $products[] = $product;

            } catch (\RuntimeException $e) {
                $this->logger->error('Failed to parse product {index} on page {page}: {error}', [
                    'index' => $index + 1,
                    'page' => $pageNumber,
                    'error' => $e->getMessage()
                ]);
                // Continue with other products instead of failing completely
            }
        }

        $this->logger->info('Parsed {count} products from page {page}', [
            'count' => count($products),
            'page' => $pageNumber
        ]);

        return $products;
    }

    private function parseProductItem(\DOMXPath $xpath, \DOMElement $productItem): ProductDTO
    {
        $name = $this->extractProductName($xpath, $productItem);
        $url = $this->extractProductUrl($xpath, $productItem);
        $price = $this->extractProductPrice($xpath, $productItem);
        $imageUrl = $this->extractProductImageUrl($xpath, $productItem);

        return new ProductDTO($name, $price, $url, $imageUrl);
    }

    private function extractProductName(\DOMXPath $xpath, \DOMElement $productItem): string
    {
        $titleLink = $xpath->query(
            ".//div[contains(concat(' ', normalize-space(@class), ' '), ' product-item__title ')]/a",
            $productItem
        );

        if ($titleLink->length === 0) {
            throw new \RuntimeException('Product name element not found');
        }

        $name = trim($titleLink->item(0)->nodeValue);
        if (empty($name)) {
            throw new \RuntimeException('Product name is empty');
        }

        return $name;
    }

    private function extractProductUrl(\DOMXPath $xpath, \DOMElement $productItem): string
    {
        $titleLink = $xpath->query(
            ".//div[contains(concat(' ', normalize-space(@class), ' '), ' product-item__title ')]/a",
            $productItem
        );

        if ($titleLink->length === 0) {
            throw new \RuntimeException('Product URL element not found');
        }

        $url = $titleLink->item(0)->getAttribute('href');
        if (empty($url)) {
            throw new \RuntimeException('Product URL is empty');
        }

        // Convert relative URLs to absolute URLs
        if (str_starts_with($url, '/')) {
            $url = 'https://telemart.ua' . $url;
        }

        return $url;
    }

    private function extractProductPrice(\DOMXPath $xpath, \DOMElement $productItem): int
    {
        $priceNode = $xpath->query("
            .//div[
                contains(concat(' ', normalize-space(@class), ' '), ' product-cost ') and
                not(contains(concat(' ', normalize-space(@class), ' '), ' product-cost_old ')) and
                not(contains(concat(' ', normalize-space(@class), ' '), ' product-cost_discount '))
            ]
        ", $productItem);

        if ($priceNode->length === 0) {
            throw new \RuntimeException('Product price element not found');
        }

        $priceText = trim($priceNode->item(0)->nodeValue);
        if (empty($priceText)) {
            throw new \RuntimeException('Product price is empty');
        }

        // Clean price text and extract numeric value
        $cleanPrice = preg_replace(['/\s+/', '/₴/', '/[^\d]/'], '', $priceText);

        if (!is_numeric($cleanPrice) || $cleanPrice <= 0) {
            throw new \RuntimeException(sprintf('Invalid price format: "%s"', $priceText));
        }

        return (int)$cleanPrice * 100; // Convert to cents
    }

    private function extractProductImageUrl(\DOMXPath $xpath, \DOMElement $productItem): string
    {
        $imageNode = $xpath->query('(.//div[@class="swiper-slide"])[1]//a/img/@src', $productItem);

        if ($imageNode->length === 0) {
            throw new \RuntimeException('Product image element not found');
        }

        $imageUrl = trim($imageNode->item(0)->nodeValue);
        if (empty($imageUrl)) {
            throw new \RuntimeException('Product image URL is empty');
        }

        // Convert relative URLs to absolute URLs
        if (str_starts_with($imageUrl, '/')) {
            $imageUrl = 'https://telemart.ua' . $imageUrl;
        }

        return $imageUrl;
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\Product;

use App\Model\Product\Processor\ProductListExtractionProcessor;
use App\Repository\ProductRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ProductController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/product/list', methods: ['GET'])]
    public function get(
        ProductRepository $productRepository,
        NormalizerInterface $normalizer,
    ): JsonResponse {
        try {
            $products = $productRepository->findAll();

            return new JsonResponse($normalizer->normalize($products));
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), [
                'exception' => (string) $exception,
            ]);

            return new JsonResponse(['success' => false, 'error' => 'An unexpected error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/product/list/extract', methods: ['POST'])]
    public function extract(
        ProductListExtractionProcessor $productListExtractionProcessor,
    ): JsonResponse {
        try {
            $productListExtractionProcessor->process();

            return new JsonResponse(['success' => true]);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), [
                'exception' => (string) $exception,
            ]);

            return new JsonResponse(['error' => 'An unexpected error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

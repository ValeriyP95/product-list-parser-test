<?php

declare(strict_types=1);

namespace App\Controller\Product;

use App\Model\Product\Processor\ProductListParsingProcessor;
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
    public function getList(
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

    #[Route('/product/list', methods: ['POST'])]
    public function saveList(
        ProductListParsingProcessor $productListParsingProcessor,
    ): JsonResponse {
        try {
            $productListParsingProcessor->process();

            return new JsonResponse(['success' => true]);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), [
                'exception' => (string) $exception,
            ]);

            return new JsonResponse(['error' => 'An unexpected error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

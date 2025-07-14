<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Webmozart\Assert\Assert;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function create(): Product
    {
        return new Product();
    }

    public function saveMany(array $products): void
    {
        Assert::allIsInstanceOf($products, Product::class);

        foreach ($products as $product) {
            $this->getEntityManager()->persist($product);
        }

        $this->getEntityManager()->flush();
    }
}

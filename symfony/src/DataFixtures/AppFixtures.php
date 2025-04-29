<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $product = new Product();
        $product->setName('Sample Product');
        $product->setPrice(19.99);
        $product->setDescription('This is a sample product.');

        $manager->persist($product);
        $manager->flush();
    }
}

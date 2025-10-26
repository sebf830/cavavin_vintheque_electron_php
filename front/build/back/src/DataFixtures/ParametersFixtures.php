<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\Parameters;
use Doctrine\Persistence\ObjectManager;

class ParametersFixtures extends Fixture
{
    public function __construct(
        private ParameterBagInterface $params
    ){}
    
    public function load(ObjectManager $manager): void
    {
        $param = (new Parameters())
        ->setHasStockNotification(false)
        ->setHasInspectorNotification(false)
        ;
        $manager->persist($param);
        $manager->flush();
    }
}
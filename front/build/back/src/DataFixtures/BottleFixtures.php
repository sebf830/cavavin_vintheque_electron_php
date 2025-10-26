<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\Bottle;
use Doctrine\Persistence\ObjectManager;

class BottleFixtures extends Fixture
{
    public function __construct(
        private ParameterBagInterface $params
    ){}
    
    public function load(ObjectManager $manager): void
    {
        $bottle = (new Bottle())
        ->setName('Pouilly-Fuissé (exemple à supprimer)')
        ->setCepageMandatory(["Chardonnay"])
        ->setCepageOptional([])
        ->setRegion('Bourgogne')
        ->setDomaine('Domaine duchamp')
        ->setVignoble("maconnais")
        ->setImage(null)
        ->setType("Blanc")
        ->setType2("sec")
        ->setQuantity(6)
        ->setComments(null)
        ->setYear(2020)
        ->setNote(8)
        ->setCreationDate(new \DateTime())
        ->setAccords("Saint-Jacques poêlées, homard, turbot, volaille noble, risotto à la truffe, fromages doux, escalope de veau.")
        ->setType3("tranquille")
        ->setCountry('France')
        ;
        $manager->persist($bottle);
        $this->addReference('bottle_1', $bottle);
        $manager->flush();
    }
}
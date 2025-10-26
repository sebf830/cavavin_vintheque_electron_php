<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use App\Entity\Note;
use App\DataFixtures\BottleFixtures;
use App\Entity\Bottle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class BottleNoteFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private ParameterBagInterface $params
    ){}
    
    public function load(ObjectManager $manager): void
    {
        $datas = json_decode(file_get_contents($this->params->get('app.root') . '/json/note.json'), true);

        foreach($datas as $data){

            /** @var Bottle $bottle */
            $bottle = $this->getReference($data['bottle'], Bottle::class);

            $note = (new Note) 
            ->setTitre($data['titre'])
            ->setContent($data['content'])
            ->setImage($data['image'])
            ->setBottle($bottle)
            ->setRating($data['rating'])
            ->setCreationDate(new \Datetime());

            $manager->persist($note);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [BottleFixtures::class];
    }
}



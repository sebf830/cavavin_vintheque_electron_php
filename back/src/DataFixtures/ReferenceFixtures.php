<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\Reference;
use Doctrine\Persistence\ObjectManager;

class ReferenceFixtures extends Fixture
{
    public function __construct(
        private ParameterBagInterface $params
    ){}

    public function getType3($name){
        $type3 = ["champagne","cremant","clairette de die","vouvray mousseux ","saumur mousseux","touraine petillant","seyssel mousseux","ayze mousseux","gaillac mousseux","blanquette de limoux","prosecco","franciacorta","lambrusco di sorbara","asti","moscato d'asti","trento","malvasia di castelnuovo don bosco","alta langa","oltrepo pavese","saint peray mousseux","montlouis sur loire Mousseux","cremant d’alsace","cremant de bourgogne","cremant de savoie","vin de corse petillant","cremant du jura","crémant de limoux","blanquette de limoux","blanquette","limoux","cremant de bordeaux","cremant de die"];
        return in_array($this->sluggify($name), $type3) ? "effervescent" : "tranquille";
    }
    
    public function load(ObjectManager $manager): void
    {
        $dir = $this->params->get('app.root') . '/json/vignobles';

        if(!$dir) throw new Exception('Pas de dossier');
        
        $files = scandir($dir);

        foreach($files as $file){
            if($file == '.' || $file == '..' || !file_exists($dir .'/'. $file)) continue;

            $path = $dir .'/'. $file;
            $datas = json_decode(file_get_contents($path), true);

            if(!$datas) continue;

            foreach($datas as $key => $data){
                if(isset($data['appellations_regionales'])){
                    foreach($data['appellations_regionales'] as $ar){
                        
                        $vignoble = !is_null($this->getVignoble($ar['name'])) ? $this->getVignoble($ar['name']) : $key;
                        $reference = (new Reference())
                        ->setName($ar['name'])
                        ->setCepages($ar['cepages'])
                        ->setRegion($key)
                        ->setVignoble($vignoble )
                        ->setAccords($ar['accords'])
                        ->setType($ar['type'])
                        ->setType2($ar['type2'])
                        ->setType3($this->getType3($ar['name']))
                        ->setCountry('France')
                        ;
                        $manager->persist($reference);
                    }

                    if(isset($data['appellations_communales'])){
                        foreach($data['appellations_communales'] as $ar){

                        $vignoble = !is_null($this->getVignoble($ar['name'])) ? $this->getVignoble($ar['name']) : $key;
                        $reference = (new Reference())
                        ->setName($ar['name'])
                        ->setCepages($ar['cepages'])
                        ->setRegion($key)
                        ->setVignoble($vignoble)
                        ->setAccords($ar['accords'])
                        ->setType($ar['type'])
                        ->setType2($ar['type2'])
                        ->setType3($this->getType3($ar['name']))
                        ->setCountry('France')
                        ;
                        $manager->persist($reference);
                        }
                    }
                }
            }
        }
        $manager->flush();


        $datas2 = json_decode(file_get_contents($this->params->get('app.root') . '/json/vignobles_italiens/italie.json'), true);
        
        foreach($datas2 as $data){
            foreach($data as $key => $objects){
                foreach($objects as $object){
                    $reference = (new Reference())
                    ->setName($object['name'])
                    ->setCepages($object['cepages'])
                    ->setRegion($key)
                    ->setVignoble($key)
                    ->setAccords($object['accords'])
                    ->setType($object['type'])
                    ->setType2($object['type2'])
                    ->setType3($this->getType3($object['name']))
                    ->setCountry('Italie');
                    $manager->persist($reference);
                }
            }
        }
        $manager->flush();

        $datas3 = json_decode(file_get_contents($this->params->get('app.root') . '/json/vignobles_espagnols/espagne.json'), true);
        
        foreach($datas3 as $data){
            foreach($data as $key => $objects){
                foreach($objects as $object){

                    $vignoble = !is_null($this->getVignoble($object['name'])) ? $this->getVignoble($object['name']) : $key;
                    $reference = (new Reference())
                    ->setName($object['name'])
                    ->setCepages($object['cepages'])
                    ->setRegion($key)
                    ->setVignoble($vignoble)
                    ->setAccords($object['accords'])
                    ->setType($object['type'])
                    ->setType2($object['type2'])
                    ->setType3($this->getType3($object['name']))
                    ->setCountry('Espagne');
                    $manager->persist($reference);
                }
            }
        }
        $manager->flush();

        $datas4 = json_decode(file_get_contents($this->params->get('app.root') . '/json/vignobles_allemands/allemagne.json'), true);
       
        foreach($datas4 as $data){
            foreach($data as $key => $objects){
                foreach($objects as $object){

                    $vignoble = !is_null($this->getVignoble($object['name'])) ? $this->getVignoble($object['name']) : $key;
                    $reference = (new Reference())
                    ->setName($object['name'])
                    ->setCepages($object['cepages'])
                    ->setRegion($key)
                    ->setVignoble($vignoble)
                    ->setAccords($object['accords'])
                    ->setType($object['type'])
                    ->setType2($object['type2'])
                    ->setType3($this->getType3($object['name']))
                    ->setCountry('Allemagne');
                    $manager->persist($reference);
                }
            }
        }
        $manager->flush();
    }

    public function getVignoble($name){
        $datas = json_decode(file_get_contents($this->params->get('app.root') . '/json/sous_vignobles.json'), true);
        foreach($datas as $key => $data){
            if($this->sluggify($name) == $this->sluggify($key)) return $data;
        }
        return null;
    }

    public function sluggify($name){
        $name = strtr($name, ['É' => 'e', 'È' => 'e', 'Ê' => 'e', 'À' => 'a']);
        $name = strtolower($name);
        $name = strtr($name, ['-' => ' ','_' => ' ',':' => ' ','é' => 'e','è' => 'e','ê' => 'e','à' => 'a','â' => 'a','î' => 'i', 'ô' => 'o']);
        return trim($name);
    }
}
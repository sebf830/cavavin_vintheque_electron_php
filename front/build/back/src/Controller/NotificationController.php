<?php 
namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Notification;
use App\Entity\Bottle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Parameters;
use App\Entity\Reference;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    public function __construct(
        private ParameterBagInterface $params,
        private EntityManagerInterface $em ){}


    #[Route('/update/stock-notification', name: 'app_update_stock_notifications', methods:['POST'])]
    public function update_stock_notification(): JsonResponse
    {
        $notifications = $this->em->getRepository(Notification::class)->findBy(
            ['type' => 'stock']
        );

        foreach($notifications as $el){
            $el->setIsClosedByUser(true);
        }
        $this->em->flush();

        return new JsonResponse([], 200);
    }

    #[Route('/update/inspector-notification', name: 'app_update_inspector_notifications', methods:['POST'])]
    public function update_inspector_notification(): JsonResponse
    {
        $notifications = $this->em->getRepository(Notification::class)->findBy(
            ['type' => 'inspector']
        );

        foreach($notifications as $el){
            $el->setIsClosedByUser(true);
        }
        $this->em->flush();

        return new JsonResponse([], 200);
    }

    #[Route('/settings/parameters', name: 'app_setting_parameters', methods:['GET'])]
    public function getparametersSettings(): JsonResponse
    {
        $params = $this->em->getRepository(Parameters::class)->findAll();
        $param = reset($params);

        if (!$param) return new JsonResponse(['error' => 'User parameters not found'], 404);

        $accessStock = $param->hasStockNotification() == null || !$param->hasStockNotification() ? false : true;
        $accessInspector = is_null($param->hasInspectorNotification()) || !$param->hasInspectorNotification() ? false : true;

        return new JsonResponse([
            'hasStockNotification' => $accessStock,
            'hasInspectorNotification' => $accessInspector 
        ]);
    }

    #[Route('/setting/outofstock', name: 'app_setting_outofstock', methods:['POST'])]
    public function outofstock(Request $request): JsonResponse{

        $data = json_decode($request->getContent(), true);

        if($data == null) return new JsonResponse(['error' => 'missing parameters'], 400);

        $params = $this->em->getRepository(Parameters::class)->findAll();
        $param = reset($params);

        $param->setHasStockNotification($data['enabled']);

        $this->em->flush();

        return new JsonResponse(['success' => true, 'value' => $data['enabled']], 200);
    }

    #[Route('/setting/inspector', name: 'app_setting_inspector', methods:['POST'])]
    public function set_inspector(Request $request): JsonResponse{

        $data = json_decode($request->getContent(), true);

        if($data == null) return new JsonResponse(['error' => 'missing parameters'], 400);

        $params = $this->em->getRepository(Parameters::class)->findAll();
        $param = reset($params);

        $param->setHasInspectorNotification($data['enabled']);

        $this->em->flush();

        return new JsonResponse(['success' => true, 'value' => $data['enabled']], 200);
    }

    private function createAndPersistInspectorNotif($message, $bottle, $code){
        $notification = (new Notification)
        ->setMessage($message)
        ->setType('inspector')
        ->setIsClosedByUser(false)
        ->setBottle($bottle)
        ->setDate(new \DateTime())
        ->setCode($code);
        $this->em->persist($notification);
    }

    #[Route('/inspector-notification', name: 'app_inspector_notification', methods:['GET'])]
    public function getInspectorNotification(): Response{

        $notifRepo = $this->em->getRepository(Notification::class);
        $params = $this->em->getRepository(Parameters::class)->findAll();
        $param = reset($params);

        if(!$param->hasInspectorNotification()) return new JsonResponse([], 200);

        // On clean les notifications déjà lues et qui plus de 1 mois
        $notifications = $notifRepo->findBy(['type' => 'inspector','isClosedByUser' => true ]);
        foreach($notifications as $el){
            $age = $el->getDate()->diff(new \DateTime());
            if ($age->days > 30)  $this->em->remove($el);
        } $this->em->flush();

        // creation de nouvelles notifications 
        $annee = (new \DateTime())->format('Y');
        foreach($this->em->getRepository(Bottle::class)->findAll() as $bottle){

            // champs obligatoires manquants
            if(!$bottle->getName())  {
                $notifExist = $this->em->getRepository(Notification::class)->findBy(['type' => 'inspector', 'bottle' => $bottle, 'code' => 'emptyName']);
                if(!$notifExist) $this->createAndPersistInspectorNotif("Une réference sans nom a été enregistré, pensez à renseigner l'appellation", $bottle, 'emptyName');
            }
            if(!$bottle->getDomaine()) {
                $notifExist = $this->em->getRepository(Notification::class)->findBy(['type' => 'inspector', 'bottle' => $bottle, 'code' => 'emptyDomaine']);
                if(!$notifExist) $this->createAndPersistInspectorNotif("{$bottle->getName()} {$bottle->getYear()} n'a pas de domaine renseigné", $bottle, 'emptyDomaine');
            }
            if(!$bottle->getType()) {
                $notifExist = $this->em->getRepository(Notification::class)->findBy(['type' => 'inspector', 'bottle' => $bottle, 'code' => 'emptyType']);
                if(!$notifExist) $this->createAndPersistInspectorNotif("{$bottle->getName()}({$bottle->getDomaine()} {$bottle->getYear()}) n'a pas de couleur renseignée", $bottle, 'emptyType');
            }
            if(!$bottle->getType2()) {
                $notifExist = $this->em->getRepository(Notification::class)->findBy(['type' => 'inspector', 'bottle' => $bottle, 'code' => 'emptyType2']);
                if(!$notifExist) $this->createAndPersistInspectorNotif("{$bottle->getName()}({$bottle->getDomaine()} {$bottle->getYear()}) n'a pas de type de sucrosité renseigné", $bottle, 'emptyType2');
            }
            if(!$bottle->getregion()) {
                $notifExist = $this->em->getRepository(Notification::class)->findBy(['type' => 'inspector', 'bottle' => $bottle, 'code' => 'emptyRegion']);
                $this->createAndPersistInspectorNotif("{$bottle->getName()}({$bottle->getDomaine()} {$bottle->getYear()}) n'a pas de région viticole renseignée", $bottle, 'emptyRegion');
            }

            // 2 vins ont le meme nom + domaine + année
            $exists = $this->em->getRepository(Bottle::class)->findBy(['name' => $bottle->getName(), 'domaine' => $bottle->getDomaine(), 'year' => $bottle->getYear()]);
            if(count($exists) > 1) {
                $notifExist = $this->em->getRepository(Notification::class)->findBy(['type' => 'inspector', 'bottle' => $bottle, 'code' => 'doublon']);
                if(!$notifExist) $this->createAndPersistInspectorNotif("{$bottle->getName()}({$bottle->getDomaine()} {$bottle->getYear()}) : cette appellation a été renseignée plusieurs fois, pensez à regrouper?", $bottle, 'doublon');
            }

            // Année 
            if((int)$bottle->getYear() > (int)$annee) {
                $notifExist = $this->em->getRepository(Notification::class)->findBy(['type' => 'inspector', 'bottle' => $bottle, 'code' => 'tooHighDate']);
                if(!$notifExist) $this->createAndPersistInspectorNotif("{$bottle->getName()}({$bottle->getDomaine()} {$bottle->getYear()}) : année invalide", $bottle, 'tooHighDate');
            }
            if((int)$bottle->getYear() < 1940) {
                $notifExist = $this->em->getRepository(Notification::class)->findBy(['type' => 'inspector', 'bottle' => $bottle, 'code' => 'tooLowDate']);
                if(!$notifExist) $this->createAndPersistInspectorNotif("{$bottle->getName()}({$bottle->getDomaine()} {$bottle->getYear()}) : l'année semble trop ancienne", $bottle, 'tooLowDate');
            }

            // Comparaison avec la reference 
            $names = [$bottle->getName(), strtolower($bottle->getName())];
            $refs = $this->em->getRepository(Reference::class)
                ->createQueryBuilder('e')
                ->where('e.name IN (:names)')
                ->setParameter('names', $names)
                ->getQuery()->getResult();

            if($refs && count($refs) > 0){
                $ref = reset($refs);
                if(!in_array(strtolower($bottle->getType()),  array_map('strtolower', $ref->getType()))) {
                    $notifExist = $this->em->getRepository(Notification::class)->findBy(['type' => 'inspector', 'bottle' => $bottle, 'code' => 'invalidType']);
                    if(!$notifExist) $this->createAndPersistInspectorNotif("{$bottle->getName()} {$bottle->getDomaine()} {$bottle->getYear()} : la couleur ne semble erronnée ou non disponible pour cette appellation",$bottle, 'invalidType');
                }
                if(!in_array(strtolower($bottle->getType2()), array_map('strtolower', $ref->getType2()))) {
                    $notifExist = $this->em->getRepository(Notification::class)->findBy(['type' => 'inspector', 'bottle' => $bottle, 'code' => 'invalidType2']);
                    if(!$notifExist) $this->createAndPersistInspectorNotif("{$bottle->getName()}{$bottle->getDomaine()} {$bottle->getYear()} : le type de sucrosité semble incorrect ou non disponible pour cette appellation",$bottle, 'invalidType2');
                }
            }

            if(strtolower($bottle->getType3()) == 'tranquille' && !$bottle->getYear()) {
                $notifExist = $this->em->getRepository(Notification::class)->findBy(['type' => 'inspector', 'bottle' => $bottle, 'code' => 'invalidType3']);
                if(!$notifExist) $this->createAndPersistInspectorNotif("{$bottle->getName()} {$bottle->getDomaine()} {$bottle->getYear()} : cette appellation n'a pas de millésime renseigné",$bottle, 'invalidType3');
            }

            $total = 0;
            //Si il y a des notes de dégustation et une note
            if($bottle->getNotes() && count($bottle->getNotes()) > 0){
                foreach($bottle->getNotes() as $note){
                    $total += (int)$note->getRating();
                }

                $score = $total / count($bottle->getNotes());
                // moyenne des notes ++ et note globale --
                if($score > 6 && (int)$bottle->getNote() < 5) {
                    $notifExist = $this->em->getRepository(Notification::class)->findBy(['type' => 'inspector', 'bottle' => $bottle, 'code' => 'tooHighScore']);
                    if(!$notifExist) $this->createAndPersistInspectorNotif("{$bottle->getName()} {$bottle->getDomaine()} {$bottle->getYear()} : La note globale du vin semble élévé par rapport aux notes de dégustation",$bottle, 'tooHighScore');
                }
                // moyenne des notes
                if($score < 5 && (int)$bottle->getNote() > 6) {
                     $notifExist = $this->em->getRepository(Notification::class)->findBy(['type' => 'inspector', 'bottle' => $bottle, 'code' => 'tooLowScore']);
                     if(!$notifExist) $this->createAndPersistInspectorNotif("{$bottle->getName()} {$bottle->getDomaine()} {$bottle->getYear()} : La note globale du vin semble faible par rapport aux notes de dégustation",$bottle, 'tooLowScore');
                }
            }

        }
        // enregistrer toutes les notifications crées
        $this->em->flush();


        // On récupère les notifs à afficher
        $datas = $notifRepo->createQueryBuilder('n')
            ->where('n.type = :type')
            ->andWhere('n.isClosedByUser = :false')
            ->setParameter('type', "inspector")
            ->setParameter('false', false)
            ->getQuery()->getArrayResult();

        return new JsonResponse($datas , 200);
    }

    #[Route('/out-of-stock', name: 'app_out_of_stock_bottles', methods:['GET'])]
    public function getOutOfStockBottlesNotification(): Response{

        $notifRepo = $this->em->getRepository(Notification::class);
        $params = $this->em->getRepository(Parameters::class)->findAll();
        $param = reset($params);

        if(!$param->hasStockNotification()) return new JsonResponse([], 200);

        // On clean les notifications déjà lues et qui plus de 1 mois
        $notifications = $notifRepo->findBy(['type' => "stock", "isClosedByUser" => true]);
        foreach($notifications as $el){

            $age = $el->getDate()->diff(new \DateTime());
            if ($el->isClosedByUser() && $age->days > 30)  $this->em->remove($el);
            
        }$this->em->flush();

        // on update en BDD les notifications out of stock
        $bottlesOutOfStock = $this->em->getRepository(Bottle::class)->findBy(['quantity' => 0]);

        foreach($bottlesOutOfStock as $bottle){
                $exist = $this->em->getRepository(Notification::class)->findOneBy(['type' => 'stock', 'bottle' => $bottle]);

                // On persite uniquement les nouvelles notifications
                if($exist == null){
                    $message = ucfirst($bottle->getName() .' '. ucfirst($bottle->getDomaine()) . ' ' . $bottle->getYear() . ' : Stock épuisé');
                    $notification = (new Notification)
                    ->setMessage($message)
                    ->setType('stock')
                    ->setIsClosedByUser(false)
                    ->setBottle($bottle)
                    ->setDate(new \DateTime());
                    $this->em->persist($notification);
                }
        } $this->em->flush();

        $datas = $notifRepo->createQueryBuilder('n')
            ->where('n.type = :type')
            ->andWhere('n.isClosedByUser = :false')
            ->setParameter('type', "stock")
            ->setParameter('false', false)
            ->getQuery()->getArrayResult();

        return new JsonResponse($datas , 200);
    }
}
<?php 
namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Entity\Note;
use App\Entity\Bottle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\BottleRepository;
use App\Repository\NoteRepository;
use App\Repository\ReferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    public function __construct(
        private ParameterBagInterface $params,
        private EntityManagerInterface $em 
    ){}
    
    #[Route('/api/search-reference', name: 'app_search_reference', methods:(['POST']))]
    public function search_bottle(Request $request, ReferenceRepository $refRepo): Response
    {
        $data = json_decode($request->getContent(), true);
        $query = $data['query'];

        if (strlen($query) < 2) return new JsonResponse([], 200); 

        $datas = $refRepo->createQueryBuilder('b')
            ->select('b.id, b.name')
            ->where('b.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('b.name', 'ASC')
            ->setMaxResults(12)
            ->getQuery()
            ->getArrayResult();

        return new JsonResponse($datas, 200);
    }

    #[Route('/api/bottle-create', name: 'app_create_bottle', methods:(['POST']))]
    public function create_bottle(Request $request): Response
    {
        $datas = $request->request->all();
        if(!$datas) return new JsonResponse(['error' => 'Données manquantes'], 400);

        $bottle = (new Bottle())
        ->setCepageMandatory($request->get('mandatory'))
        ->setCepageOptional($request->get('optional'))
        ->setAccords($request->request->get('accords'))
        ->setVignoble($request->request->get('vignoble'))
        ->setName($request->request->get('name'))
        ->setType($request->request->get('type'))
        ->setType2($request->request->get('type2'))
        ->setType3($request->request->get('type3'))
        ->setRegion($request->request->get('region'))
        ->setDomaine($request->request->get('domaine'))
        ->setComments($request->request?->get('comments') ?? null)
        ->setYear($request->request->get('year'))
        ->setQuantity((int) $request->request->get('quantity'))
        ->setCreationDate(new \DateTime())
        ->setCountry($request->request->get('country'));

        /** @var UploadedFile|null $image */
        $image = $request->files->get('image');

        if ($image) {
            $filename = uniqid() . '.' . $image->guessExtension();
            try {
                $image->move('../public/uploads/', $filename);
                $bottle->setImage($filename);
            } catch (FileException $e) {
                return new JsonResponse(['error' => 'Erreur lors de l\'upload de l\'image'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        $this->em->persist($bottle);
        $this->em->flush();
        
        return new JsonResponse(['success' => 'Bouteille enregistrée'], 200);
    }

    #[Route('/api/types', name: 'app_get_types', methods:(['GET']))]
    public function get_types(): Response
    {
        $type = json_decode(file_get_contents($this->params->get('app.root') . '/json/type.json'), true);
        $type2 = json_decode(file_get_contents($this->params->get('app.root') . '/json/type2.json'), true);

        return new JsonResponse(['type'=>$type, 'type2'=>$type2], 200);
    }

    #[Route('/api/bottles', name: 'app_list_bottles', methods:(['GET']))]
    public function list(Request $request, BottleRepository $bottleRepo): Response
    {
        $limit = (int) $request->query->get('limit', 50);
        $page = (int) $request->query->get('page', 1);
        $search = $request->query->get('search', '');
        $filters = explode(',', $request->query->get('filters', ''));

        if ($limit < 1)  $limit = 50;
        if ($page < 1)  $page = 1;

        $offset = ($page - 1) * $limit; // offset

        $filters = array_filter($filters, fn($f) => in_array($f, ['name', 'domaine', 'year', 'type', 'type2', 'region']));

        // Appel au repository avec filtre & pagination
        [$items, $totalCount] = $bottleRepo->findWithPaginationAndSearch($limit, $offset, $search, $filters);

        $totalPages = (int) ceil($totalCount / $limit);

        // Transformer les entités en tableau (ou via le Serializer)
        $data = array_map(function ($b) {
            return [
                'id' => $b->getId(),
                'name' => $b->getName(),
                'domaine' => $b->getDomaine(),
                'year' => $b->getYear(),
                'type' => $b->getType(),
                'type2' => $b->getType2(),
                'region' => $b->getRegion(),
                'quantity' => $b->getQuantity()
            ];
        }, $items);

        return new JsonResponse([
            'items' => $data,
            'totalCount' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
        ], 200);
    }

    #[Route('/api/reference/{id}', name: 'app_get_reference', methods:(['GET']))]
    public function get_reference(int $id, ReferenceRepository $refRepo): Response
    {
        $reference = $refRepo->createQueryBuilder('b')
            ->where('b.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();

        $reference = $reference[0];

        $type = array_map(fn($name) => ['name' => $name], $reference['type']);
        $type2 = array_map(fn($name) => ['name' => $name], $reference['type2']);
        $reference['type']= $type;
        $reference['type2']= $type2;

        return new JsonResponse($reference, 200);
    }

    #[Route('/api/bottle/{id}', name: 'api_bottle_show', methods: ['GET'])]
    public function show(int $id, BottleRepository $repo): JsonResponse
    {
        $bottle = $repo->createQueryBuilder('b')
            ->leftJoin('b.notes', 'n')
            ->addSelect('n')
            ->where('b.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();

        return new JsonResponse($bottle[0], 200);
    }

    #[Route('/api/bottle/{id}/rate', name: 'api_bottle_rate', methods: ['POST'])]
    public function rate_bottle(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['rating']))  return new JsonResponse(['error' => 'Note manquante'], 400);

        $bottle = $this->em->getRepository(Bottle::class)->find($id);
        $bottle->setNote((int)$data['rating']);
        $this->em->flush();

        return new JsonResponse(['success' => true, 200]);
    }

    #[Route('/api/edit-bottle/{id}', name: 'api_bottle_edit', methods: ['POST'])]
    public function edit_bottle(int $id, Request $request): JsonResponse
    { 
        $datas = $request->request->all();
        if(!$datas) return new JsonResponse(['error' => 'Données manquantes'], 400);

        $bottle = $this->em->getRepository(Bottle::class)->find($id);

        $bottle 
        ->setName($request->request->get('name'))
        ->setType($request->request->get('type'))
        ->setType2($request->request->get('type2'))
        ->setRegion($request->request->get('region'))
        ->setComments($request->request?->get('comments') ?? null)
        ->setDomaine($request->request->get('domaine'))
        ->setYear($request->request->get('year'))
        ->setQuantity((int) $request->request->get('quantity'))
        ->setCountry($request->request->get('country'))
        ->setCreationDate(new \DateTime());

        /** @var UploadedFile|null $image */
        $image = $request->files->get('image');

        if ($image) {
            $filename = uniqid() . '.' . $image->guessExtension();
            try {
                $image->move('../public/uploads/', $filename);
                $bottle->setImage($filename);
            } catch (FileException $e) {
                return new JsonResponse(['error' => 'Erreur lors de l\'upload de l\'image'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        $this->em->persist($bottle);
        $this->em->flush();
        
        return new JsonResponse(['success' => 'Bouteille enregistrée'], 200);
    }


    #[Route('/api/delete-bottle/{id}', name: 'api_bottle_delete', methods: ['DELETE'])]
    public function delete_bottle(int $id): JsonResponse
    { 
        $bottle = $this->em->getRepository(Bottle::class)->find($id);

        if(!$bottle) return new JsonResponse(['error' => 'bouteille introuvable'], 400);

        $this->em->remove($bottle);
        $this->em->flush();

        return new JsonResponse(['error' => 'bouteille supprimée'], 200); 
    }

    #[Route('/api/stats', name: 'api_stats', methods: ['GET'])]
    public function stats(BottleRepository $bottleRepository, NoteRepository $noteRepo): JsonResponse
    {
        $bottles = $bottleRepository->findAll();
        $notes_count = $noteRepo->count();

        $colorStats = [];
        $regionStats = [];
        $appellationStats = [];
        $typeStats = [];
        $totalBottles = 0;
        $uniqueRegions = [];
        $uniqueNames = [];
        $topRated = [];
        $warnings = [];
        $recent = [];
        $countries = [
            "france" => [],
            "world" => []
        ];
        $date = (int)date('Y');
        $types3 = [
            "tranquille" => 0,
            "effervescent" => 0
        ];
        $oldest = [
            "name" => "",
            "year" => 0,
            "domaine" => ""
        ];

        foreach ($bottles as $bottle) {

            // Get the oldest wine
            if((int)$bottle->getYear() > $oldest['year']) {
                $oldest = [
                "name" => $bottle->getName(),
                "year" => $bottle->getYear(),
                "domaine" => $bottle-> getDomaine()
                ];
            }

            // Count france and world appellations
            $country = strtolower($bottle->getCountry());
            isset($countries[$country]) ? $countries[$country][] = $bottle->getName() : $countries['world'][] = $bottle->getName();

            // Type 3 : effervescence
            $types3[$bottle->getType3()] += 1;

            // Quantite
            $totalBottles += $bottle->getQuantity();

            // Couleur
            $color = $bottle->getType(); // ex: "Rouge", "Blanc"
            $normalizedCouleur = ucfirst(strtolower(trim($color)));
            $colorStats[$normalizedCouleur] = ($colorStats[$normalizedCouleur] ?? 0) + 1;

             // Région (normalisation)
            $region = $bottle->getRegion();
            $normalizedRegion = ucfirst(strtolower(trim($region)));
            $regionStats[$normalizedRegion] = ($regionStats[$normalizedRegion] ?? 0) + 1;
            // Enregistrement pour comptage unique
            $uniqueRegions[$normalizedRegion] = true;


             // Appellations (normalisation)
            $appellation = $bottle->getName();
            $normalizedName = ucfirst(strtolower(trim($appellation)));
            $appellationStats[$normalizedName] = ($appellationStats[$normalizedName] ?? 0) + 1;
            // Enregistrement pour comptage unique
            $uniqueNames[$normalizedName] = true;


            // Type (sec, doux, brut, moelleux...)
            $type = $bottle->getType2(); // exemple champ "type2"
            $typeStats[$type] = ($typeStats[$type] ?? 0) + 1;

            // Filtrer toutes les bouteilles avec une note
            $notedBottles = array_filter($bottles, fn($bottle) => $bottle->getNote() !== null);

            // Trier par note décroissante
            usort($notedBottles, fn($a, $b) => $b->getNote() <=> $a->getNote());

            // Si plus de 3 avec même note, prendre 3 au hasard
            $topCandidates = array_slice($notedBottles, 0, 10); // On limite à 10 pour tirer 3 random
            shuffle($topCandidates);
            $topRated = array_slice($topCandidates, 0, 3);

            // meilleurs vins notes
            $topRatedFormatted = array_map(function($bottle) {
                return [
                    'name' => $bottle->getName(),
                    'domaine' => $bottle->getDomaine(),
                    'year' =>  $bottle->getYear(),
                    'rating' => $bottle->getNote()
                ];
            }, $topRated);


            $age = $date - (int)$bottle->getYear();
            if($age >= 10){
                $warnings[] = [
                    'name' => $bottle->getName(),
                    'domaine' => $bottle->getDomaine(),
                    'year' => $bottle->getYear(),
                    'message' => "Déjà {$age} ans dans votre cave"
                ];
            }
            elseif($bottle->getQuantity() < 5){
                $warnings[] = [
                    'name' => $bottle->getName(),
                    'domaine' => $bottle->getDomaine(),
                    'year' => $bottle->getYear(),
                    'message' => "Stock faible"
                ];
            }
            elseif($bottle->getQuantity() == 0){
                $warnings[] = [
                    'name' => $bottle->getName(),
                    'domaine' => $bottle->getDomaine(),
                    'year' => $bottle->getYear(),
                    'message' => "Stock épuisé"
                ];
            }
        }

        // randomiser les warnings et conserver 3
        shuffle($warnings);
        $warningsRand = array_slice($warnings, 0, 3);

        // trier les régions  et conserver 5
        arsort($regionStats); 
        $topRegions = array_slice($regionStats, 0, 5, true);

        // Récupérer les 3 dernières bouteilles (tri par date de création DESC)
        $recentBottles = $bottleRepository->findBy([], ['creation_date' => 'DESC'], 3);

        if($recentBottles){
            $recent = array_map(function ($bottle) {
                return [
                    'name' => $bottle->getName(),
                    'domaine' => $bottle->getDomaine(),
                    'year' =>  $bottle->getYear(),
                    'createdAt' => $bottle->getCreationDate()?->format('d/m/Y'),
                ];
            }, $recentBottles);
        }


        // Recuperer les notes de degustation
        $recentTastings = $noteRepo->findBy([], ['creation_date' => 'DESC'], 3);
        $recentTastingsFormatted = array_map(function ($note) {
            // Suppose que getBottle() renvoie une entité Bottle avec getId() et getName()
            $bottle = $note->getBottle();
            return [
                'title' => $note->getTitre(),
                'creation_date' => $note->getCreationDate()?->format('d/m/Y'),
                'bottle' => [
                    'id' => $bottle ? $bottle->getId() : null,
                    'name' => $bottle ? $bottle->getName() : null,
                    'year' => $bottle ? $bottle->getYear() : null,
                    'domaine' => $bottle ? $bottle->getDomaine() : null,
                ],
            ];
        }, $recentTastings);

        $countries['france'] = count(array_unique($countries['france']));
        $countries['world'] = count(array_unique($countries['world']));

        return new JsonResponse([
            'colors' => $colorStats ?? [],
            'regions' => $topRegions ?? [],
            'names' => $appellationStats ?? [],
            'types' => $typeStats ?? [],
            'total_bottles' => $totalBottles,
            'region_count' => $uniqueRegions ? count($uniqueRegions) : 0,
            'name_count' => $uniqueNames ? count($uniqueNames) : 0,
            'notes_count' => $notes_count,
            'top_rated' => $topRatedFormatted ?? [],
            'recent' => $recent ?? [],
            'last_notes' => $recentTastingsFormatted ?? [],
            'warnings' => $warningsRand ?? [],
            'types3' => $types3,
            "oldest" => $oldest,
            "countries" => $countries
        ], 200);
    }

    #[Route('/api/bottles/stock', name: 'app_list_bottles_stock', methods:(['GET']))]
    public function list_stock(BottleRepository $bottleRepo): Response
    {
         $bottles = $bottleRepo->createQueryBuilder('b')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return new JsonResponse($bottles, 200);
    }

    #[Route('/api/bottle/{id}/stock', name: 'api_bottle_stock', methods: ['PATCH'])]
    public function bottle_stock(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $amount = $data['amount'] ?? 0;

        if (!is_numeric($amount))   return new JsonResponse(['error' => 'Invalid amount'], 400);

        $bottle = $this->em->getRepository(Bottle::class)->find($id);

        $newQty = max(0, $bottle->getQuantity() + (int)$amount);
        $bottle->setQuantity($newQty);
        $this->em->flush();

        return new JsonResponse(['success' => true], 200);
    }

    #[Route('/api/notes', name: 'api_notes', methods: ['GET'])]
    public function notes(NoteRepository $noteRep): JsonResponse
    {
        $notes = $noteRep->createQueryBuilder('n')
            ->leftJoin('n.bottle', 'b')    
            ->addSelect('b')  
            ->orderBy('n.creation_date', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return new JsonResponse($notes, 200);
    }

    #[Route('/api/note/{id}', name: 'api_note', methods: ['GET'])]
    public function note(int $id, NoteRepository $noteRep): JsonResponse
    {
        $note = $noteRep->createQueryBuilder('n')
            ->leftJoin('n.bottle', 'b')    
            ->addSelect('b')  
            ->andWhere('n.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();

        return new JsonResponse($note[0], 200);
    }

    #[Route('/api/note_create', name: 'api_note_create', methods: ['POST'])]
    public function create_note(Request $request): JsonResponse
    {
        $title = $request->request->get('title');
        $bottleId = $request->request->get('bottle');
        $content = $request->request->get('content');
        $rating = $request->request->get('rating');
        $imageFile = $request->files->get('image');
        
        if (!$title || !$bottleId)  return new JsonResponse(['error' => 'Champs manquants'], Response::HTTP_BAD_REQUEST);

        $bottle = $this->em->getRepository(Bottle::class)->find($bottleId);

        if (!$bottle) return new JsonResponse(['error' => 'Bouteille non trouvée'], Response::HTTP_NOT_FOUND);

        $note = new Note();
        $note->setTitre($title);
        $note->setBottle($bottle);
        $note->setContent($content);
        $note->setRating((int)$rating);
        $note->setCreationDate(new \DateTime());

        if ($imageFile) {
            $filename = uniqid() . '.' . $imageFile->guessExtension();
            try {
                $imageFile->move('../public/uploads/', $filename);
                $note->setImage($filename);
            } catch (FileException $e) {
                return new JsonResponse(['error' => 'Erreur lors de l\'upload de l\'image'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        $this->em->persist($note);
        $this->em->flush();

        return new JsonResponse(['message' => 'Note enregistrée avec succès'],201);
    }

    #[Route('/ping', name: 'ping')]
    public function ping(): Response { 
        return new Response('pong'); 
    }

    #[Route('/clear-cache', name: 'clear_cache', methods: ['POST'])]
    public function clearCache(Filesystem $fs, KernelInterface $kernel): JsonResponse
    {
        $cacheDir = $kernel->getCacheDir();
        $fs = new Filesystem();
        $fs->remove($cacheDir);

        return new JsonResponse(['status' => 'success', 'message' => 'Cache backend vidé']);
    }

    #[Route('/api/delete-note/{id}', name: 'api_delete_note', methods: ['DELETE'])]
    public function deleteNote(int $id, EntityManagerInterface $em): JsonResponse {
        $note = $em->getRepository(Note::class)->find($id);
        
        if (!$note)  return new JsonResponse(['error' => 'Note non trouvée'], 404);

        $em->remove($note);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}

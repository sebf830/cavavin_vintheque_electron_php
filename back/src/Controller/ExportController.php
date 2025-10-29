<?php 

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class ExportController extends AbstractController
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getSqlitePath(){
        $root = dirname(__DIR__, 3); 
        $exe = $root. '/sqlite/sqlite3.exe';
        return realpath($exe);
    }

    #[Route('/export-sqlite', name: 'export_sqlite', methods:['GET'])]
    public function exportSqlite() : JsonResponse|BinaryFileResponse
    {
         if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
            return new JsonResponse(["message" => "Cette fonctionalité n'est pas disponible sur windows"], 200);
        }

        $zip = new \ZipArchive();

        $zipFile = sys_get_temp_dir() . '/sauvegarde_cavavin.zip';
        $params = $this->connection->getParams();
        $sqliteFile = $params['path'];
        
        $uploadDir = $this->getParameter('app.root') . '/public/uploads';
        $images = scandir($uploadDir);
        
        try {
            if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            
                $dumpFilename = '/base_de_donnees_' . time() . '.sql';
                $dumpFilePath = sys_get_temp_dir() . $dumpFilename;
                $command = 'sqlite3 ' . escapeshellarg($sqliteFile) . ' .dump > ' . escapeshellarg($dumpFilePath);
                exec($command, $output, $returnVar);
                if ($returnVar !== 0 || !file_exists($dumpFilePath)) throw $this->createNotFoundException('Erreur lors de la création du dump SQLite');

                $zip->addFile($dumpFilePath, $dumpFilename);

                foreach($images as $image){
                    if(in_array($image, ['.', '..', 'DS-STORE', 'DS_STORE']))continue;

                    $imagePath = $uploadDir . '/' . $image;
                    $zip->addFile($imagePath, $image);
                }
            }
            $zip->close();
            unlink($dumpFilePath);
        } catch (\Throwable $e) {

            if (isset($zip)) @$zip->close();
            if(isset($dumpFilePath)) @unlink($dumpFilePath);
            return new JsonResponse(["error" => "Erreur interne lors de l'export"], 500);
        }

        try {
            $response =  new BinaryFileResponse($zipFile, 200, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => (new ResponseHeaderBag)->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,'sauvegarde_cavavin.zip')
            ]);
            return $response;
            $response->deleteFileAfterSend(true);
        }
        catch (\Throwable $e) {
            return new JsonResponse(["error" => "Erreur interne lors de l'export : {$e->getMessage()}"], 500);
        } 
    }

    #[Route('/api/import-sqlite', name: 'import_sqlite', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file || $file->getClientOriginalExtension() !== 'sqlite') return new JsonResponse(['error' => 'Fichier invalide'], 400);

        $projectDir = $this->getParameter('app.root');
        $destinationPath = $projectDir . '/var/data/database.sqlite';

        try {
            $file->move(dirname($destinationPath), basename($destinationPath));
        } catch (FileException $e) {
            return new JsonResponse(['error' => "Échec de lors de l'import : '{$e->getMessage()}"], 500);
        }

        return new JsonResponse(['success' => true], 200);
    }
}

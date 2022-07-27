<?php

namespace App\Controller;

use App\Service\BackupService;
use App\Util\SystemUtil;
use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BackupController extends AbstractController
{


    /**
     * @Route("/database/backup", name="database_backup")
     * @param ParameterBagInterface $parameterBag
     * @return Response
     */
    public function backup(ParameterBagInterface $parameterBag): Response
    {

        $model['files'] = SystemUtil::getFiles($parameterBag->get('app.database.backupdir'));

        //breadcumb
        $model['entity'] = 'controller.database.backup.entity';
        $model['page'] = 'controller.database.backup.page';

        return $this->render('database/backup.html.twig', $model);
    }

    /**
     * @Route("/database/add", name="database_add",methods={"POST"})
     * @param ParameterBagInterface $parameterBag
     * @param BackupService $backupService
     * @return Response
     */
    public function add(ParameterBagInterface $parameterBag,BackupService $backupService): Response
    {
        $dir = $parameterBag->get('app.database.backupdir');
        $files = array_map(static function(SplFileInfo $file){
            return $file->getFilename();
        },SystemUtil::getFiles($dir));
        SystemUtil::removeFiles($files,$dir);

        $backupService->save();
        return $this->redirectToRoute('database_backup');
    }

    /**
     * @Route("/database/remove", name="database_remove")
     * @param ParameterBagInterface $parameterBag
     * @param Request $request
     * @return Response
     */
    public function remove(ParameterBagInterface $parameterBag, Request $request): Response
    {
        $dir = $parameterBag->get('app.database.backupdir');
        SystemUtil::removeFiles([$request->get('name')],$dir);

        return $this->redirectToRoute('database_backup');
    }

    /**
     * @Route("/database/download", name="database_download")
     * @param ParameterBagInterface $parameterBag
     * @param Request $request
     * @return Response
     */
    public function download(ParameterBagInterface $parameterBag, Request $request): Response
    {
        $dir = $parameterBag->get('app.database.backupdir');
        return SystemUtil::downloadZip([$request->get('name')],
            $dir,$request->get('name'));

    }

}

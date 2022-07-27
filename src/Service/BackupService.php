<?php


namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class BackupService
{
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;
    /**
     * @var FlashBagInterface
     */
    private $flashBag;

    public function __construct(ParameterBagInterface $parameterBag, FlashBagInterface $flashBag)
    {

        $this->parameterBag = $parameterBag;
        $this->flashBag = $flashBag;
    }

    /**
     * @return void
     */
    public function save(): void
    {
        $mysqlExportPath =$this->parameterBag->get('app.database.backupdir').'backup-'.time().'.sql';
        $mysqlDatabaseName = $this->parameterBag->get('app.database.db');
        $mysqlHostName = $this->parameterBag->get('app.database.host');
        $mysqlUserName = $this->parameterBag->get('app.database.username');
        $mysqlPassword = $this->parameterBag->get('app.database.password');
        $mysqlPort = $this->parameterBag->get('app.database.port');

        //Please do not change the following points
        //Export of the database and output of the status
        $command='mysqldump --opt -h ' .$mysqlHostName .' -u ' .$mysqlUserName .' -P ' .$mysqlPort .' --password=' .$mysqlPassword .' ' .$mysqlDatabaseName .' > ' .$mysqlExportPath;
        exec($command,$output,$worked);
        switch($worked){
            case 0:
                $this->flashBag ->add('success', 'controller.database.backup.flash.success');
                break;
            case 1:
                $this->flashBag ->add('danger', 'controller.database.backup.flash.danger1');
                break;
            case 2:
                $this->flashBag ->add('danger', 'controller.database.backup.flash.danger2');
                break;
        }
    }

    /**
     * @return void
     */
    public function restore(): void
    {
        $mysqlExportPath =$this->parameterBag->get('app.database.backupdir').'backup-'.time().'.sql';
        $mysqlDatabaseName = $this->parameterBag->get('app.database.db');
        $mysqlHostName = $this->parameterBag->get('app.database.host');
        $mysqlUserName = $this->parameterBag->get('app.database.username');
        $mysqlPassword = $this->parameterBag->get('app.database.password');
        $mysqlPort = $this->parameterBag->get('app.database.port');

        //Please do not change the following points
        //Import of the database and output of the status
        $command='mysqldump --opt -h ' .$mysqlHostName .' -u ' .$mysqlUserName .' -P ' .$mysqlPort .' --password=' .$mysqlPassword .' ' .$mysqlDatabaseName .' < ' .$mysqlExportPath;
        exec($command,$output,$worked);
        switch($worked){
            case 0:
                $this->flashBag ->add('success', 'controller.database.backup.flash.success');
                break;
            case 1:
                $this->flashBag ->add('danger', 'controller.database.backup.flash.danger1');
                break;
            case 2:
                $this->flashBag ->add('danger', 'controller.database.backup.flash.danger2');
                break;
        }
    }
}


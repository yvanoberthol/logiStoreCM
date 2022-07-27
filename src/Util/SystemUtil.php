<?php


namespace App\Util;


use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;
use ZipArchive;

final class SystemUtil
{
    private const BASE_DIR = '/';

    public static function createFolder($name): void {

        $dir = self::BASE_DIR;

        $finder = new Finder();
        $iterator = $finder->in($dir)->directories()->getIterator();
        $directories = [];
        foreach ($iterator as $item){
            $directories[] = $item->getFilename();
        }
        if (!in_array($name,$directories,true)) {
            $fileSystem = new Filesystem();
            $fileSystem->mkdir($dir.$name);
        }

    }

    public static function renameFile($old,$new,$dir): void {

        $finder = new Finder();
        $iterator = $finder->in($dir)->files()->getIterator();
        $files = [];
        foreach ($iterator as $item){
            $files[] = $item->getFilename();
        }
        if (in_array($old,$files,true)) {
            $fileSystem = new Filesystem();
            $fileSystem->rename($dir.$old,$dir.$new);
        }

    }

    public static function moveFile($fileName,$dir,$destination): void {

        $finder = new Finder();
        $iterator = $finder->in($dir)->files()->getIterator();
        $files = [];
        foreach ($iterator as $item){
            $files[] = $item->getFilename();
        }

        if (in_array($fileName,$files,true)) {
            $fileSystem = new Filesystem();
            $fileSystem->copy($dir.$fileName,$destination.$fileName,true);
            $fileSystem->remove($dir.$fileName);
        }

    }

    public static function renameAndMoveFile($old,$new,$dir,$destination): void {

        $finder = new Finder();
        $iterator = $finder->in($dir)->files()->getIterator();
        $files = [];
        foreach ($iterator as $item){
            $files[] = $item->getFilename();
        }

        if (in_array($old,$files,true)) {
            $fileSystem = new Filesystem();
            $fileSystem->rename($dir.$old,$dir.$new);
            $fileSystem->copy($dir.$new,$destination.$new,true);
            $fileSystem->remove($dir.$new);
        }

    }

    public static function getFiles($folder): array {

        $finder = new Finder();
        $iterator = $finder->in($folder)->files()->getIterator();
        $files = [];
        foreach ($iterator as $item){
            $files[] = $item;
        }

        return $files;
    }

    public static function formatBytes($size, $precision = 2): string
    {
        $base = log($size, 1024);
        $suffixes = array('', 'Kb', 'Mb', 'Gb', 'Tb');
        return round(1024 ** ($base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

    public static function countFiles($folder): array {
        return count(self::getFiles($folder));
    }

    public static function uploadFiles($files, $uploadDirectory, SluggerInterface $slugger): void
    {
        foreach ($files as $file){
            $fileName = $slugger->slug($file->getClientOriginalName());
            $saveFileName = $fileName.'-'.uniqid("",false).'.'.$file->guessExtension();

            try{
                $file->move($uploadDirectory,$saveFileName);
            }catch (FileException $e){

            }
        }
    }

    public static function compressZip($files,$dir,$fileName){
        // The name of the Zip documents.
        $zipName = $fileName.'.zip';

        return self::convertToZip($zipName,$files,$dir);
    }

    public static function downloadZip($files,$dir,$fileName): Response
    {
        $zipName = $fileName.'.zip';
        $file = self::compressZip($files,$dir,$fileName);
        $response = new Response($file);
        $size = filesize($zipName);
        @unlink($zipName);
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
        $response->headers->set('Content-length', $size);
        return $response;
    }

    private static function convertToZip($zipName,$files,$dir){
        // Create new Zip Archive.
        $zip = new ZipArchive();

        $zip->open($zipName,  ZipArchive::CREATE);
        foreach ($files as $file) {
            $zip->addFromString($file, file_get_contents($dir.$file));
        }
        $zip->close();
        return file_get_contents($zipName);
    }

    public static function extractZip($zipFile,$dir){
        $zip = new ZipArchive();
        $res = $zip->open($zipFile);
        if ($res === true) {
            $zip->extractTo($dir);
            $zip->close();
        }
    }

    public static function verifyExistingFile($dir,$files): void
    {
        foreach ($files as $iValue) {
            $fileSystem = new Filesystem();
            $fileSystem->exists($dir.'/'. $iValue);
        }

    }

    public static function removeFiles($files,$dir): void{
        $fileSystem = new Filesystem();
        foreach ($files as $file){
            $file = $dir.'/'.$file;
            $fileSystem->remove($file);
        }
    }

    public static function cleanDirectory($dir): void{
        $files = array_map(static function($file){
            return $file->getFileName();
        },self::getFiles($dir));

        self::removeFiles($files,$dir);
    }
}

<?php

namespace App\Command\Robots;

use ZipArchive;
use App\Entity\Disk;
use App\Entity\Config;
use App\Entity\Volume;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Mediashare\CloudFile\CloudFile;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Backup
 */
Class Backup {
    public $em;
    public $io;
    private $backup_host;
    private $backup_apikey;

    public function run() {
        $config = $this->getBackupConfig();
        if ($config):
            // Backup Database
            $database = $this->backupDatabase();
            if ($database):
                // Backup Disks
                $disks = $this->backupDisks();
                // Remove old backups for free space
                $this->removeOldBackups();
            endif;
        endif;
    }

    private function getBackupConfig() {
        $configs = $this->em->getRepository(Config::class)->findAll();
        foreach ($configs as $config)
            if ($config->getBackupHost()):
                $this->backup_host = $config->getBackupHost();
                $this->backup_apikey = $config->getBackupApikey();
                return $config;
            endif;
        return false;
    }

    private function removeOldBackups() {
        $cloudfile = new CloudFile($this->backup_host, $this->backup_apikey);
        $status = $cloudfile->stats();
        if ($status['status'] !== 'success'): 
            return false; 
        endif;

        // Get all backups
        $results = $cloudfile->file()->list();
        $backups = $results['files']['results'];
        while (count($results['files']['results']) === 100) {
            $results = $cloudfile->file()->list($results['files']['page'] + 1);
            $backups = array_merge($backups, $results['files']['results']);
        }
        // Check date for deletion
        $date = new \DateTime();
        foreach ($backups as $backup):
            $backup_date = new \DateTime($backup['createDate']['date']);
            if ($date->diff($backup_date)->days > 0):
                $remove = $cloudfile->file()->remove($backup['id']);
                $this->io->writeln('<comment>Old Backup has been deleted</comment>');
            endif;
        endforeach;
        $this->io->writeln('<info>Old Backup has been checked</info>');
    }

    private function backupDatabase() {
        $database = $this->container->getParameter('kernel_dir').'/var/data.db';
        if ($this->checksum($database)):
            $upload = $this->upload($database);
            if ($upload):
                $this->io->writeln('<info>Database has been uploaded</info>');
                return true;
            endif;
        endif;
        return false;
    }

    private function backupDisks() {        
        foreach ($this->em->getRepository(Disk::class)->findAll() as $disk):

            foreach ($disk->getFiles() as $file):
                if (empty($volumes[$file->getVolume()->getId()])):
                    $volumes[$file->getVolume()->getId()] = $file->getVolume();
                endif;
            endforeach;

            foreach ($volumes as $volume):
                $zipPath = $this->createZip($disk, $volume);
                if ($zipPath && $this->checksum($zipPath)):
                    $upload = $this->upload($zipPath);
                    if ($upload):
                        $this->io->writeln('<info>Disk '.$disk->getName().' - Volume '.$volume->getName().' has been uploaded</info>');
                        return true;
                    endif;
                    \unlink($zipPath);
                endif;
            endforeach;
        endforeach;
        return false;
    }

    private function checksum(string $file) {
        $cloudfile = new CloudFile($this->backup_host, $this->backup_apikey);
        $status = $cloudfile->stats();
        if ($status['status'] !== 'success'): 
            return false; 
        endif;
        $checksum = $cloudfile->file()->search('checksum='.\md5_file($file));
        if (isset($checksum['files']) && $checksum['files']['counter'] > 0): return false;
        else: return true; endif;
    }

    private function upload(string $file) {
        $cloudfile = new CloudFile($this->backup_host, $this->backup_apikey);
        $status = $cloudfile->stats();
        if ($status['status'] !== 'success'): return false; endif;
        $upload = $cloudfile->file()->upload($file);
        if ($upload['status'] !== 'success'): return false; endif;
        return $upload;
    }

    private function createZip(Disk $disk, Volume $volume) {
        $path = rtrim($disk->getPath(), '/').'/'.$volume->getId();
        if (count(scandir($path.'/')) > 2):
            // Initialize archive object
            $zip = new ZipArchive();
            $zip->open($zipPath = $this->container->getParameter('kernel_dir').'/var/'.$disk->getName().' - '.$volume->getName().' backup.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

            // Create recursive directory iterator
            /** @var SplFileInfo[] $files */
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $name => $file) {
                // Skip directories (they would be added automatically)
                if (!$file->isDir()) {
                    // Get real and relative path for current file
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($path) + 1);
                    // Add current file to archive
                    $zip->addFile($filePath, $relativePath);
                }
            }
            // Zip archive will be created only after closing object
            $zip->close();
            return $zipPath;
        else: return false; endif;
    }
}

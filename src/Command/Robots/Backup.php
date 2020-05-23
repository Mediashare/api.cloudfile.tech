<?php

namespace App\Command\Robots;

use ZipArchive;
use App\Entity\Disk;
use App\Entity\Config;
use Mediashare\PingIt\PingIt;
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
    public $pingIt;
    private $backup_host;
    private $backup_apikey;

    public function run() {
        $this->pingIt = new PingIt($this->container->getParameter('pingit_backup'));
        $config = $this->getBackupConfig();
        if ($config):
            $this->pingIt->send('[BackUp] The backup is started!', 'The backup system has been started.', 'feather icon-radio', 'primary');
            // Remove old backups
            $this->removeOldBackups();
            // Backup Database
            $this->backupDatabase();
            // Backup Disks
            $this->backupDisks();
            $this->pingIt->send('[BackUp] The backup is ready!', 'The backup system has been finished.', 'feather icon-save', 'success');
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
            $this->pingIt->send('[BackUp] CloudFile API server is down!', null, 'feather icon-radio', 'danger');
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
                $this->pingIt->send('[BackUp] Database has been uploaded', null, 'feather icon-upload', 'primary');
            endif;
        endif;
    }

    private function backupDisks() {        
        foreach ($this->em->getRepository(Disk::class)->findAll() as $disk):
            $zipPath = $this->createZip($disk);
            if ($zipPath && $this->checksum($zipPath)):
                $upload = $this->upload($zipPath);
                if ($upload):
                    $this->io->writeln('<info>Disk '.$disk->getName().' has been uploaded</info>');
                    $this->pingIt->send('[BackUp] Disk '.$disk->getName().' has been uploaded', null, 'feather icon-upload', 'primary');
                endif;
                \unlink($zipPath);
            endif;
        endforeach;
        
    }

    private function checksum(string $file) {
        $cloudfile = new CloudFile($this->backup_host, $this->backup_apikey);
        $status = $cloudfile->stats();
        if ($status['status'] !== 'success'): 
            $this->pingIt->send('[BackUp] CloudFile API server is down!', null, 'feather icon-radio', 'danger');
            return false; 
        endif;
        $checksum = $cloudfile->file()->search('checksum='.\md5_file($file));
        if ($checksum['files']['counter'] > 0): return false;
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

    private function createZip(Disk $disk) {
        if (count(scandir(rtrim($disk->getPath(), '/').'/')) > 2):
            // Initialize archive object
            $zip = new ZipArchive();
            $zip->open($zipPath = $this->container->getParameter('kernel_dir').'/var/'.$disk->getName().' - backup.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

            // Create recursive directory iterator
            /** @var SplFileInfo[] $files */
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($disk->getPath()),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $name => $file) {
                // Skip directories (they would be added automatically)
                if (!$file->isDir()) {
                    // Get real and relative path for current file
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($disk->getPath()) + 1);
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

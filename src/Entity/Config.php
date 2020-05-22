<?php

namespace App\Entity;

use App\Repository\ConfigRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConfigRepository::class)
 */
class Config
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     */
    private $cloudfile_password;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     */
    private $backup_host;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $backup_apikey;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCloudfilePassword(): ?string
    {
        return $this->cloudfile_password;
    }

    public function setCloudfilePassword(?string $cloudfile_password): self
    {
        $this->cloudfile_password = $cloudfile_password;

        return $this;
    }

    public function getBackupHost(): ?string
    {
        return $this->backup_host;
    }

    public function setBackupHost(?string $backup_host): self
    {
        $this->backup_host = $backup_host;

        return $this;
    }

    public function getBackupApikey(): ?string
    {
        return $this->backup_apikey;
    }

    public function setBackupApikey(?string $backup_apikey): self
    {
        $this->backup_apikey = $backup_apikey;

        return $this;
    }
}

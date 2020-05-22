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
}

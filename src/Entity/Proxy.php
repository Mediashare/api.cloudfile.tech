<?php

namespace App\Entity;

use App\Entity\File;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProxyRepository")
 */
class Proxy
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="string")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\File")
     * @ORM\JoinColumn(nullable=false)
     */
    private $file;

    /**
     * @ORM\Column(type="integer")
     */
    private $displayed;

    public function __construct(File $file) {
        $this->setId(\uniqid());
        $this->setFile($file);
        $this->setDisplayed(0);
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self {
        $this->id = $id;
        return $this;
    }

    public function getDisplayed(): ?int
    {
        return $this->displayed;
    }

    public function setDisplayed(int $displayed): self
    {
        $this->displayed = $displayed;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Entity\Volume;
use App\Service\FileSystemApi;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\DiskRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass=DiskRepository::class)
 */
class Disk
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="string")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=1000)
     */
    private $path;

    public function __toString(): string {
        return $this->getName();
    }

    public function __construct() {
        $this->setId(\uniqid());
        $this->volumes = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self {
        $this->id = $id;
        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|Volume[]
     */
    public function getVolumes(): Collection
    {
        return $this->volumes;
    }

    public function addVolume(Volume $volume): self
    {
        if (!$this->volumes->contains($volume)) {
            $this->volumes[] = $volume;
            $volume->setDisk($this);
        }

        return $this;
    }

    public function removeVolume(Volume $volume): self
    {
        if ($this->volumes->contains($volume)) {
            $this->volumes->removeElement($volume);
            // set the owning side to null (unless already changed)
            if ($volume->getDisk() === $this) {
                $volume->setDisk(null);
            }
        }

        return $this;
    }

    public function getInfo(): array {
        $fileSystem = new FileSystemApi();
        $free_space = \disk_free_space($this->getPath());
        $total_space = \disk_total_space($this->getPath());
        $used_space = $total_space - $free_space;
        return [
            'name' => $this->getName(),
            'size' => [
                'used_pct' => number_format($used_space * 100 / $total_space, 1),
                'used' => $fileSystem->getSizeReadable($used_space),
                'total' => $fileSystem->getSizeReadable($total_space),
                'free' => $fileSystem->getSizeReadable($free_space),
            ],
        ];
    }
}

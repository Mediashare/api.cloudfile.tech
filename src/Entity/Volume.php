<?php

namespace App\Entity;

use App\Entity\Disk;
use App\Entity\File;
use App\Service\FileSystemApi;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VolumeRepository")
 */
class Volume
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
     * @ORM\Column(type="string", length=255)
     */
    private $apikey;

    /**
     * @ORM\Column(type="integer")
     */
    private $size;

    /**
     * @ORM\Column(type="boolean")
     */
    private $private;
    
    /**
     * @ORM\Column(type="datetime")
     */
    private $createDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updateDate;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\File", mappedBy="volume", cascade={"remove", "persist"})
     */
    private $files;

    public function __toString() {
        return $this->getId();
    }

    public function __construct() {
        $this->files = new ArrayCollection();
        $this->setId(\uniqid());
        $this->generateApiKey();
        $this->setPrivate(true);
        $this->setCreateDate(new \DateTime());
        $this->setUpdateDate(new \DateTime());

    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }   

    public function generateApiKey(): self {
        $this->setApikey($this->rngString(32));
        return $this;
    }

    public function getApikey(): ?string
    {
        return $this->apikey;
    }

    public function setApikey(string $apikey): self
    {
        $this->apikey = $apikey;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getPrivate(): ?bool
    {
        return $this->private;
    }

    public function setPrivate(bool $private): self
    {
        $this->private = $private;

        return $this;
    }

    public function getCreateDate(): ?\DateTime
    {
        return $this->createDate;
    }

    public function setCreateDate(\DateTime $createDate): self
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getUpdateDate(): ?\DateTime
    {
        return $this->updateDate;
    }

    public function setUpdateDate(\DateTime $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * @return Collection|File[]
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(File $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files[] = $file;
            $file->setVolume($this);
        }

        return $this;
    }

    public function removeFile(File $file): self
    {
        if ($this->files->contains($file)) {
            $this->files->removeElement($file);
            // set the owning side to null (unless already changed)
            if ($file->getVolume() === $this) {
                $file->setVolume(null);
            }
        }

        return $this;
    }

    public function getInfo(?bool $all_data = true): array {
        $info = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'updateDate' => $this->getUpdateDate(),
            'createDate' => $this->getCreateDate(),
        ];

        if ($all_data):
            $info['size'] = $this->getSize();
            $info['private'] = $this->getPrivate();
            $info['apikey'] = $this->getApikey();
            
            // Stats
            $info['stats']['files'] = count($this->getFiles());
            $size = 0;
            foreach ($this->getFiles() as $file):
                $size += $file->getSize();
            endforeach;

            $fileSystem = new FileSystemApi();
            if ($this->getSize() > 0):
                $total_space = $fileSystem->human2byte($this->getSize().'G');
                $free_space = $total_space - $size;
                $info['stats']['stockage'] = [
                    'used_pct' => number_format($size * 100 / $total_space, 1),
                    'used' => $fileSystem->getSizeReadable($size),
                    'total' => $fileSystem->getSizeReadable($total_space),
                    'free' => $fileSystem->getSizeReadable($free_space),
                ];
            endif;
        endif;

        return $info;
    }

    private function rngString($length = 32) {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }
}

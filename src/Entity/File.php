<?php

namespace App\Entity;

use App\Service\FileSystemApi;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FileRepository")
 */
class File
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="string")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\Column(type="string")
     */
    private $stockage;

    /**
     * @ORM\Column(type="string")
     */
    private $path;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $size;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mimeType;

    /**
     * @ORM\Column(type="array")
     */
    private $metadata = [];

    /**
     * @ORM\Column(type="datetime")
     */
    private $createDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $apiKey;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $private;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="files")
     */
    private $user;

    public function __construct() {
        $this->setId(\uniqid());
        $this->setPrivate(false);
        $this->setCreateDate(new \DateTime());
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

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

    public function getStockage(): ?string
    {
        return $this->stockage;
    }

    public function setStockage(string $stockage): self
    {
        $this->stockage = $stockage;

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

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

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
    
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): self
    {
        $this->apiKey = $apiKey;
        if ($this->apiKey):$this->setPrivate(true);endif;
        return $this;
    }

    public function getPrivate(): ?bool
    {
        if ($this->private):
            return true;
        else:
            return false;
        endif;
    }

    public function setPrivate(?bool $private): self
    {
        $this->private = $private;

        return $this;
    }

    public function getInfo(): array {
        $fileSystem = new FileSystemApi();
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'mimeType' => $this->getMimeType(),
            'size' => $fileSystem->getSizeReadable($this->getSize()),
            'metadata' => $this->getMetadata(),
            'private' => $this->getPrivate(),
            'createDate' => $this->getCreateDate(),
            'urls' => [
                'info' => '/api/info/'.$this->getId(),
                'show' => '/api/show/'.$this->getId(),
                'download' => '/api/download/'.$this->getId(),
                'remove' => '/api/remove/'.$this->getId(),
            ],
        ];
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}

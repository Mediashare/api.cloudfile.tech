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
     * @ORM\Column(type="integer", nullable=true)
     */
    private $size;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mimeType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $checksum;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\Volume", inversedBy="files")
     */
    private $volume;

    /**
     * @ORM\ManyToOne(targetEntity=Disk::class, inversedBy="files")
     * @ORM\JoinColumn(nullable=false)
     */
    private $disk;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $filename;

    public function __toString() {
        return $this->getName();
    }

    public function __construct() {
        $this->setId(\uniqid());
        $this->setPrivate(true);
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

    public function getChecksum(): ?string {
        if (!$this->checksum):
            $this->setChecksum();
        endif;
        return $this->checksum;
    }

    public function setChecksum(?string $checksum = null): self {
        if ($checksum):
            $this->checksum = $checksum;
        else:
            $this->checksum = md5_file($this->getPath());
        endif;

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

    public function setApiKey(?string $apiKey = null): self
    {
        $this->apiKey = $apiKey;
        
        return $this;
    }

    public function getPrivate(): ?bool
    {
        return $this->private;
    }

    public function setPrivate(?bool $private): self
    {
        $this->private = $private;

        return $this;
    }

    public function getInfo(?bool $all_data = true): array {
        $fileSystem = new FileSystemApi();
        $info = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'size' => $fileSystem->getSizeReadable($this->getSize()),
            'createDate' => (array) $this->getCreateDate(),
        ];
        
        if ($all_data):
            if (isset($_SERVER['SYMFONY_DEFAULT_ROUTE_URL'])):
                $host = trim($_SERVER['SYMFONY_DEFAULT_ROUTE_URL'], '/');
            else:
                if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'): $http = 'https://';
                else: $http = 'http://'; endif;
                $host = $http.trim($_SERVER['HTTP_HOST'], '/');
            endif;
            $info['urls'] = [
                'info' => $host.'/info/'.$this->getId(),
                'show' => $host.'/show/'.$this->getId(),
                'render' => $host.'/render/'.$this->getId(),
                'download' => $host.'/download/'.$this->getId(),
                'remove' => $host.'/remove/'.$this->getId(),
            ];
            $info['mimeType'] = $this->getMimeType();
            $info['checksum'] = $this->getChecksum();
            $info['metadata'] = $this->getMetadata(); 
            $info['private'] = $this->getPrivate();
        endif;

        return $info;
    }

    public function getVolume(): ?Volume
    {
        return $this->volume;
    }

    public function setVolume(?Volume $volume): self
    {
        $this->volume = $volume;

        return $this;
    }

    public function getDisk(): ?Disk
    {
        return $this->disk;
    }

    public function setDisk(?Disk $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getPath(): ?string
    {
        return \rtrim($this->getDisk()->getPath(), '/').'/'.$this->getVolume()->getId().'/'.$this->getId().'/'.$this->getFilename();
    }
}

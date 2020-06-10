<?php

namespace App\Entity;

use App\Service\FileSystemApi;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    private $apikey;

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
     * @ORM\JoinColumn(nullable=true)
     */
    private $disk;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $filename;

    /**
     * @ORM\OneToMany(targetEntity=Search::class, mappedBy="file", cascade={"persist"})
     */
    private $searches;

    public function __toString() {
        return $this->getName();
    }

    public function __construct() {
        $this->setId(\uniqid());
        $this->setPrivate(true);
        $this->setCreateDate(new \DateTime());
        $this->searches = new ArrayCollection();
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
    
    public function generateApiKey(): self {
        $this->setApikey($this->rngString(32));
        return $this;
    }

    public function getApikey(): ?string
    {
        return $this->apikey;
    }

    public function setApikey(?string $apikey = null): self
    {
        $this->apikey = $apikey;
        
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

    public function getInfo(?bool $all_data = true): array {
        $fileSystem = new FileSystemApi();
        $info = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'size' => $fileSystem->getSizeReadable($this->getSize()),
            'createDate' => (array) $this->getCreateDate(),
        ];
        
        if ($all_data):
            $info['mimeType'] = $this->getMimeType();
            $info['checksum'] = $this->getChecksum();
            $info['metadata'] = $this->getMetadata(); 
            $info['private'] = $this->getPrivate();

            // Urls
            if (isset($_SERVER['HTTP_HOST']) || isset($_SERVER['SYMFONY_DEFAULT_ROUTE_URL'])):
                if (isset($_SERVER['SYMFONY_DEFAULT_ROUTE_URL'])):
                    $host = trim($_SERVER['SYMFONY_DEFAULT_ROUTE_URL'], '/');
                else:
                    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'): $http = 'https://';
                    else: $http = 'http://'; endif;
                    $host = $http.trim($_SERVER['HTTP_HOST'], '/');
                endif;
                if ($this->getPrivate()): 
                    $info['apikey'] = $this->getApikey();
                    $apikey = '?apikey='.$this->getApikey();
                else: $apikey = null; endif;
                $info['urls'] = [
                    'info' => $host.'/info/'.$this->getId().$apikey,
                    'show' => $host.'/show/'.$this->getId().$apikey,
                    'render' => $host.'/render/'.$this->getId().$apikey,
                    'download' => $host.'/download/'.$this->getId().$apikey,
                    'remove' => $host.'/remove/'.$this->getId(),
                ];
            endif;
        endif;

        return $info;
    }

    private function rngString($length = 32) {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }

    /**
     * @return Collection|Search[]
     */
    public function getSearches(): Collection
    {
        return $this->searches;
    }

    public function addSearch(Search $search): self
    {
        if (!$this->searches->contains($search)) {
            $this->searches[] = $search;
            $search->setFile($this);
        }

        return $this;
    }

    public function removeSearch(Search $search): self
    {
        if ($this->searches->contains($search)) {
            $this->searches->removeElement($search);
            // set the owning side to null (unless already changed)
            if ($search->getFile() === $this) {
                $search->setFile(null);
            }
        }

        return $this;
    }
}

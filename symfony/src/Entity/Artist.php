<?php

namespace App\Entity;

use App\Entity\Sonata\SonataMediaMedia;
use App\Repository\ArtistRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE', region: 'member')]
class Artist implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 190)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $genre = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $hardware = null;

    #[ORM\OneToMany(targetEntity: EventArtistInfo::class, mappedBy: 'Artist', cascade: ['persist', 'detach'])]
    #[ORM\JoinColumn(nullable:true, onDelete:"CASCADE")]
    private $eventArtistInfos;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Member::class, inversedBy: 'artist')]
    private ?\App\Entity\Member $member = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bioEn = null;

    #[ORM\Column(type: 'array', nullable: true)]
    private ?array $links = [];

    #[ORM\ManyToOne(targetEntity: SonataMediaMedia::class, cascade: ['persist', 'detach'])]
    private ?\App\Entity\Sonata\SonataMediaMedia $Picture = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $copyForArchive = false;

    public function __construct()
    {
        $this->eventArtistInfos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): self
    {
        $this->genre = $genre;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio;

        return $this;
    }

    public function getHardware(): ?string
    {
        return $this->hardware;
    }

    public function setHardware(?string $hardware): self
    {
        $this->hardware = $hardware;

        return $this;
    }

    /**
     * @return Collection|EventArtistInfo[]
     */
    public function getEventArtistInfos(): Collection
    {
        return $this->eventArtistInfos;
    }

    public function addEventArtistInfo(EventArtistInfo $eventArtistInfo): self
    {
        if (!$this->eventArtistInfos->contains($eventArtistInfo)) {
            $this->eventArtistInfos[] = $eventArtistInfo;
            $eventArtistInfo->setArtist($this);
        }

        return $this;
    }

    public function removeEventArtistInfo(EventArtistInfo $eventArtistInfo): self
    {
        if ($this->eventArtistInfos->contains($eventArtistInfo)) {
            $this->eventArtistInfos->removeElement($eventArtistInfo);
            // set the owning side to null (unless already changed)
            if ($eventArtistInfo->getArtist() === $this) {
                $eventArtistInfo->setArtist(null);
            }
        }

        return $this;
    }

    public function clearEventArtistInfos(): void
    {
        $this->eventArtistInfos->clear();
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getMember(): ?Member
    {
        return $this->member;
    }

    public function setMember(?Member $member): self
    {
        $this->member = $member;
        if (!is_null($member)) {
            $member->addArtist($this);
        }
        return $this;
    }

    public function getBioEn(): ?string
    {
        return $this->bioEn;
    }

    public function setBioEn(?string $bioEn): self
    {
        $this->bioEn = $bioEn;

        return $this;
    }

    public function getLinks(): ?array
    {
        return $this->links;
    }

    public function setLinks(?array $links): self
    {
        $this->links = $links;

        return $this;
    }

    public function getPicture(): ?SonataMediaMedia
    {
        return $this->Picture;
    }

    public function setPicture(?SonataMediaMedia $Picture): self
    {
        $this->Picture = $Picture;

        return $this;
    }

    public function getCopyForArchive(): ?bool
    {
        return $this->copyForArchive;
    }

    public function setCopyForArchive(?bool $copyForArchive): self
    {
        $this->copyForArchive = $copyForArchive;

        return $this;
    }
    public function getLinkUrls(): ?string
    {
        $ret = '';
        foreach ($this->links as $link) {
            $ret .= '<a href="'.$link['url'].'">'.$link['title'].'</a>';
            if (end($this->links) !== $link) {
                $ret .= ' | ';
            }
        }
        return $ret;
    }
}

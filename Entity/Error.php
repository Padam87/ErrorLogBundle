<?php

namespace Padam87\ErrorLogBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Padam87\ErrorLogBundle\Entity\Emeddables\Exception;

/**
 * @ORM\Entity()
 */
class Error
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     */
    private ?int $id = null;

    /**
     * A unique hash based on "method", "route", "message", "file" and "line".
     * Necessary to avoid "1071 Specified key was too long" errors.
     * Should not be used for anything else.
     *
     * @ORM\Column(unique=true)
     */
    private ?string $uniqueHash = null;

    /**
     * @ORM\Column()
     */
    private ?string $level = null;

    /**
     * @ORM\Column()
     */
    private ?string $method = null;

    /**
     * @ORM\Column()
     */
    private ?string $route = null;

    /**
     * @ORM\Embedded(class="Padam87\ErrorLogBundle\Entity\Emeddables\Exception")
     */
    private ?Exception $exception = null;

    /**
     * @var Occurrence[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Padam87\ErrorLogBundle\Entity\Occurrence", mappedBy="error", cascade={"remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"loggedAt" = "DESC"})
     */
    private ?Collection $occurrences = null;

    /**
     * @ORM\OneToOne(targetEntity="Padam87\ErrorLogBundle\Entity\Occurrence", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private ?Occurrence $fistOccurrence = null;

    /**
     * @ORM\OneToOne(targetEntity="Padam87\ErrorLogBundle\Entity\Occurrence", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private ?Occurrence $lastOccurrence = null;

    /**
     * @ORM\OneToOne(targetEntity="Padam87\ErrorLogBundle\Entity\Error", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private ?Error $previous = null;

    public function __construct()
    {
        $this->occurrences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUniqueHash(): ?string
    {
        return $this->uniqueHash;
    }

    public function setUniqueHash(?string $uniqueHash): self
    {
        $this->uniqueHash = $uniqueHash;

        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function getException(): ?Exception
    {
        return $this->exception;
    }

    public function setException(?Exception $exception): self
    {
        $this->exception = $exception;

        return $this;
    }

    /**
     * @return Collection|Occurrence[]
     */
    public function getOccurrences(): Collection
    {
        return $this->occurrences;
    }

    public function setOccurrences($occurrences): self
    {
        $this->occurrences->clear();

        foreach ($occurrences as $occurrence) {
            $this->addOccurrence($occurrence);
        }

        return $this;
    }

    public function addOccurrence(Occurrence $occurrence): self
    {
        $occurrence->setError($this);

        $this->occurrences->add($occurrence);

        if ($this->fistOccurrence === null) {
            $this->fistOccurrence = $occurrence;
        }

        $this->lastOccurrence = $occurrence;

        return $this;
    }

    public function removeOccurrence(Occurrence $occurrence): self
    {
        $occurrence->setError(null);

        $this->occurrences->removeElement($occurrence);

        return $this;
    }

    public function getFistOccurrence(): ?Occurrence
    {
        return $this->fistOccurrence;
    }

    public function setFistOccurrence(?Occurrence $fistOccurrence): self
    {
        $this->fistOccurrence = $fistOccurrence;

        return $this;
    }

    public function getLastOccurrence(): ?Occurrence
    {
        return $this->lastOccurrence;
    }

    public function setLastOccurrence(?Occurrence $lastOccurrence): self
    {
        $this->lastOccurrence = $lastOccurrence;

        return $this;
    }

    public function getPrevious(): ?Error
    {
        return $this->previous;
    }

    public function setPrevious(?Error $previous): ?Error
    {
        $this->previous = $previous;

        return $this;
    }
}

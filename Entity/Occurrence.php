<?php

namespace Padam87\ErrorLogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Padam87\ErrorLogBundle\Entity\Emeddables\Exception;
use Padam87\ErrorLogBundle\Entity\Emeddables\Request;

/**
 * @ORM\Entity()
 * @ORM\Table("error_occurrence")
 */
class Occurrence
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Padam87\ErrorLogBundle\Entity\Error", inversedBy="occurrences")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private ?Error $error = null;

    /**
     * @ORM\Embedded(class="Padam87\ErrorLogBundle\Entity\Emeddables\Request")
     */
    private ?Request $request = null;

    /**
     * @ORM\Embedded(class="Padam87\ErrorLogBundle\Entity\Emeddables\Exception")
     */
    private ?Exception $exception = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTimeInterface $loggedAt = null;

    /**
     * @ORM\ManyToOne(targetEntity="Padam87\ErrorLogBundle\Entity\UserInterface")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private ?UserInterface $user = null;

    public function __construct()
    {
        $this->request = new Request();
        $this->exception = new Exception();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getError(): ?Error
    {
        return $this->error;
    }

    public function setError(?Error $error): self
    {
        $this->error = $error;

        return $this;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function setRequest(?Request $request): self
    {
        $this->request = $request;

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

    public function getLoggedAt(): ?\DateTimeInterface
    {
        return $this->loggedAt;
    }

    public function setLoggedAt(?\DateTimeInterface $loggedAt): self
    {
        $this->loggedAt = $loggedAt;

        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }
}

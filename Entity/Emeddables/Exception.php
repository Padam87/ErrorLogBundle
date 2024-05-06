<?php

namespace Padam87\ErrorLogBundle\Entity\Emeddables;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Exception
{
    #[ORM\Column]
    private ?string $class = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column]
    private ?string $file = null;

    #[ORM\Column(type: 'integer')]
    private ?int $line = null;

    #[ORM\Column(type: 'json')]
    private ?array $trace = [];

    public static function fromException(\Throwable $exception, string $rootDir): self
    {
        $e = new self();

        $e->class = $exception::class;
        $e->message = $exception->getMessage();
        $e->file = str_replace($rootDir, '', $exception->getFile());
        $e->line = $exception->getLine();

        foreach ($exception->getTrace() as $step) {
            unset($step['args']);

            $step['file'] = @str_replace($rootDir, '', $step['file']);

            $e->trace[] = $step;
        }

        return $e;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(?string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(?string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }

    public function setLine(?int $line): self
    {
        $this->line = $line;

        return $this;
    }

    public function getTrace(): ?array
    {
        return $this->trace;
    }

    public function setTrace(?array $trace): self
    {
        $this->trace = $trace;

        return $this;
    }
}

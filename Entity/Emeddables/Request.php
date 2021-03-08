<?php

namespace Padam87\ErrorLogBundle\Entity\Emeddables;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @ORM\Embeddable()
 */
class Request
{
    /**
     * @ORM\Column(nullable=true)
     */
    private ?string $url = null;

    /**
     * @ORM\Column(nullable=true)
     */
    private ?string $method = null;

    /**
     * @ORM\Column(nullable=true)
     */
    private ?string $locale = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $query = [];

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $request = [];

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $headers = [];

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $attributes = [];

    public static function fromRequest(?\Symfony\Component\HttpFoundation\Request $request): self
    {
        $r = new self();

        $r->method = $request->getMethod();
        $r->url = $request->getPathInfo();
        $r->locale = $request->getLocale();
        $r->request = $request->request->all();
        $r->query = $request->query->all();
        $r->headers = $request->headers->all();
        $r->attributes = $request->attributes->all();

        return $r;
    }

    public static function fromInput(InputInterface $input): self
    {
        $r = new self();

        $r->method = 'console';
        $r->attributes = [
            '_route' => $input->getFirstArgument(),
        ];

        return $r;
    }

    public function __toString(): string
    {
        return sprintf('%s %s', $this->method, $this->url);
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getQuery(): ?array
    {
        return $this->query;
    }

    public function getRequest(): ?array
    {
        return $this->request;
    }

    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    public function setAttributes(?array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }
}

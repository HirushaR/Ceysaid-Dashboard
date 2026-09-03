<?php

namespace App\Support;

class AdminNotificationAction
{
    private string $label;
    private ?string $url = null;

    private function __construct(private readonly string $name)
    {
        $this->label = $name;
    }

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function button(): self
    {
        return $this;
    }

    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function toArray(): array
    {
        return ['name' => $this->name, 'label' => $this->label, 'url' => $this->url];
    }
}

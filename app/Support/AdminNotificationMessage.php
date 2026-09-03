<?php

namespace App\Support;

class AdminNotificationMessage
{
    private string $title = 'Notification';
    private ?string $body = null;
    private string $color = 'info';
    private ?string $icon = null;
    private array $actions = [];

    public static function make(): self
    {
        return new self;
    }

    public function title(string $title): self { $this->title = $title; return $this; }
    public function body(string $body): self { $this->body = $body; return $this; }
    public function color(string $color): self { $this->color = $color; return $this; }
    public function icon(string $icon): self { $this->icon = $icon; return $this; }
    public function success(): self { return $this->color('success'); }
    public function warning(): self { return $this->color('warning'); }
    public function danger(): self { return $this->color('danger'); }

    /** @param array<int, AdminNotificationAction> $actions */
    public function actions(array $actions): self
    {
        $this->actions = $actions;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'color' => $this->color,
            'icon' => $this->icon,
            'actions' => array_map(fn (AdminNotificationAction $action) => $action->toArray(), $this->actions),
        ];
    }
}

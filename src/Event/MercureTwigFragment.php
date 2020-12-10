<?php

declare(strict_types=1);

namespace Bigoen\MercureTwig\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MercureTwigFragment extends Event
{
    public string $identifier;
    public string $url;
    public array $topics;

    public function __construct(string $identifier, string $url, array $topics)
    {
        $this->identifier = $identifier;
        $this->url = $url;
        $this->topics = $topics;
    }
}

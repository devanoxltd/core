<?php

namespace Devanox\Core\Support\AvatarProvider;

use Devanox\Core\Trait\Avatar;

class DuckDuckGo implements AvatarInterface
{
    use Avatar;

    public function handel(string $username): self
    {
        $this->username = $username;
        $url = sprintf('https://icons.duckduckgo.com/ip3/%s.ico', $username);

        $this->url = $url;

        return $this;
    }
}

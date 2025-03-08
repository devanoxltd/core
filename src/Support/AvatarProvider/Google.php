<?php

namespace Devanox\Core\Support\AvatarProvider;

use Devanox\Core\Trait\Avatar;

class Google implements AvatarInterface
{
    use Avatar;

    public function handel(string $username): self
    {
        $this->username = $username;
        $url = sprintf('https://www.google.com/s2/favicons?domain_url=%s&sz=256', $username);

        $this->url = $url;

        return $this;
    }
}

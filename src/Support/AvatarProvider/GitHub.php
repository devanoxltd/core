<?php

namespace Devanox\Core\Support\AvatarProvider;

use Devanox\Core\Trait\Avatar;

class GitHub implements AvatarInterface
{
    use Avatar;

    public function handel(string $username): self
    {
        $this->username = $username;
        $url = sprintf('https://github.com/%s.png?%s', $username, http_build_query(['size' => config('app.avatar.size')]));

        $this->url = $url;

        return $this;
    }
}

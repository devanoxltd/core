<?php

namespace Devanox\Core\Support\AvatarProvider;

use Devanox\Core\Trait\Avatar;

class Gravatar implements AvatarInterface
{
    use Avatar;

    public function handel(string $username): self
    {
        $username = strtolower($username);

        $this->username = $username;

        $username = md5($username);

        $url = sprintf('https://gravatar.com/avatar/%s?%s', $username, http_build_query(['size' => config('app.avatar.size'), 'd' => '404']));

        $this->url = $url;

        return $this;
    }
}

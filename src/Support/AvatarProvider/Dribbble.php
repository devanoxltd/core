<?php

namespace Devanox\Core\Support\AvatarProvider;

use Devanox\Core\Trait\Avatar;
use Illuminate\Support\Facades\Http;
use PHPHtmlParser\Dom;

class Dribbble implements AvatarInterface
{
    use Avatar;

    public function handel(string $username): self
    {
        $this->username = $username;

        $url = sprintf('https://dribbble.com/%s', $username);

        $response = Http::get($url);

        if ($response->failed()) {
            return $this;
        }

        $body = $response->body();

        $dom = new Dom;
        $dom->loadStr($body);

        try {
            $avatar = $dom->find('.profile-avatar')->getAttribute('src');
        } catch (\Throwable $th) {
            $avatar = null;
        }

        if (! $avatar) {
            return $this;
        }

        $url = $avatar;

        $this->url = $url;

        return $this;
    }
}

<?php

namespace Devanox\Core\Trait;

use Illuminate\Support\Facades\Http;

trait Avatar
{
    public $url = null;

    public $username = null;

    public function getFallbackUrl(): string
    {
        if (request()->fallback == 'false') {
            return abort(404);
        }

        if (request()->fallback) {
            return request()->fallback;
        }

        return $this->getAvatarAsset();
    }

    public function getAvatarAsset(): string
    {
        return asset('images/avatar.png');
    }

    public function getAvatarUrl(): string
    {
        return $this->url;
    }

    public function getAvatar(): \Illuminate\Http\Client\Response
    {
        if (! $this->url) {
            return $this->getFallback();
        }

        $response = Http::get($this->url);

        if ($response->failed()) {
            return $this->getFallback();
        }

        return $response;
    }

    public function getFallback(): \Illuminate\Http\Client\Response
    {
        $url = $this->getFallbackUrl();

        try {
            $response = Http::get($url);
        } catch (\Throwable $th) {
            $response = Http::get($this->getAvatarAsset());
        }

        return $response;
    }
}

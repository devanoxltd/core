<?php

namespace Devanox\Core\Support\AvatarProvider;

interface AvatarInterface
{
    public function handel(string $username): self;

    public function getAvatar(): \Illuminate\Http\Client\Response;

    public function getAvatarUrl(): string;

    public function getFallback(): \Illuminate\Http\Client\Response;

    public function getFallbackUrl(): string;
}

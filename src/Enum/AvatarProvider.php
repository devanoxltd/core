<?php

namespace Devanox\Core\Enum;

use Devanox\Core\Support\AvatarProvider\DeviantArt;
use Devanox\Core\Support\AvatarProvider\Dribbble;
use Devanox\Core\Support\AvatarProvider\DuckDuckGo;
use Devanox\Core\Support\AvatarProvider\GitHub;
use Devanox\Core\Support\AvatarProvider\GitLab;
use Devanox\Core\Support\AvatarProvider\Google;
use Devanox\Core\Support\AvatarProvider\Gravatar;
use Devanox\Core\Support\AvatarProvider\Instagram;
use Devanox\Core\Support\AvatarProvider\OnlyFans;
use Devanox\Core\Support\AvatarProvider\ReadCV;
use Devanox\Core\Support\AvatarProvider\Reddit;
use Devanox\Core\Support\AvatarProvider\SoundCloud;
use Devanox\Core\Support\AvatarProvider\Substack;
use Devanox\Core\Support\AvatarProvider\Telegram;
use Devanox\Core\Support\AvatarProvider\TikTok;
use Devanox\Core\Support\AvatarProvider\Twitch;
use Devanox\Core\Support\AvatarProvider\X;
use Devanox\Core\Support\AvatarProvider\YouTube;

enum AvatarProvider: string
{
    case deviantart = 'deviantart';
    case dribbble = 'dribbble';
    case duckduckgo = 'duckduckgo';
    case github = 'github';
    case gitlab = 'gitlab';
    case google = 'google';
    case gravatar = 'gravatar';
    // case instagram = 'instagram';
    // case onlyfans = 'onlyfans';
    case readcv = 'readcv';
    case reddit = 'reddit';
    case soundcloud = 'soundcloud';
    case substack = 'substack';
    case telegram = 'telegram';
    // case tiktok = 'tiktok';
    case twitch = 'twitch';
    case x = 'x';
    case youtube = 'youtube';

    // phpcs:ignore
    public function class(): string
    {
        return match ($this) {
            self::deviantart => DeviantArt::class,
            self::dribbble => Dribbble::class,
            self::duckduckgo => DuckDuckGo::class,
            self::github => GitHub::class,
            self::gitlab => GitLab::class,
            self::google => Google::class,
            self::gravatar => Gravatar::class,
            // self::instagram => Instagram::class,
            // self::onlyfans => OnlyFans::class,
            self::readcv => ReadCV::class,
            self::reddit => Reddit::class,
            self::soundcloud => SoundCloud::class,
            self::substack => Substack::class,
            self::telegram => Telegram::class,
            // self::tiktok => TikTok::class,
            self::twitch => Twitch::class,
            self::x => X::class,
            self::youtube => YouTube::class,
            default => GitHub::class,
        };
    }
}

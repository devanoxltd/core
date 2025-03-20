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
    case DeviantArt = 'deviantart';
    case Dribbble = 'dribbble';
    case DuckDuckGo = 'duckduckgo';
    case GitHub = 'github';
    case GitLab = 'gitlab';
    case Google = 'google';
    case Gravatar = 'gravatar';
    // case Instagram = 'instagram';
    // case OnlyFans = 'onlyfans';
    case ReadCV = 'readcv';
    case Reddit = 'reddit';
    case SoundCloud = 'soundcloud';
    case Substack = 'substack';
    case Telegram = 'telegram';
    // case TikTok = 'tiktok';
    case Twitch = 'twitch';
    case X = 'x';
    case YouTube = 'youtube';

    // phpcs:ignore
    public function class(): string
    {
        return match ($this) {
            self::DeviantArt => DeviantArt::class,
            self::Dribbble => Dribbble::class,
            self::DuckDuckGo => DuckDuckGo::class,
            self::GitHub => GitHub::class,
            self::GitLab => GitLab::class,
            self::Google => Google::class,
            self::Gravatar => Gravatar::class,
            // self::Instagram => Instagram::class,
            // self::OnlyFans => OnlyFans::class,
            self::ReadCV => ReadCV::class,
            self::Reddit => Reddit::class,
            self::SoundCloud => SoundCloud::class,
            self::Substack => Substack::class,
            self::Telegram => Telegram::class,
            // self::TikTok => TikTok::class,
            self::Twitch => Twitch::class,
            self::X => X::class,
            self::YouTube => YouTube::class,
            default => GitHub::class,
        };
    }
}

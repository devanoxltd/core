<?php

namespace Devanox\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Devanox\Core\Enum\AvatarProvider;

class AvatarController extends Controller
{
    public function index($provider, $username = null)
    {
        if ($username === null) {
            $username = $provider;
            $provider = 'github';
        }

        $provider = str($provider)->lower()->__toString();

        $provider = AvatarProvider::tryFrom($provider);

        if ($provider === null) {
            $provider = AvatarProvider::Github;
        }

        $providerClass = $provider->class();

        $avatar = new $providerClass();
        $avatar = $avatar->handel($username);

        $response = $avatar->getAvatar();

        return response($response->body(), $response->status())
            ->header('Content-Type', $response->header('Content-Type'));
    }

    public function fallback()
    {
        $fallback = asset('images/avatar.png');
        return response()->redirectTo($fallback);
    }
}

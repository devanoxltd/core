<?php

declare(strict_types=1);

it('renders the workbench homepage', function (): void {
    visit('/')
        ->assertTitle('Core Workbench')
        ->assertSee('Core package workbench')
        ->assertNoSmoke();
});

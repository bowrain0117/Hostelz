<?php

namespace App\Providers;

use App;
use App\Events\AttachedTextUpdated;
use App\Listeners\ClearCacheAfterAttachedUpdated;
use App\Listeners\ImportSubscriber;
use App\Models\HostelsChain;
use App\Models\Imported;
use App\Models\User;
use App\Observers\HostelsChainObserver;
use App\Observers\ImportedObserver;
use App\Observers\UserObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        AttachedTextUpdated::class => [
            ClearCacheAfterAttachedUpdated::class,
        ], ];

    protected $subscribe = [
        ImportSubscriber::class,
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();

        User::observe(UserObserver::class);
        Imported::observe(ImportedObserver::class);
        HostelsChain::observe(HostelsChainObserver::class);

        /*
        ** Event Listeners
        */

        Event::listen('Illuminate\Auth\Events\Login', function ($data): void {
            $data->user->loginEvent($data->remember);
        });

        Event::listen('Illuminate\Auth\Events\Logout', function ($data): void {
            if ($data->user) {
                $data->user->logoutEvent();
            }
        });
    }
}

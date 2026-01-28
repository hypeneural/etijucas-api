<?php

namespace App\Providers;

use App\Models\Bairro;
use App\Models\ContentFlag;
use App\Models\Phone;
use App\Models\User;
use App\Models\UserRestriction;
use App\Policies\ActivityPolicy;
use App\Policies\BairroPolicy;
use App\Policies\ContentFlagPolicy;
use App\Policies\PhonePolicy;
use App\Policies\UserPolicy;
use App\Policies\UserRestrictionPolicy;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Bairro::class, BairroPolicy::class);
        Gate::policy(UserRestriction::class, UserRestrictionPolicy::class);
        Gate::policy(ContentFlag::class, ContentFlagPolicy::class);
        Gate::policy(Phone::class, PhonePolicy::class);
        Gate::policy(Activity::class, ActivityPolicy::class);
    }
}

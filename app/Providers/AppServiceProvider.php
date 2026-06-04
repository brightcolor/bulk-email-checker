<?php

namespace App\Providers;

use App\Models\ApiKey;
use App\Models\BulkJob;
use App\Models\Team;
use App\Models\TenantMembership;
use App\Models\TenantSettings;
use App\Policies\ApiKeyPolicy;
use App\Policies\BulkJobPolicy;
use App\Policies\TeamPolicy;
use App\Policies\TenantMembershipPolicy;
use App\Policies\TenantSettingsPolicy;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuditLogger::class, function ($app) {
            return new AuditLogger($app['request']);
        });
    }

    public function boot(): void
    {
        Gate::policy(BulkJob::class, BulkJobPolicy::class);
        Gate::policy(TenantMembership::class, TenantMembershipPolicy::class);
        Gate::policy(ApiKey::class, ApiKeyPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(TenantSettings::class, TenantSettingsPolicy::class);
    }
}

<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        TrustProxies::at(config('app.trusted_proxies', '*'));

        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Model::preventLazyLoading(! app()->isProduction());

        RateLimiter::for('login', function (Request $request): Limit {
            $studentId = Str::transliterate(Str::lower($request->string('student_id')->trim()->toString()));

            return Limit::perMinute(5)->by($studentId.'|'.$request->ip());
        });

        RateLimiter::for('admin-login', function (Request $request): Limit {
            $email = Str::transliterate(Str::lower($request->string('email')->trim()->toString()));

            return Limit::perMinute(5)->by('admin|'.$email.'|'.$request->ip());
        });

        RateLimiter::for('admin-mfa', function (Request $request): Limit {
            $pendingAdministrator = $request->session()->get('admin_mfa.pending_user_id', 'guest');

            return Limit::perMinute(5)->by('admin-mfa|'.$pendingAdministrator.'|'.$request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request): Limit {
            $email = Str::transliterate(Str::lower($request->string('email')->trim()->toString()));

            return Limit::perMinute(3)->by('password-reset|'.$email.'|'.$request->ip());
        });

        RateLimiter::for('sync', function (Request $request): Limit {
            $student = $request->user()?->getAuthIdentifier() ?? 'guest';

            return Limit::perMinute(30)->by($student.'|'.$request->ip());
        });

        RateLimiter::for('render-reminders', fn (Request $request): Limit => Limit::perMinute(3)
            ->by('render-reminders|'.$request->ip()));

        Gate::define('manage-administrators', fn (User $user): bool => $user->hasCapability('administrators.manage'));
        Gate::define('view-system-health', fn (User $user): bool => $user->hasCapability('system-health.view'));
    }
}

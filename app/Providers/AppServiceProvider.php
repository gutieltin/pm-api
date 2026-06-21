<?php

namespace App\Providers;

use App\Events\TaskUpdated;
use App\Events\UserCreated;
use App\Listeners\SendTaskProgressNotification;
use App\Listeners\SendUserCreatedNotification;
use App\Models\Task;
use App\Policies\TaskPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        // Force HTTPS in production (Render terminates SSL for us, but Laravel needs to know)
        if (env('APP_ENV') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // General API limit: 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Stricter limit for Login: 5 attempts per minute
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Corporate "Heavy" actions (e.g., generating reports or creating many users)
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id);
        });

        Gate::policy(Task::class, TaskPolicy::class);

        // Register event listeners
        Event::listen(UserCreated::class, SendUserCreatedNotification::class);
        Event::listen(TaskUpdated::class, SendTaskProgressNotification::class);
    }
}

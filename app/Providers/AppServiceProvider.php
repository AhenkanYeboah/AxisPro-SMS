<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
{
    if (config('app.env') === 'production') {
        URL::forceScheme('https');
    }
}
    public function boot(): void
    {
        // Render terminates HTTPS at its edge/proxy - the app itself
        // receives requests over plain HTTP internally. Without this,
        // asset()/url() generate http:// links even though the page is
        // served over https://, and browsers silently block that as
        // mixed content. Scoped to production only so local dev is
        // unaffected.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}

<?php

namespace App\Providers;

use App\Services\Fiscal\FiscalEmissorRegistry;
use App\Services\Fiscal\FiscalService;
use Illuminate\Support\ServiceProvider;

class FiscalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FiscalEmissorRegistry::class, FiscalEmissorRegistry::class);
        $this->app->singleton(FiscalService::class, FiscalService::class);
    }
}

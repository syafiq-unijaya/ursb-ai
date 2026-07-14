<?php

namespace App\Providers;

use App\AiTools\GetCarsTool;
use DeveloperUnijaya\AiChatbox\Orchestration\ToolRegistry;
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
        // Make the cars lookup available to the AI chatbox orchestrator.
        $this->app->make(ToolRegistry::class)->register(new GetCarsTool());
    }
}

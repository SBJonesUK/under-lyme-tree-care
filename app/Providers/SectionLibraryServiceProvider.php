<?php

namespace App\Providers;

use App\Http\Controllers\CP\SectionLibraryController;
use App\SectionLibrary\SectionLibraryManager;
use Illuminate\Support\ServiceProvider;
use Statamic\Facades\Utility;

class SectionLibraryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SectionLibraryManager::class, fn ($app) => new SectionLibraryManager($app['files']));
    }

    public function boot(): void
    {
        Utility::register('section_library')
            ->action([SectionLibraryController::class, 'index'])
            ->title('Section Library')
            ->navTitle('Section Library')
            ->icon('addons')
            ->description('Install and manage reusable section bundles for the starter kit.')
            ->routes(function ($router) {
                $router->post('refresh', [SectionLibraryController::class, 'refresh'])->name('refresh');
                $router->post('initialize', [SectionLibraryController::class, 'initialize'])->name('initialize');
            });
    }
}

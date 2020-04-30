<?php

namespace App\Providers;

use App\Models\Reservation;
use Hashids\Hashids;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        $this->mapCPanelRoutes();

        $this->mapDoctorCPanelRoutes();


        $this->mapVisitRoutes();

        $this->mapGeneralVisitsApiRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::middleware('api')
            ->prefix('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    protected function mapCPanelRoutes()
    {
        Route::prefix('api/v1/cpanel')
            ->middleware('api')
            ->namespace('App\Http\Controllers\CPanel')
            ->group(base_path('routes/cpanel.php'));
    }

    protected function mapVisitRoutes()
    {
        Route::prefix('api/v1/cpanel')
            ->middleware('api')
            ->namespace('App\Http\Controllers\CPanel')
            ->group(base_path('routes/visit.php'));
    }

    protected function mapDoctorCPanelRoutes()
    {
        Route::prefix('api/')
            ->middleware('api')
            ->namespace('App\Http\Controllers\CPanel')
            ->group(base_path('routes/doctor-cpanel.php'));
    }

    protected function mapGeneralVisitsApiRoutes()
    {
        Route::middleware('api')
            ->prefix('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/global-visits-api.php'));
    }

}

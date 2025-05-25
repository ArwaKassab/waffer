<?php

namespace App\Providers;

use App\Repositories\Contracts\AddressRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\AddressRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Services\AddressService;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // تسجيل الريبوزتوري
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AddressRepositoryInterface::class, AddressRepository::class);

        // تسجيل الـ AddressService
        $this->app->bind(AddressService::class, function ($app) {
            return new AddressService($app->make(AddressRepositoryInterface::class));
        });

        // تسجيل الـ UserService
        $this->app->bind(UserService::class, function ($app) {
            return new UserService(
                $app->make(UserRepositoryInterface::class),
                $app->make(AddressService::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}

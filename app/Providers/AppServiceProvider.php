<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\{ProductRepositoryInterface,
    UserRepositoryInterface,
    AddressRepositoryInterface,
    StoreRepositoryInterface,
    AreaRepositoryInterface,
    CategoryRepositoryInterface};
use App\Repositories\Eloquent\{ProductRepository,
    UserRepository,
    AddressRepository,
    StoreRepository,
    AreaRepository,
    CategoryRepository};

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AddressRepositoryInterface::class, AddressRepository::class);
        $this->app->bind(StoreRepositoryInterface::class, StoreRepository::class);
        $this->app->bind(AreaRepositoryInterface::class, AreaRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
    }

    public function boot(): void
    {
        //
    }
}

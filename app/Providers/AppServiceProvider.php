<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\{AdRepositoryInterface,
    CartRepositoryInterface,
    ComplaintRepositoryInterface,
    CustomerRepositoryInterface,
    LinkRepositoryInterface,
    OfferDiscountRepositoryInterface,
    OrderRepositoryInterface,
    ProductRepositoryInterface,
    UserRepositoryInterface,
    AddressRepositoryInterface,
    StoreRepositoryInterface,
    AreaRepositoryInterface,
    CategoryRepositoryInterface,
    WalletRepositoryInterface};
use App\Repositories\Eloquent\{AdRepository,
    CartRepository,
    ComplaintRepository,
    CustomerRepository,
    LinkRepository,
    OfferDiscountRepository,
    OrderRepository,
    ProductRepository,
    UserRepository,
    AddressRepository,
    StoreRepository,
    AreaRepository,
    CategoryRepository,
    WalletRepository};

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
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);
        $this->app->bind(OfferDiscountRepositoryInterface::class, OfferDiscountRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(WalletRepositoryInterface::class, WalletRepository::class);
        $this->app->bind(LinkRepositoryInterface::class, LinkRepository::class);
        $this->app->bind(ComplaintRepositoryInterface::class, ComplaintRepository::class);
        $this->app->bind(AdRepositoryInterface::class, AdRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);

    }

    public function boot(): void
    {
        //
    }
}

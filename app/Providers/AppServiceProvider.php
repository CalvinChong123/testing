<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use App\Models\{
    SptModelHasRole,
    Admin,
    Game,
    Transaction,
    CashFloat,
    CashReplenishment,
    Merchant,
    MerchantGroup,
    Referral,
    UserPointBalance,
    UserPromotionCreditBalance,
};
use App\Observers\SyncLogObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {}

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // NEXUS: Enable this if facing "Specified key was too long" error during migration at server
        // \Illuminate\Support\Facades\Schema::defaultStringLength(191);

        Model::preventLazyLoading();

        // sync log observer
        SptModelHasRole::observe(SyncLogObserver::class);
        Admin::observe(SyncLogObserver::class);
        Game::observe(SyncLogObserver::class);
        Referral::observe(SyncLogObserver::class);
        Transaction::observe(SyncLogObserver::class);
        CashFloat::observe(SyncLogObserver::class);
        CashReplenishment::observe(SyncLogObserver::class);
        Merchant::observe(SyncLogObserver::class);
        MerchantGroup::observe(SyncLogObserver::class);
        UserPointBalance::observe(SyncLogObserver::class);
        UserPromotionCreditBalance::observe(SyncLogObserver::class);
    }
}

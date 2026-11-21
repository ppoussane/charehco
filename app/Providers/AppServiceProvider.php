<?php

namespace App\Providers;

use App\Models\User;
use App\Support\LocalDate;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configureGates();
        $this->configureDates();
    }

    /**
     * $date->localDate('short') formats in the visitor's language (Jalali for Farsi).
     */
    protected function configureDates(): void
    {
        $macro = function (string $style = 'date'): string {
            /** @var \DateTimeInterface $this */
            return LocalDate::format($this, $style);
        };

        CarbonImmutable::macro('localDate', $macro);
        Carbon::macro('localDate', $macro);
    }

    /**
     * Site-wide abilities used by routes and navigation.
     */
    protected function configureGates(): void
    {
        Gate::define('admin', fn (User $user): bool => $user->isAdmin());
        Gate::define('edit-content', fn (User $user): bool => $user->canEditContent());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

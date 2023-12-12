<?php

namespace App\Providers;

use App;
use App\Helpers\EventLog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Lib\CustomValidations;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
//        Model::preventLazyLoading(! $this->app->isProduction());

        RateLimiter::for('perMinuteJobLimit', fn ($job) => Limit::perMinute(240));

        /* Globals */

        $GLOBALS['PUBLIC_PATH_DYNAMIC_DATA'] = public_path(); // is normally public_path(), except in testing mode.
        $GLOBALS['DATA_STORAGE'] = config('custom.userRoot') . '/data'; // shared between dev and live, but different path is used for testing.

        /*
        ** Debug Flags
        */

        if ($this->app->environment('production')) {
            DB::disableQueryLog();
        } else {
            if (config('custom.eventLogDisabled')) {
                EventLog::disable();
            }
            if (config('custom.eventLogVerbose')) {
                EventLog::setVerbose(true);
            }
        }

        /*
        ** IoC Bindings
        */

        $this->app->singleton('emailer', function () {
            return new \Lib\Emailer(config('custom.emailPretend'), config('custom.emailerAllEmailsTo'));
        });

        /*
        $this->app->singleton('billing', function() {
            return new BillingImplementation(config('custom.billingSandbox'));
        });
        */

        /* Misc Customization */

        CustomValidations::addValidations();

        /* Blade Customization */

        // @langGet() blade command (same thing as using {!! langGet() !!})
        Blade::directive('langGet', function ($expression) {
            return "<?php echo langGet($expression); ?>";
        });

        // @langChoice() blade command (same thing as using {!! langChoice() !!})
        Blade::directive('langChoice', function ($expression) {
            return "<?php echo langChoice($expression); ?>";
        });

        // @routeURL() blade command (same thing as using {!! routeURL() !!})
        Blade::directive('routeURL', function ($expression) {
            return "<?php echo routeURL($expression); ?>";
        });

        Blade::directive('breadcrumb', function ($expression) {
            return "<?php echo breadcrumb($expression); ?>";
        });

        Blade::directive('setVariableFromSection', function ($expression) {
            $name = trim($expression, "()'");

            return "<?php \$$name = \$__env->yieldContent('$name'); ?>";
        });

        /*
            This makes {{{ }}} do escaped output.  This is different than the Blade
            escape in that ours doesn't set double_encode=true, so that it re-escapes things like "&amp;"
            so that it's displayed fully encoded (which is what we want when doing things like
            editing database values, etc.
        */

        Blade::extend(function ($view, $compiler) {
            //  todo: fixed with old code
            $compileEchoDefaults = function ($value) {
                return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $value);
            };

            $callback = function ($matches) use ($compiler, $compileEchoDefaults) {
                $whitespace = empty($matches[2]) ? '' : $matches[2] . $matches[2];

                return '<?php echo htmlentities(' . $compileEchoDefaults($matches[1]) . '); ?>' . $whitespace;
            };

            return preg_replace_callback('/{{{\s*(.+?)\s*}}}(\r?\n)?/s', $callback, $view);
        });

        // This allows us to load views in the 'paymentProcessor' namespace like this:
        //
        //		view('paymentProcessor::[name].[view file name]')
        //
        // The view files are stored in shared/Services/PaymentProcessors.
        $this->loadViewsFrom(__DIR__ . '/../PaymentProcessors', 'paymentProcessor');

        Blade::anonymousComponentPath(resource_path('/views/slp/components'), 'slp');
        Blade::anonymousComponentPath(resource_path('/views/city/components'), 'city');
        Blade::anonymousComponentPath(resource_path('/views/user/components/reservations'), 'user-reservations');
        Blade::anonymousComponentPath(base_path('lib/views/Lib/formHandler'), 'form-handler');
    }

    /**
     * Register any application services.
     *
     * This service provider is a great spot to register your various container
     * bindings with the application. As you can see, we are registering our
     * "Registrar" implementation here. You can add your own bindings too!
     *
     * @return void
     */
    public function register(): void
    {
        /* (See https://mattstauffer.co/blog/conditionally-loading-service-providers-in-laravel-5 for info on loading services conditionally). */

        $this->app->bind(
            'Illuminate\Contracts\Auth\Registrar',
            'App\Services\Registrar'
        );
    }
}

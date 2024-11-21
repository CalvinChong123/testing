<?php

namespace App\Providers;

use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Telescope::night(); // comment to toggle theme

        $this->hideSensitiveRequestDetails();

        $this->setTagsToRecord();

        Telescope::filter(function (IncomingEntry $entry) {
            return true; // temparary for all enviroments
            // if ($this->app->environment('local')) {
            //     return true;
            // }

            return $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     *
     * @return void
     */
    protected function hideSensitiveRequestDetails()
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    private function setTagsToRecord(): void
    {
        Telescope::tag(function (IncomingEntry $entry) {
            if ($entry->type === 'request') {
                $uri = $entry->content['uri'] ?? null;
                $endpoint = substr($uri, 0, strpos($uri, '?') ?: strlen($uri));
                $host = optional($entry->content['headers'])['host'] ?? null;
                $responseCode = $entry->content['response_status'] ?? null;
                $authUser = auth()->user();

                $tags = [
                    $host,
                    'Status:'.$responseCode,
                    $endpoint,
                ];

                if ($authUser) {
                    array_push($tags, "$endpoint|$authUser->id");
                }

                return array_filter($tags);
            }

            return [];
        });
    }
}

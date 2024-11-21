<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Config;
use Illuminate\Support\Facades\Log;
use App\Models\SynchronizeAuthToken;

class RegisterOutlet extends Command
{
    protected $signature = 'outlet:register';
    protected $description = 'First time register the outlet with HQ';

    public function handle()
    {

        $config = Config::where('name', Config::NAME_OUTLET_NAME)->first();

        $outletData = [
            'local_outlet_id' => $config->outlet_id,
            'name' => $config->outlet_name,
        ];

        $hqUrl = config('app.hq_url');

        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->post($hqUrl . '/api/outlet/create', $outletData);

        if ($response->successful()) {
            SynchronizeAuthToken::get()->each->delete();
            SynchronizeAuthToken::create([
                'token' => $response['data']['auth_token']
            ]);
            return Command::SUCCESS;
        } else {
            $this->error('Failed to register outlet: ' . $response->body());
            return Command::FAILURE;
        }
    }
}

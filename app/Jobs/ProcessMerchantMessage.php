<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Merchant;
use App\Models\MerchantReport;
use App\Http\Services\MerchantService;
use App\Library\MerchantCommandTag;

class ProcessMerchantMessage implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $merchant;
    protected $data;

    public function __construct($merchant, $data)
    {
        $this->merchant = $merchant;
        $this->data = $data;
    }

    public function handle()
    {
        $decodeData = json_decode($this->data, true);
        Log::info("Processing message from merchant: {$this->merchant->id}");
        $user = $this->merchant->currentMerchantUser()['user'] ?? null;
        if ($user) {
            Log::info('user: ' . $user->name);
        } else {
            Log::info('No current merchant user found.');
        }
        Log::info($decodeData);

        MerchantReport::create([
            'merchant_id' => $this->merchant->id,
            'command' => $decodeData['cmd'] ?? null,
            'response' => $this->data,
            'event_name' => $decodeData['event_name'] ?? null,
            'user_id' => $user->id,
        ]);


        if ($decodeData['cmd'] == 'event' && $decodeData['event_name'] == 'handpay pending') {
            $payload = [
                'user_id' => $user->id,
                'merchant_id' => $this->merchant->id,
                'cid0' => $this->merchant->cid0,
                'cid1' => $this->merchant->cid1,
                'cid2' => $this->merchant->cid2,
            ];
            $result = MerchantService::createWithdrawalTransaction($payload);
            Log::info($result);
        }
    }
}

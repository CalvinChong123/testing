<?php

namespace App\Listeners;

use App\Events\MerchantsBroadcastEvent;
use App\Events\MerchantsBroadcastReceiveEvent;
use App\Models\Merchant;
use Exception;
use Illuminate\Support\Facades\Log;

class MerchantsBroadcastListener
{
    private $port = 30624;

    private $command = '{ "cmd": "browse"}';

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(MerchantsBroadcastEvent $event)
    {
        $broadcastAddress = '255.255.255.255';
        $timeout = 10;
        $startTime = time();

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $timeout, 'usec' => 0]);

        try {
            socket_bind($socket, '0.0.0.0', $this->port);
            socket_sendto($socket, $this->command, strlen($this->command), 0, $broadcastAddress, $this->port);

            while ((time() - $startTime) < $timeout) {
                $response = '';
                $from = '';
                $port = 0;
                $result = socket_recvfrom($socket, $response, 2048, 0, $from, $port);

                if ($result === false) {
                    $errorCode = socket_last_error($socket);
                    throw new Exception('Error receiving response: ' . socket_strerror($errorCode));
                }

                Log::info("Merchant Broadcast Listner receive response from $from: $response");

                $data = json_decode($response, true);

                if (isset($data['cid0']) && isset($data['cid1']) && isset($data['cid2'])) {
                    $merchant = Merchant::where('cid0', $data['cid0'])
                        ->where('cid1', $data['cid1'])
                        ->where('cid2', $data['cid2'])
                        ->first();

                    if ($merchant) {
                        Log::info('Found merchant: ' . $merchant->id);
                        // Broadcast the online merchant to the frontend immediately
                        event(new MerchantsBroadcastReceiveEvent($merchant->id));
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Error in broadcast listener: ' . $e->getMessage());
        } finally {
            socket_close($socket);
        }
    }
}

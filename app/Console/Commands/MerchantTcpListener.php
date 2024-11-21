<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Merchant;
use Spatie\Async\Pool;
use Exception;
use App\Jobs\ProcessMerchantMessage;

class MerchantTcpListener extends Command
{
    protected $signature = 'merchant:tcp-listener';
    protected $description = 'Listen for TCP messages from merchants';

    public function handle()
    {
        // $pidFile = storage_path('app/merchant_tcp_listener.pid');

        // if (file_exists($pidFile)) {
        //     $this->error('Listener is already running.');
        //     return;
        // } else {
        //     log::info('Listener is not running');
        // }

        // file_put_contents($pidFile, getmypid());

        // log::info('Starting merchant tcp listener');

        $merchants = Merchant::all();
        $pool = Pool::create();

        foreach ($merchants as $merchant) {
            $this->info("Starting listener for merchant: {$merchant->id}");
            $pool->add(function () use ($merchant) {
                $this->listenToMerchant($merchant);
            });
        }

        // $pool->add(function () use ($merchant) {
        //     $this->info("Starting listener for merchant: {$merchant->id}");
        //     Log::info("Worker started with PID: " . getmypid());
        //     $this->listenToMerchant($merchant);
        // })
        //     ->then(function () {
        //         Log::info("Task completed with PID: " . getmypid());
        //     })
        //     ->catch(function (Exception $e) {
        //         Log::error("Error with worker: " . $e->getMessage());
        //     });

        $pool->wait();

        // if (file_exists($pidFile)) {
        //     $this->error('Listener is already running.');
        //     return;
        // } else {
        //     log::info('Listener is not running');
        // }


        // unlink($pidFile);
    }

    private function listenToMerchant($merchant)
    {
        $ip = $merchant->ip_address;
        $tcpPort = '30625';

        try {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if (!$socket) {
                throw new Exception('Unable to create socket');
            }

            $connected = socket_connect($socket, $ip, $tcpPort);
            if (!$connected) {
                throw new Exception('Unable to connect to merchant at ' . $ip);
            }

            $this->sendCommand($socket, 'identify', $merchant);

            while (true) {
                $data = socket_read($socket, 2048);
                if ($data === false) {
                    Log::error("Connection lost with merchant: {$ip}");
                    break;
                }

                $this->processMerchantMessage($merchant, $data);
            }
        } catch (Exception $e) {
            Log::error("Error in connection: {$e->getMessage()}");
        } finally {
            if (isset($socket)) {
                socket_close($socket);
            }
        }
    }

    private function sendCommand($socket, $command, $merchant)
    {
        $cmd = json_encode([
            'cmd' => $command,
            'cid0' => $merchant->cid0,
            'cid1' => $merchant->cid1,
            'cid2' => $merchant->cid2
        ]) . "\n\n";

        $result = socket_write($socket, $cmd, strlen($cmd));

        Log::info("Sent command: $cmd");
        if ($result === false) {
            $errorCode = socket_last_error($socket);
            $errorMessage = socket_strerror($errorCode);
            Log::error("Failed to send command: $errorMessage");
        } else {
            Log::info("Command sent successfully, bytes written: $result");
        }
    }

    private function processMerchantMessage($merchant, $data)
    {
        dispatch(new ProcessMerchantMessage($merchant, $data));
    }
}

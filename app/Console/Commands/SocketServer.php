<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SocketServer extends Command
{
    protected $signature = 'socket:client {message}';

    protected $description = 'Send a message to the socket server';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $host = '255.255.255.255';
        $port = 30624;
        $timeout = 15;

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $timeout, 'usec' => 0]);

        $message = $this->argument('message');
        socket_sendto($socket, $message, strlen($message), 0, $host, $port);

        $response = '';
        $from = '';
        $port = 0;

        $bytes = socket_recvfrom($socket, $response, 2048, 0, $from, $port);
        if ($bytes === false) {
            $this->error('Error receiving response: '.socket_strerror(socket_last_error($socket)));
        } else {
            $response = trim($response);
            $this->info("Received response: $response from $from:$port");
        }

        socket_close($socket);
    }
}

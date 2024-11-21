<?php

namespace App\Library;

class MerchantCommandTag
{
    public const MM_ENTER = 'mm_enter';

    public const MM_EXIT = 'mm_exit';

    public const PING = 'ping';

    public const IDENTIFY = 'identify';

    public const BROWSE = 'browse';

    public const BLINK = 'blink';

    public const METERS = 'meters';

    public const GMETERS = 'gmeters';

    public const BILL_METERS = 'bill_meters';

    public const GAME_INFO = 'game_info';

    public const LOCK = 'lock';

    public const UNLOCK = 'unlock';

    public const EVENT = 'event';

    public const PSTAT = 'pstat';

    public const IRQSTAT = 'irqstat';

    public const STATS = 'stats';

    public const CLEAR = 'clear';

    public const TCP_STATUS = 'tcp_status';

    public const CASH_IN = 'cashin';

    public const CASH_OUT = 'cashout';

    public const REBOOT = 'reboot';

    public const HP_RESET = 'hp_reset';

    public const HP_STAT = 'hp_stat';

    public const SUSPEND = 'suspend';

    public const RESUME = 'resume';

    public const UPDATE = 'update';

    public const DIAG = 'diag';

    public static function getAllCommands()
    {
        $reflection = new \ReflectionClass(__CLASS__);
        $constants = $reflection->getConstants();

        return array_values($constants);
    }
}

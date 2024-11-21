<?php

namespace App\Library;

class ResponseCode
{
    /**
     * NEXUS: Error Message Code for API
     * ? use to help API caller to identify specific error in API response
     */
    public const DEFAULT_ERROR = 40000;

    public const FORCE_UPDATE = 40001;

    public const USER_NOT_FOUND = 40002;

    public const SOCIALIZE_SHOULD_LINK_ACCOUNT = 40003;

    public const SOCIALIZE_TOKEN_ALREADY_REGISTERED = 40004;

    public const USER_ALREADY_LINKED = 40005;
}

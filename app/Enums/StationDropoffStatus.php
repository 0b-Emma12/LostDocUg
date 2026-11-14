<?php

namespace App\Enums;

enum StationDropoffStatus: string
{
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Deposited = 'deposited';
    case Collected = 'collected';
}

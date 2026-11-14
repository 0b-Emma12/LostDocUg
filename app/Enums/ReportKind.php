<?php

namespace App\Enums;

enum ReportKind: string
{
    case Lost = 'lost';
    case Found = 'found';
}

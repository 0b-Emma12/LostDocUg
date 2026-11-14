<?php

namespace App\Enums;

enum DraftStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Expired = 'expired';
}

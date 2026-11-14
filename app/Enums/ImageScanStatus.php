<?php

namespace App\Enums;

enum ImageScanStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case MalwareDetected = 'malware_detected';
}

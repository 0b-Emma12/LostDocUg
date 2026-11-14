<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Processing = 'processing';
    case Active = 'active';
    case UnderReview = 'under_review';
    case Matched = 'matched';
    case Recovered = 'recovered';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Expired = 'expired';
}

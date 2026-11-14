<?php

namespace App\Enums;

enum DocumentKind: string
{
    case NiraId = 'nira_id';
    case Passport = 'passport';
    case DrivingLicence = 'driving_licence';
    case LandTitle = 'land_title';
    case AcademicCertificate = 'academic_certificate';
    case StudentId = 'student_id';
    case BankCard = 'bank_card';
    case VoterDocument = 'voter_document';

    public function allowsImages(): bool
    {
        return $this !== self::BankCard;
    }
}

<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('claims')
            ->whereNotNull('claimant_statement')
            ->orderBy('id')
            ->chunkById(100, function (Collection $claims): void {
                foreach ($claims as $claim) {
                    DB::table('claims')
                        ->where('id', $claim->id)
                        ->update(['claimant_statement' => $this->encryptIfNeeded($claim->claimant_statement)]);
                }
            });
    }

    public function down(): void
    {
        DB::table('claims')
            ->whereNotNull('claimant_statement')
            ->orderBy('id')
            ->chunkById(100, function (Collection $claims): void {
                foreach ($claims as $claim) {
                    DB::table('claims')
                        ->where('id', $claim->id)
                        ->update(['claimant_statement' => $this->decryptIfEncrypted($claim->claimant_statement)]);
                }
            });
    }

    private function encryptIfNeeded(string $statement): string
    {
        try {
            Crypt::decryptString($statement);

            return $statement;
        } catch (DecryptException) {
            return Crypt::encryptString($statement);
        }
    }

    private function decryptIfEncrypted(string $statement): string
    {
        try {
            return Crypt::decryptString($statement);
        } catch (DecryptException) {
            return $statement;
        }
    }
};

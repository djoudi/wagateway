<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('is_suspended');
        });

        $emails = array_values(array_unique(array_filter(array_map(
            static fn (string $email) => strtolower(trim($email)),
            explode(',', (string) (filled(env('ADMIN_EMAILS')) ? env('ADMIN_EMAILS') : 'admin@wagateway.dz')),
        ))));

        if ($emails === []) {
            $emails = ['admin@wagateway.dz'];
        }

        if (! in_array('admin@wagateway.dz', $emails, true)) {
            $emails[] = 'admin@wagateway.dz';
        }

        foreach ($emails as $email) {
            DB::table('users')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->update(['is_admin' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
        });

        $used = [];

        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            $base = Str::slug(Str::before($user->email, '@'), '_');

            if ($base === '') {
                $base = 'user';
            }

            $username = $base;
            $suffix = 1;

            while (in_array($username, $used, true)) {
                $username = $base.'_'.$suffix;
                $suffix++;
            }

            $used[] = $username;

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->bigInteger('ktokyda_user_id')->nullable()->after('user_id');
            $table->text('ktokyda_access_token')->nullable()->after('ktokyda_user_id');
            $table->string('ktokyda_refresh_token', 100)->nullable()->after('ktokyda_access_token');
            $table->integer('ktokyda_token_expires_at')->nullable()->after('ktokyda_refresh_token');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'ktokyda_user_id',
                'ktokyda_access_token',
                'ktokyda_refresh_token',
                'ktokyda_token_expires_at',
            ]);
        });
    }
};

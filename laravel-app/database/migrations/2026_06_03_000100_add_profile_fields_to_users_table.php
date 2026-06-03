<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) $table->string('role')->default('User');
            if (!Schema::hasColumn('users', 'addr')) $table->string('addr')->nullable();
            if (!Schema::hasColumn('users', 'gender')) $table->string('gender')->nullable();
            if (!Schema::hasColumn('users', 'pic')) $table->text('pic')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'addr', 'gender', 'pic']);
        });
    }
};

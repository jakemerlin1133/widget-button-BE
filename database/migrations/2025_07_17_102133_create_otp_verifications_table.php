<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('otp_verifications', function (Blueprint $table) {
        $table->id();
        $table->string('phone');
        $table->string('otp');
        $table->string('ip_address');
        $table->timestamp('expires_at');
        $table->integer('attempts')->default(0);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};

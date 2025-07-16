<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->string('status'); // ongoing, completed, terminated
            $table->json('face_verification_logs')->nullable(); // menyimpan log verifikasi wajah
            $table->json('suspicious_activities')->nullable(); // menyimpan aktivitas mencurigakan
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('exam_sessions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->text('answer_text');
            $table->integer('points_earned')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('answers');
    }
};

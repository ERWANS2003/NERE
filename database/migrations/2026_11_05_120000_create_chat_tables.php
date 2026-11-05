<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_rooms', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('type', 20)->index();
            $table->foreignId('department_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('chat_room_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chat_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['chat_room_id', 'user_id']);
        });

        Schema::create('chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chat_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['chat_room_id', 'id']);
            $table->index(['chat_room_id', 'updated_at']);
            $table->index('created_at');
        });

        Schema::create('chat_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chat_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('last_read_message_id')->default(0);
            $table->timestamps();

            $table->unique(['chat_room_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_reads');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_room_members');
        Schema::dropIfExists('chat_rooms');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->foreignId('reader_id')->constrained()->onDelete('cascade');
            $table->timestamp('loan_date');
            $table->timestamp('due_date');
            $table->timestamp('return_date')->nullable();
            $table->string('status')->default('borrowed'); // e.g., 'borrowed', 'returned', 'overdue'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};

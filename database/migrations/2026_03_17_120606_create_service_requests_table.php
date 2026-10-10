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
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'on-hold', 'revoked', 'completed', 'entered-in-error', 'unknown'])->default('active');
            $table->enum('intent', ['proposal', 'plan', 'order', 'original-order', 'reflex-order', 'filler-order', 'instance-order', 'option'])->default('order');
            $table->enum('priority', ['routine', 'urgent', 'asap', 'stat'])->default('routine');
            $table->string('category')->nullable()->comment('laboratory, imaging, etc.');
            $table->string('code')->nullable()->comment('LOINC or other code');
            $table->text('display')->nullable();
            $table->text('reason_code')->nullable();
            $table->text('reason_text')->nullable();
            $table->text('note')->nullable();
            $table->date('occurrence_date')->nullable();
            $table->date('authored_on')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index('category');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};

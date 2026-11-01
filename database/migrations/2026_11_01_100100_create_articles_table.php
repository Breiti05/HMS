<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('number', 50);
            $table->string('name');
            $table->string('unit', 20)->default('Stk');
            $table->decimal('min_stock', 12, 3)->default(0);
            $table->decimal('reorder_qty', 12, 3)->nullable();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'number']);
            $table->unique(['tenant_id', 'id']); // Ziel für mandantengebundene Fremdschlüssel
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('storage_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->unsignedSmallInteger('row')->default(1);
            $table->unsignedSmallInteger('col')->default(1);
            $table->decimal('capacity', 12, 3)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'warehouse_id', 'code']);
        });

        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('storage_location_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->timestamps();

            $table->unique(['article_id', 'storage_location_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('storage_location_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->string('type', 20); // receipt, issue, site_issue, transfer_in, transfer_out
            $table->decimal('quantity', 12, 3);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'article_id']);
        });

        Schema::create('kanban_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('open'); // open, ordered, delivered
            $table->decimal('quantity', 12, 3)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['kanban_cards', 'stock_movements', 'stock_levels', 'storage_locations', 'warehouses'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Jede Beziehung zwischen mandantengebundenen Tabellen läuft über
 * (tenant_id, id). Die Datenbank selbst verhindert damit, dass ein Datensatz
 * auf einen Datensatz eines anderen Betriebs zeigt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('type', 20)->default('main'); // main | vehicle
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'id']);
        });

        Schema::create('storage_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('code', 50);
            $table->unsignedSmallInteger('row')->default(1);
            $table->unsignedSmallInteger('col')->default(1);
            $table->decimal('capacity', 12, 3)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'warehouse_id', 'code']);
            $table->unique(['tenant_id', 'id']);
            $table->foreign(['tenant_id', 'warehouse_id'])->references(['tenant_id', 'id'])->on('warehouses')->restrictOnDelete();
        });

        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('article_id');
            $table->unsignedBigInteger('storage_location_id');
            $table->decimal('quantity', 12, 3)->default(0);
            $table->timestamps();

            $table->unique(['article_id', 'storage_location_id']);
            $table->index(['tenant_id', 'article_id']);
            $table->index(['tenant_id', 'storage_location_id']);
            $table->foreign(['tenant_id', 'article_id'])->references(['tenant_id', 'id'])->on('articles')->restrictOnDelete();
            $table->foreign(['tenant_id', 'storage_location_id'])->references(['tenant_id', 'id'])->on('storage_locations')->restrictOnDelete();
        });

        Schema::create('kanban_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('article_id');
            $table->string('status', 20)->default('open'); // open, ordered, delivered
            $table->decimal('quantity', 12, 3)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->foreign(['tenant_id', 'article_id'])->references(['tenant_id', 'id'])->on('articles')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['kanban_cards', 'stock_levels', 'storage_locations', 'warehouses'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

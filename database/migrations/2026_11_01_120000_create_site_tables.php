<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('number', 50);
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('status', 20)->default('active'); // active | done
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'number']);
            $table->unique(['tenant_id', 'id']);
        });

        Schema::create('site_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('site_id');
            $table->string('title');
            $table->dateTime('done_at')->nullable();
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'site_id']);
            $table->foreign(['tenant_id', 'site_id'])->references(['tenant_id', 'id'])->on('sites')->restrictOnDelete();
        });

        Schema::create('site_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('article_id');
            $table->unsignedBigInteger('storage_location_id');
            $table->decimal('quantity', 12, 3);
            $table->char('token', 32)->unique();
            $table->dateTime('loaded_at')->nullable();
            $table->foreignId('loaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'site_id', 'loaded_at']);
            $table->foreign(['tenant_id', 'site_id'])->references(['tenant_id', 'id'])->on('sites')->restrictOnDelete();
            $table->foreign(['tenant_id', 'article_id'])->references(['tenant_id', 'id'])->on('articles')->restrictOnDelete();
            $table->foreign(['tenant_id', 'storage_location_id'])->references(['tenant_id', 'id'])->on('storage_locations')->restrictOnDelete();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('article_id');
            $table->unsignedBigInteger('storage_location_id');
            $table->unsignedBigInteger('site_id')->nullable();
            $table->string('type', 20); // receipt, issue, site_issue, transfer_in, transfer_out
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->uuid('transfer_group')->nullable();
            $table->nullableMorphs('source'); // später: Bestellposition, Lieferschein, Baustellenmaterial
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'article_id']);
            $table->index(['tenant_id', 'storage_location_id']);
            $table->index(['tenant_id', 'site_id']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('transfer_group');
            $table->foreign(['tenant_id', 'article_id'])->references(['tenant_id', 'id'])->on('articles')->restrictOnDelete();
            $table->foreign(['tenant_id', 'storage_location_id'])->references(['tenant_id', 'id'])->on('storage_locations')->restrictOnDelete();
            $table->foreign(['tenant_id', 'site_id'])->references(['tenant_id', 'id'])->on('sites')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['stock_movements', 'site_materials', 'site_tasks', 'sites'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

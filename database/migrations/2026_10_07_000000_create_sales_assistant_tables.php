<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_records', function (Blueprint $t) {
            $t->id();
            $t->string('customer_email')->nullable();
            $t->decimal('total', 12, 2);
            $t->date('sold_at');
            $t->timestamps();
        });
        Schema::create('inventory_items', function (Blueprint $t) {
            $t->id();
            $t->string('sku')->unique();
            $t->string('name');
            $t->unsignedInteger('quantity')->default(0);
            $t->timestamps();
        });
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('company')->nullable();
            $t->timestamps();
        });
        Schema::create('knowledge_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->text('content');
            $t->json('embedding')->nullable();
            $t->timestamps();
        });
        Schema::create('ai_usages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('conversation_id')->nullable();
            $t->unsignedInteger('input_tokens')->default(0);
            $t->unsignedInteger('output_tokens')->default(0);
            $t->decimal('cost', 10, 6)->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usages');
        Schema::dropIfExists('knowledge_documents');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('sales_records');
    }
};

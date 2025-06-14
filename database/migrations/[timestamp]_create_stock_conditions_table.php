<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('bean_type');
            $table->integer('quantity');
            $table->decimal('temperature', 8, 2);
            $table->decimal('humidity', 8, 2);
            $table->string('status');
            $table->string('location');
            $table->string('air_condition');
            $table->text('action_taken')->nullable();
            $table->timestamp('last_updated');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_conditions');
    }
};

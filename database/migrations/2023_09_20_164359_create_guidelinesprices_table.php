<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuidelinespricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('guidelinesprices', function (Blueprint $table) {
            $table->id();
            $table->integer('totalsum')->nullable();
            $table->integer('bpt_price')->nullable();
            $table->integer('cam_discount')->nullable();
            $table->integer('dayscost')->nullable();
            $table->string('deliverymethod')->nullable();
            $table->integer('delivery_cost')->nullable();
            $table->integer('interest_rate')->nullable();
            $table->integer('misc_expense')->nullable();
            $table->integer('price_premium')->nullable();
            $table->integer('price_premium_sing')->nullable();
            $table->string('rfq_no')->nullable();
            $table->string('schldId')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('guidelinesprices');
    }
}

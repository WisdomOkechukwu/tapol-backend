<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransferRecipientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfer_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id')->nullable();
            $table->string('recipient_code')->nullable();
            $table->string('fullname')->nullable();
            $table->string('accountnumber')->nullable();
            $table->string('bankcode')->nullable();
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
        Schema::dropIfExists('transfer_recipients');
    }
}

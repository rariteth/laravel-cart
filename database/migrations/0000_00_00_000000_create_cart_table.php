<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShoppingcartTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create(config('cart.database.table'), function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('identifier');
        
            $table->string('instance', 20);
            $table->string('guard', 20);
        
            $table->longText('content');
        
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        
            $table->unique(['identifier', 'instance', 'guard']);
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::drop(config('cart.database.table'));
    }
}

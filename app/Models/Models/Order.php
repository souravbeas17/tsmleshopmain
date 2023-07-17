<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
     protected $table = "orders";

     protected $fillable = ['po_no','cus_po_no','rfq_no','sche','type','status','letterhead','po_date','created_at','updated_at'];
}

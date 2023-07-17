<?php

namespace App\Http\Controllers\Api\Modules\Quote;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Models\Quote;
use App\Models\Models\QuoteSchedule;
use App\Models\Models\ProductSubCategory;
use App\Models\Models\Quotedelivery;
use App\Models\Models\Requote;
use App\Models\Models\Order;
use App\Models\Models\Deleteremark;
use App\Models\Models\Plant;
use App\Models\Models\DeliveryMethod;
use App\Mail\Models\RfqGeneratedMail;
use App\Http\Controllers\Api\Modules\QuoteEmail\QuoteEmailController;
use App\Models\User;
use Validator;
use Auth;
use DB;
use \PDF;
use Mail;
use Nullix\CryptoJsAes\CryptoJsAes;
use File; 
use Storage;

class PoeditController extends Controller
{
      
     public function poEditMultiple(Request $request){
                   // echo "<pre>";print_r($request->all());exit();

       try{

      //   $encrypted = json_encode($request->all());
      //   // $json = json_encode($encrypted1);
      // $password = "123456";

      // $decrypted = CryptoJsAes::decrypt($encrypted, $password);
      // dd($decrypted);

      foreach ($request->all() as $key => $value) {
      	   
      	   // dd($value['pro_size']);
      	if(!empty($value['id']))
      	{
      	   $arr['pro_size'] = $value['pro_size'];
      	   $arr['quantity'] = $value['quantity'];
      	   $arr['location'] = $value['location'];
      	   $arr['bill_to'] = $value['bill_to'];
      	   $arr['ship_to'] = $value['ship_to'];
      	   $arr['to_date'] = $value['to_date'];
      	   $arr['from_date'] = $value['from_date'];
      	   $arr['remarks'] = $value['remarks'];
      	   $arr['kamsRemarks'] = $value['kamsRemarks'];
      	   $arr['expected_price'] = $value['expected_price'];
      	   $arr['delivery'] = $value['delivery'];
      	   $arr['plant'] = $value['plant'];
      	   $arr['valid_till'] = $value['valid_till'];
      	   $arr['sub_cat_id'] = $value['sub_cat_id'];


      	   // dd($value['id']);
          
           QuoteSchedule::where('id',$value['id'])->update($arr);
       }
       else{
           
           $arr['quote_id'] = $value['quote_id'];
           $arr['schedule_no'] = $value['schedule_no'];
       	   $arr['pro_size'] = $value['pro_size'];
      	   $arr['quantity'] = $value['quantity'];
      	   $arr['location'] = $value['location'];
      	   $arr['bill_to'] = $value['bill_to'];
      	   $arr['ship_to'] = $value['ship_to'];
      	   $arr['to_date'] = $value['to_date'];
      	   $arr['from_date'] = $value['from_date'];
      	   $arr['remarks'] = $value['remarks'];
      	   $arr['kamsRemarks'] = $value['kamsRemarks'];
      	   $arr['expected_price'] = $value['expected_price'];
      	   $arr['delivery'] = $value['delivery'];
      	   $arr['plant'] = $value['plant'];
      	   $arr['valid_till'] = $value['valid_till'];
      	   $arr['sub_cat_id'] = $value['sub_cat_id'];
      	   $arr['kam_price'] = $value['kam_price'];
      	   $arr['pickup_type'] = $value['pickup_type'];
      	   $arr['pay_term'] = 'Advance Payment';
      	   $arr['quote_status'] = 1;

      	   QuoteSchedule::create($arr);
       }

      }
          


        
        return response()->json(['status'=>1,
          'message' =>'success',
          'result' => 'PO updated'],
          config('global.success_status'));
          

      }catch(\Exception $e){

       return response()->json(['status'=>0,'message' =>'error','result' => $e->getMessage()],config('global.failed_status'));
     }
 }


      public function poUpdateSche(Request $request){

         // echo "<pre>";print_r($request->allsche);exit();

	       try{

                  $sche['sche'] = $request->allsche;

                  $po = $request->po_no;

                  Order::where('po_no',$po)->update($sche);
	        
	        return response()->json(['status'=>1,
	          'message' =>'success',
	          'result' => 'PO updated'],
	          config('global.success_status'));
	          

	      }catch(\Exception $e){

	       return response()->json(['status'=>0,'message' =>'error','result' => $e->getMessage()],config('global.failed_status'));
	     }
      }
}

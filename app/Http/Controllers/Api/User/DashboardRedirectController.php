<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Jobs\UserCreated;
use App\Models\Models\Order;
use App\Models\Models\Quote;
use Illuminate\Support\Facades\Hash;
use App\ServicesMy\MailService;
use JWTAuth;
use Validator;
use Response;
use Mail;
use DB;
use Auth;
use Nullix\CryptoJsAes\CryptoJsAes;

class DashboardRedirectController extends Controller
{
     
	 /**
    * Display a listing of the customers.
    *
    * @return \Illuminate\Http\Response
    */
   public function dashCamCusList($id)
   {
      try{
   	    $userid = $id;

   		$getuser = User::where('id',$userid)->first();


	        $customer = DB::table('users')  
	            ->where('users.zone',$getuser->zone)
	            ->where('users.user_type','C') 
	            ->get();
	             
	        // $data['total_no_cust_assinged'] = $userzone;

	            foreach ($customer as $key => $value) {
	            	 
	            	  $arr[$key]['user_code'] = $value->user_code;
	            	  $arr[$key]['org_name'] = $value->org_name;
	            	  $arr[$key]['email'] = $value->email;
	            	  $arr[$key]['reg_by'] = $value->reg_by;

	            }

	         // dd($customer);
	        return response()->json(['status'=>1,
          'message' =>'success',
          'result' => $arr],
          config('global.success_status'));
          // }
          // else{

          //    return response()->json(['status'=>1,
           //       'message' =>'success',
           //       'result' => 'Quote not created'],
           //       config('global.success_status'));

          // }

      }catch(\Exception $e){

       return response()->json(['status'=>0,'message' =>'error','result' => $e->getMessage()],config('global.failed_status'));
     }
	        

   }
}

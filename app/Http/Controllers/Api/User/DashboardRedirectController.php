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


	            foreach ($customer as $key => $value) {
	            	 
	            	  $arr[$key]['user_code'] = $value->user_code;
	            	  $arr[$key]['org_name'] = $value->org_name;
	            	  $arr[$key]['email'] = $value->email;
	            	  $arr[$key]['phone'] = $value->phone;
	            	  $arr[$key]['reg_by'] = $value->reg_by;

	            }

	         // dd($customer);
	        return response()->json(['status'=>1,
          'message' =>'success',
          'result' => $arr],
          config('global.success_status'));


      }catch(\Exception $e){

       return response()->json(['status'=>0,'message' =>'error','result' => $e->getMessage()],config('global.failed_status'));
     }
	        

   }




   	 /**
    * Display a listing of the confirmed volume.
    *
    * @return \Illuminate\Http\Response
    */
   public function dashboardVolCon($id)
   {
      try{

      	  $getuser = User::where('id',$id)->first();

   		 if ( date('m') <= 03 ) {
   		 		$preyear = date("Y",strtotime("-1 year"));
   		 		$fromdate = $preyear.'-'.'04'.'-'.'01';
			    $todate = date("Y-m-d");
			}
			else {
				$year = date("Y");
			    $fromdate = $year.'-'.'04'.'-'.'01';
			    $todate = date("Y-m-d");
			}
   	       	// dd($fromdate,$todate);
   		 	// Show data according to financial year.....
   		 	

            if($getuser->user_type == 'PLANT')
            {
	         $orgname = $getuser->org_name;
            
   		 	// dd($orgname);
   		 	$plantId = DB::table('plants')->where('name',$orgname)->first();
   		 	$getsono = DB::table('sc_excel_datas')->where('Plant',$plantId->code)->get();
   		 	// dd($plantId->code,$getsono);
 	 	 
   		 	$volumeCon = DB::table('quote_schedules')
   		 					->leftjoin('quotes','quote_schedules.quote_id','quotes.id')
   		 					->leftjoin('sub_categorys','quote_schedules.sub_cat_id','sub_categorys.id')
   		 					->leftjoin('users','quotes.user_id','users.id')
   		 					->where('quote_schedules.plant',$orgname)
   		 					->where('quotes.kam_status',4)
				            ->whereDate('quotes.created_at','>=', $fromdate)
			                ->whereDate('quotes.created_at','<=', $todate) 
				            ->whereNull('quotes.deleted_at') 
				            ->select('quote_schedules.quantity','quotes.rfq_no','users.org_name','sub_categorys.sub_cat_name') 
				            ->get()->toArray();	      
			 
	        
	       	$data['plantvolumeconfirmed'] = $volumeCon;

	       	$rfqNego = DB::table('quote_schedules')
   		 					->leftjoin('quotes','quote_schedules.quote_id','quotes.id')
   		 					->leftjoin('sub_categorys','quote_schedules.sub_cat_id','sub_categorys.id')
   		 					->leftjoin('users','quotes.user_id','users.id')
   		 					->where('quote_schedules.plant',$orgname)
   		 					->where('quotes.kam_status',6)
				            ->whereDate('quotes.created_at','>=', $fromdate)
			                ->whereDate('quotes.created_at','<=', $todate) 
				            ->whereNull('quotes.deleted_at') 
				            ->select('quote_schedules.quantity','quotes.rfq_no','users.org_name','sub_categorys.sub_cat_name') 
				            ->get()->toArray();

			$data['rfq_under_negotiation'] = $rfqNego;

			$exworkcons = DB::table('quote_schedules')
   		 					->leftjoin('quotes','quote_schedules.quote_id','quotes.id')
   		 					->leftjoin('sub_categorys','quote_schedules.sub_cat_id','sub_categorys.id')
   		 					->leftjoin('users','quotes.user_id','users.id') 
   		 					->where('quote_schedules.plant',$orgname)
   		 					->where('quotes.kam_status',4)
   		 					->where('quote_schedules.delivery','Ex-Works')
				            ->whereDate('quotes.created_at','>=', $fromdate)
			                ->whereDate('quotes.created_at','<=', $todate) 
				            ->whereNull('quotes.deleted_at') 
				            ->select('quote_schedules.quantity','quotes.rfq_no','users.org_name','sub_categorys.sub_cat_name') 
				            ->get()->toArray();

			$data['ex_work_confirmed_orders'] = $exworkcons;
		}
		else{

			$volumeCon = DB::table('quotes')
   		 	->leftjoin('users','quotes.user_id','users.id')
   		 	 	->select('quotes.quantity','quotes.rfq_no','users.org_name') 
	            ->where('quotes.kam_status',4)
	            ->whereDate('quotes.created_at','>=', $fromdate)
                ->whereDate('quotes.created_at','<=', $todate) 
	            ->whereNull('quotes.deleted_at') 
	            ->get()->toarray();	         
	        $data['plantvolumeconfirmed'] = $volumeCon;

	        $volumeUnderNego = DB::table('quotes')
	        ->leftjoin('users','quotes.user_id','users.id')
   		 	 	->select('quotes.quantity','quotes.rfq_no','users.org_name') 
	            ->where('quotes.kam_status',6) 
	            ->whereDate('quotes.created_at','>=', $fromdate)
                ->whereDate('quotes.created_at','<=', $todate)
	            ->whereNull('quotes.deleted_at')
	            // ->groupBy('rfq_no')
	            ->get()->toarray();	         
	        $data['rfq_under_negotiation'] = $volumeUnderNego;
 			

    
            
            $exdepot = array();
	        $getdepotrfqno = DB::table('quotes')
	        	->select('quotes.id')
	            ->where('quotes.kam_status',4)
	            ->whereDate('quotes.created_at','>=', $fromdate)
                ->whereDate('quotes.created_at','<=', $todate)
	            ->whereNull('quotes.deleted_at')
	            ->groupBy('rfq_no')
	            ->get(); 

	            foreach ($getdepotrfqno as $key => $value) {
	            	
                     array_push($exdepot,$value->id);
	            }
	            
	            	$getdepotrfq = DB::table('quote_schedules')
	            	->leftjoin('quotes','quote_schedules.quote_id','quotes.id')
	            	->leftjoin('sub_categorys','quote_schedules.sub_cat_id','sub_categorys.id')
	            	->leftjoin('users','quotes.user_id','users.id')
	            	->select('quote_schedules.quantity','quotes.rfq_no','users.org_name','sub_categorys.sub_cat_name')
	            	->whereIn('quote_schedules.quote_id',$exdepot)
		            ->where('quote_schedules.pickup_type','=','DEPOT') 
		            ->get()->toarray(); 
		            
		         

	            	$getqutsedno = DB::table('quote_schedules')
	            	->leftjoin('quotes','quote_schedules.quote_id','quotes.id')
	            	->leftjoin('sub_categorys','quote_schedules.sub_cat_id','sub_categorys.id')
	            	->leftjoin('users','quotes.user_id','users.id')
	            	->select('quote_schedules.quantity','quotes.rfq_no','users.org_name','sub_categorys.sub_cat_name')
	            	->whereIn('quote_schedules.quote_id',$exdepot)
		            ->where('quote_schedules.pickup_type','=','PLANT') 
		            ->get()->toarray();
		            
		            	            	
	        $data['ex_plant_confirmed_orders'] = $getqutsedno;
	            
	            	         
	        $data['ex_Depot_confirmed_orders'] = $getdepotrfq;


        	$getqutsedno = DB::table('quote_schedules')
        	->leftjoin('quotes','quote_schedules.quote_id','quotes.id')
        	->leftjoin('sub_categorys','quote_schedules.sub_cat_id','sub_categorys.id')
        	->leftjoin('users','quotes.user_id','users.id')
        	->select('quote_schedules.quantity','quotes.rfq_no','users.org_name','sub_categorys.sub_cat_name')
        	->whereIn('quote_schedules.quote_id',$exdepot)
            ->where('quote_schedules.delivery','=','DAP (Delivered at Place)') 
            ->get()->toarray();
	            	         
	        $data['DAP_confirmed_orders'] = $getqutsedno;
		}

	        // End of Show data according to financial year.....


	         // dd($customer);
	        return response()->json(['status'=>1,
          'message' =>'success',
          'result' => $data],
          config('global.success_status'));


      }catch(\Exception $e){

       return response()->json(['status'=>0,'message' =>'error','result' => $e->getMessage()],config('global.failed_status'));
     }
	        

   }
}

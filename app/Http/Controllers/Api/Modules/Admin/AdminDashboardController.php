<?php

namespace App\Http\Controllers\Api\Modules\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\OtpVerification;
use App\Mail\Register;
use App\Models\User;
use App\Jobs\UserCreated;
use App\Models\Models\Order;
use App\Models\Models\Quote;
use App\Models\Models\QuoteSchedule;
use Illuminate\Support\Facades\Hash;
use App\Models\Models\RegistrationLog;
use App\ServicesMy\MailService;
use JWTAuth;
use Validator;
use Response;
use Mail;
use DB;
use Nullix\CryptoJsAes\CryptoJsAes;

class AdminDashboardController extends Controller
{
   /**
     * This for admin dashboard.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
   	public function adminDashboard(Request $request)
   	{ 

   		try{         
            $getuser = User::get();

            $data['totalcust'] = 0;
	        $data['totalkam'] = 0;
	        $data['totalsalsplan'] = 0;
	        $data['totalsalsman'] = 0;
	        $data['totalsalsopt'] = 0;
	        $data['totalsalsplant'] = 0;

	   		foreach ($getuser as $key => $value) 
	   		{
	   			if ($value->user_type=='C') 
		   		{
					$data['totalcust']+= 1;
		   		}
		   		elseif ($value->user_type=='Kam') 
		   		{
					$data['totalkam']+= 1;
		   		}
		   		elseif ($value->user_type=='Sales') 
		   		{
					$data['totalsalsplan']+= 1;
		   		}
		   		elseif ($value->user_type=='SM') 
		   		{
					$data['totalsalsman']+= 1;
		   		}
		   		elseif ($value->user_type=='OPT') 
		   		{
					$data['totalsalsopt']+= 1;
		   		}
		   		elseif ($value->user_type=='PLANT') 
		   		{
					$data['totalsalsplant']+= 1;
		   		}

	   		}
            

            // ----------------------- graphs ----------------------------------------
            if(!empty($request->finyear))
            {
               $curryear = $request->finyear;
            }else{
               $curryear = date('Y');
           }
            $newcurryr = '01-01-'.$curryear;
            $nxtyear = date('Y', strtotime('+1 year', strtotime($newcurryr)));
            // dd($nxtyear);
            $fromdate = $curryear.'-'.'04-01';
            $todate = $nxtyear.'-'.'03-31';

            $rfq = Quote::wheredate('created_at','>=',$fromdate)->wheredate('created_at','<=',$todate)->get();
            $orders = Order::wheredate('created_at','>=',$fromdate)->wheredate('created_at','<=',$todate)->count();

	   		$data['rfq'] = count($rfq);
	   		$data['orders'] = $orders;
            
            $year = [$curryear.'-'.'04',$curryear.'-'.'05',$curryear.'-'.'06',$curryear.'-'.'07',$curryear.'-'.'08',$curryear.'-'.'09',$curryear.'-'.'10',$curryear.'-'.'11',$curryear.'-'.'12',$nxtyear.'-'.'01',$nxtyear.'-'.'02',$nxtyear.'-'.'03'];

         // ------------------------------ rfq graph ----------------------------------------------
	   		foreach ($year as $key => $value) {

	   			  $fdate = $value.'-01';
	   			  $tdate = $value.'-31';

	   			  $rfq = QuoteSchedule::wheredate('created_at','>=',$fdate)->wheredate('created_at','<=',$tdate)->sum('quantity');
	   			  // dd($fdate,$tdate);
	   			  $arr[$key]['month'] = date("F", strtotime($fdate));
	   			  $arr[$key]['rfq'] = $rfq;
	   		}

	   		$data['monthly_rfq'] = $arr;
	   	// ---------------------------------------------------------------------------------

	   	// ------------------------------ po graph ----------------------------------------------
	   		foreach ($year as $key => $value) {
                  
                  $poarr =  array();
	   			  $fdate = $value.'-01';
	   			  $tdate = $value.'-31';

	   			  // $po = Order::wheredate('created_at','>=',$fdate)->wheredate('created_at','<=',$tdate)->count();
                  $sum = 0;
	   			  $po = DB::table('orders')
			           ->leftjoin('quotes','orders.rfq_no','quotes.rfq_no')
			           ->leftjoin('quote_schedules','quotes.id','quote_schedules.quote_id')
			           ->select('quote_schedules.quantity','quote_schedules.id','orders.po_no')
			           ->wheredate('orders.created_at','>=',$fdate)->wheredate('orders.created_at','<=',$tdate)
			           // ->groupBy('orders.rfq_no')
			           ->whereNull('quotes.deleted_at')
			           ->where('quote_schedules.quote_status',1)
			           // ->get();
                       ->sum('quote_schedules.quantity');
                       // dd($po);
	   			  $order[$key]['month'] = date("F", strtotime($fdate));
	   			  $order[$key]['order'] = $po;

	   			  
	   		}
            // dd($order);
	   		$data['monthly_po'] = $order;
	   	// ---------------------------------------------------------------------------------

	    // ------------------------------- zone wise po graph--------------------------------
          
           		foreach ($year as $key => $value) {
                  
                  $zones = ['East','West','North','South'];
                  $poarr =  array();
	   			  $fdate = $value.'-01';
	   			  $tdate = $value.'-31';

	   			  // $po = Order::wheredate('created_at','>=',$fdate)->wheredate('created_at','<=',$tdate)->count();
                  $sum = 0;

                  foreach ($zones as $k => $v) {
                  	# code...
                  
	   			  $posum = DB::table('orders')
			           ->leftjoin('quotes','orders.rfq_no','quotes.rfq_no')
			           ->leftjoin('users','quotes.user_id','users.id')
			           ->leftjoin('quote_schedules','quotes.id','quote_schedules.quote_id')
			           ->select('quote_schedules.quantity','quote_schedules.id','orders.po_no')
			           ->wheredate('orders.created_at','>=',$fdate)->wheredate('orders.created_at','<=',$tdate)
			           // ->groupBy('orders.rfq_no')
			           ->whereNull('quotes.deleted_at')
			           ->where('quote_schedules.quote_status',1)->where('users.zone',$v)
			           // ->get();
                       ->sum('quote_schedules.quantity');
                       // dd($po);
	   			  
	   			  $zone[$key][$v] = $posum;
	   			}

	   			  $zone[$key]['month'] = date("F", strtotime($fdate));

	   		}
            // dd($order);
	   		$data['zone_po'] = $zone;

			  
	   // ---------------------------------------------------------------------------------

	 // ------------ Map on the basis of delivery type -------------------------------------------
	 $delivery_type = ['ExWorks', 'DAP'];
	 $delivery = array();
	 
	foreach ($year as $key => $value) {
	 $fdate = $value.'-01';
	 $tdate = $value.'-31';
	 foreach ($delivery_type as $k => $v) {
			 $dosum = DB::table('orders')
					->leftjoin('quotes','orders.rfq_no','quotes.rfq_no')
					->leftjoin('users','quotes.user_id','users.id')
					->leftjoin('quote_schedules','quotes.id','quote_schedules.quote_id')
					->select('quote_schedules.quantity','quote_schedules.id','orders.po_no')
					->wheredate('orders.created_at','>=',$fdate)->wheredate('orders.created_at','<=',$tdate)
					// ->groupBy('orders.rfq_no')
					->whereNull('quotes.deleted_at')
					->where('quote_schedules.quote_status',1)->where('quote_schedules.delivery','LIKE','%'.$v.'%')
					// ->get();
					->sum('quote_schedules.quantity');
			 $delivery[$key][$v] = $dosum ;

		 }
		 $delivery[$key]['month'] = date("F", strtotime($fdate));
	}
	$data['delivery_po'] = $delivery;
 
	
	// ------------------------------------------------------------------------------------------
    
            return response()->json(['status'=>1,'message' =>'success.','result' => $data],200);
             // return response()->json(['status'=>1,$response],200);
              
            
            }catch(\Exception $e){
                $response['error'] = $e->getMessage();
                return response()->json([$response]);
            }
   		

   	}
}

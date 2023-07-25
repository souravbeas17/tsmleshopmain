<?php

namespace App\Http\Controllers\Api\User;

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
use Illuminate\Support\Facades\Hash;
use App\Models\Models\RegistrationLog;
use App\ServicesMy\MailService;
use JWTAuth;
use Validator;
use Response;
use Mail;
use DB;
use Nullix\CryptoJsAes\CryptoJsAes;
use DateTime;
use DateInterval;
use DatePeriod;

class DashboardController extends Controller
{
    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
   public function userDashboard(Request $request)
   {
   		  	
   	 //  	echo 'First Date    = ' . date('Y-m-01') . '<br />';
    	// echo 'Last Date     = ' . date('Y-m-t')  . '<br />';
    	// exit();
   		$userid = $request->user_id;

   		$getuser = User::where('id',$userid)->first();

   		// dd($getuser);
   		// C -- Customer
   		// Kam -- cam

   		$todate = date("Y-m-d");

   		if( $request->graph == 'yearly' ){
   			if ($request->fromdatem && $request->todatem) 
			{
				$fromdate = $request->fromdatem;
				$todate = $request->todatem;
			}else{
			    $fromdate = date("Y-04-01"); 
			}

   		}elseif( $request->graph == 'monthly' ){
   			if ($request->fromdatem && $request->todatem) 
			{
				$fromdate = $request->fromdatem;
				$todate = $request->todatem;
			}else{
				$fromdate = date("Y-m-01");
			}
   		}else{
   			if ( date('m') <= 03 ) {
		 		$preyear = date("Y",strtotime("-1 year"));
		 		$fromdate = date($preyear."-04-01");
			}
			else{
			    $fromdate = date("Y-04-01"); 
			}
			$todate = date("Y-m-t");
   		}


   		//dd($fromdate,$todate);

   		 if ($getuser->user_type == 'C') {
   		 	$quote = DB::table('orders')
            ->leftjoin('quotes','orders.rfq_no','quotes.rfq_no')            
            ->where('quotes.user_id',$userid)
            ->whereDate('quotes.created_at','>=', $fromdate)
			->whereDate('quotes.created_at','<=', $todate) 
            ->whereNull('quotes.deleted_at')
            ->count();
	        $data['total_no_of_orders'] = $quote;

	         $rfqNego = DB::table('quotes') 
	            ->where('quotes.user_id',$userid)
	            ->where('quotes.kam_status',6)
	            ->whereDate('quotes.created_at','>=', $fromdate)
			    ->whereDate('quotes.created_at','<=', $todate) 
	            ->whereNull('quotes.deleted_at')
	            ->count();
	        $data['rfq_under_negotiation'] = $rfqNego;

	        $orderCon = DB::table('orders')
	            ->leftjoin('quotes','orders.rfq_no','quotes.rfq_no')             
	            ->where('quotes.user_id',$userid)
	            ->whereDate('quotes.created_at','>=', $fromdate)
			    ->whereDate('quotes.created_at','<=', $todate)
	            ->where('orders.status',1)
	            ->whereNull('quotes.deleted_at')
	            ->count();
	        $data['orders_confirmed_pending_for_delivery'] = $orderCon; 
	       

	        $custComplain = DB::table('complain_main') 
	            ->where('complain_main.user_id',$userid)
	            ->where('complain_main.closed_status',1) 
	            ->count();

	        $data['Total_no_of_open_complaints'] = $custComplain;
   		 }

   		 else if ($getuser->user_type == 'PLANT') {
   		 	// dd('PLANT');
   		 	$orgname = $getuser->org_name;
   		 	// dd($orgname);
   		 	$plantId = DB::table('plants')->where('name',$orgname)->first();
   		 	$getsono = DB::table('sc_excel_datas')->where('Plant',$plantId->code)->get();
   		 	// dd($plantId->code,$getsono);
   		 	$doquentysum = 0;
   		 	foreach ($getsono as $key => $oeder) 
   		 	{
   		 		# code...
   		 		$getdespatch = DB::table('delivery_orders')->where('so_no',$oeder->ordr_no)->get();

   		 		
   		 		foreach ($getdespatch as $key => $doqty) 
   		 		{
   		 			$doquentysum+= $doqty->do_quantity;
   		 		}
   		 	}

   		 	$data['qty_despatch'] = $doquentysum;
   		 	 
   		 	$volumeCon = DB::table('quote_schedules')
   		 					->leftjoin('quotes','quote_schedules.quote_id','quotes.id')
   		 					->where('quote_schedules.plant',$orgname)
   		 					->where('quotes.kam_status',4)
				            ->whereDate('quotes.created_at','>=', $fromdate)
			                ->whereDate('quotes.created_at','<=', $todate) 
				            ->whereNull('quotes.deleted_at') 
				            ->select('quote_schedules.id') 
				            ->sum('quote_schedules.quantity');	      
			 
	        
	       	$data['volumeconfirmed'] = $volumeCon;

	       	$rfqNego = DB::table('quote_schedules')
   		 					->leftjoin('quotes','quote_schedules.quote_id','quotes.id')
   		 					->where('quote_schedules.plant',$orgname)
   		 					->where('quotes.kam_status',6)
				            ->whereDate('quotes.created_at','>=', $fromdate)
			                ->whereDate('quotes.created_at','<=', $todate) 
				            ->whereNull('quotes.deleted_at') 
				            ->select('quote_schedules.id') 
				            ->sum('quote_schedules.quantity');

			$data['rfq_under_negotiation'] = $rfqNego;

			$exworkcons = DB::table('quote_schedules')
   		 					->leftjoin('quotes','quote_schedules.quote_id','quotes.id') 
   		 					->where('quote_schedules.plant',$orgname)
   		 					->where('quotes.kam_status',4)
   		 					->where('quote_schedules.delivery','Ex-Works')
				            ->whereDate('quotes.created_at','>=', $fromdate)
			                ->whereDate('quotes.created_at','<=', $todate) 
				            ->whereNull('quotes.deleted_at') 
				            ->sum('quote_schedules.quantity');

			$data['ex_work_confirmed_orders'] = $exworkcons;


			$volumeConPlant = DB::table('quote_schedules')
   		 					->leftjoin('quotes','quote_schedules.quote_id','quotes.id')
   		 					->where('quote_schedules.plant',$orgname)
   		 					// ->where('quote_schedules.pickup_type','=',$plantId->type) 
   		 					->where('quotes.kam_status',4)
				            ->whereDate('quotes.created_at','>=', $fromdate)
			                ->whereDate('quotes.created_at','<=', $todate) 
				            ->whereNull('quotes.deleted_at') 
				            ->select('quote_schedules.id') 
				            ->sum('quote_schedules.quantity');	      
			 
	        
	       	$data['volumeConPlantMon'] = $volumeConPlant;


	       	$volumeConDap = DB::table('quote_schedules')
   		 					->leftjoin('quotes','quote_schedules.quote_id','quotes.id')
   		 					->where('quote_schedules.plant',$orgname)
   		 					->where('quote_schedules.delivery','=','DAP (Delivered at Place)') 
   		 					->where('quotes.kam_status',4)
				            ->whereDate('quotes.created_at','>=', $fromdate)
			                ->whereDate('quotes.created_at','<=', $todate) 
				            ->whereNull('quotes.deleted_at') 
				            ->select('quote_schedules.id') 
				            ->sum('quote_schedules.quantity');	      
			 
	        
	       	$data['volumeConDapMon'] = $volumeConDap;

	       	$volumeConPlanty = DB::table('quote_schedules')
   		 					->leftjoin('quotes','quote_schedules.quote_id','quotes.id')
   		 					->where('quote_schedules.plant',$orgname)
   		 					// ->where('quote_schedules.pickup_type','=',$plantId->type) 
   		 					->where('quotes.kam_status',4)
				            ->whereDate('quotes.created_at','>=', $fromdate)
			                ->whereDate('quotes.created_at','<=', $todate) 
				            ->whereNull('quotes.deleted_at') 
				            ->select('quote_schedules.id') 
				            ->sum('quote_schedules.quantity');	      
			 
	        
	       	$data['volumeConPlantYear'] = $volumeConPlanty;


	       	$volumeConDapy = DB::table('quote_schedules')
   		 					->leftjoin('quotes','quote_schedules.quote_id','quotes.id')
   		 					->where('quote_schedules.plant',$orgname)
   		 					->where('quote_schedules.delivery','=','DAP (Delivered at Place)') 
   		 					->where('quotes.kam_status',4)
				            ->whereDate('quotes.created_at','>=', $fromdate)
			                ->whereDate('quotes.created_at','<=', $todate) 
				            ->whereNull('quotes.deleted_at') 
				            ->select('quote_schedules.id') 
				            ->sum('quote_schedules.quantity');	      
			 
	        
	       	$data['volumeConDapYear'] = $volumeConDapy;
 
            $data['plant_type'] = $plantId->type;
	       	 
   		 }
	  	 
   		 else if ($getuser->user_type == 'Kam') {
   		 	$quote = DB::table('orders')
            ->leftjoin('quotes','orders.rfq_no','quotes.rfq_no')  
            ->leftjoin('users','quotes.user_id','users.id')           
            ->where('users.zone',$getuser->zone)
            ->whereDate('orders.created_at','>=', $fromdate)
            ->whereDate('orders.created_at','<=', $todate)
            ->whereNull('quotes.deleted_at')
            ->count();
	        $data['total_no_of_orders'] = $quote;

	        $userzone = DB::table('users')  
	            ->where('users.zone',$getuser->zone)
	            ->where('users.user_type','C') 
	            ->count();
	             
	        $data['total_no_cust_assinged'] = $userzone;
	        
	        $pendingcast = DB::table('users')  
	            ->where('users.zone',$getuser->zone)
	            ->where('users.user_type','C')
	            ->where('users.user_code','=',null) 
	            ->count();
	             
	        $data['total_no_cust_pending_for_approve'] = $pendingcast;
	        // dd($getuser->zone);
	        $orderCon = DB::table('orders')
	            ->leftjoin('quotes','orders.rfq_no','quotes.rfq_no')             
	            ->leftjoin('users','quotes.user_id','users.id')
	            ->whereDate('orders.created_at','>=', $fromdate)
            	->whereDate('orders.created_at','<=', $todate)
	            ->where('orders.status',1)
	            ->where('users.zone',$getuser->zone)
	            ->whereNull('quotes.deleted_at')
	            ->count();
	        $data['orders_confirmed_pending_for_delivery'] = $orderCon;

	        $rfqNego = DB::table('quotes') 
	            ->leftjoin('users','quotes.user_id','users.id')
	            ->whereDate('quotes.created_at','>=', $fromdate)
            	->whereDate('quotes.created_at','<=', $todate)
	            ->where('quotes.kam_status',6)
	            ->where('users.zone',$getuser->zone)
	            ->whereNull('quotes.deleted_at')
	            ->count();
	        $data['rfq_under_negotiation'] = $rfqNego;

	        $custComplain = DB::table('complain_main') 
	        	->leftjoin('users','complain_main.user_id','users.id')
	            ->where('users.zone',$getuser->zone)
	            ->whereDate('complain_main.created_at','>=', $fromdate)
            	->whereDate('complain_main.created_at','<=', $todate)
	            ->where('complain_main.closed_status',1) 
	            ->count();

	        $data['Total_no_of_open_complaints'] = $custComplain;


	        // ---------------- top 5 ytd cus ---------------------------

	        
            $ytddata = array();

	        $ytd = DB::table('orders')
	               ->leftjoin('quotes','orders.rfq_no','quotes.rfq_no')
	               ->leftjoin('users','quotes.user_id','users.id') 
	               ->select('users.org_name','users.id as custid','quotes.rfq_no','quotes.id') 
	               ->whereDate('orders.created_at','>=', $fromdate)
            	   ->whereDate('orders.created_at','<=', $todate)
	               ->where('users.zone',$getuser->zone)
	               ->whereNull('quotes.deleted_at')
	               ->where('orders.status',2)  
	               ->groupBy('orders.rfq_no')
	               ->get();

	        
	             // dd($ytd);

	        

	        $topfdive = array();
	        foreach ($ytd as $key => $values) {
	        	$ytddata =0;
	        	$ortherqua = DB::table('quote_schedules') 
	        	->select('quantity')
	               ->where('quote_id','=',$values->id) 	                
	               ->get(); 
                  // dd($ortherqua);
	               foreach ($ortherqua as $k => $v) { 
	               	  $ytddata += $v->quantity; 
	               	  // echo "<pre><br>";print($ortherqua);
	               }

	               // array_push($topfdive,$ytddata); 
	               $comp = $values->org_name;	

	               if (array_key_exists($comp,$topfdive))
					  {
					       $sum1 = $topfdive[$comp] + $ytddata;

					       $topfdive[$comp] = $sum1;
					  }
					else
					  {
					      $topfdive[$comp] = $ytddata;
					  }
	                  
	        }
	        // dd($topfdive);

	       
			arsort($topfdive);
			$largest = array_slice($topfdive, 0, 5, true);
			$rest = array_slice($topfdive,5);

			$rest_sum = array_sum($rest);
	          

			$tot_arra = array();

           $largest['others'] = $rest_sum;
		    // array_push($largest,$rest_sum);
		   // dd($largest,$rest_sum);exit(); 
		   // dd($largest);

             
	        
	            
	        $data['top_five_cust_sale'] = $largest; 

	        // monthly volume graph with average net price start
	        
	        if(date('m') > 4)
			{
				$calculate_fiscal_year_for_date = date('Y')."-04-01".":".date('Y', strtotime('+1 year'))."-03-31";
			}else{
				$calculate_fiscal_year_for_date = date('Y', strtotime('-1 year'))."-04-01".":".date('Y')."-03-31";
			}
			$financ_month = explode(':', $calculate_fiscal_year_for_date);
        	$start    = (new DateTime($fromdate))->modify('first day of this month');
        	if($request->todatem){
        		$end      = (new DateTime($todate))->modify('last day of this month');
        	}else{
        		$end      = (new DateTime($financ_month[1]))->modify('last day of this month');
        	}
			$interval = DateInterval::createFromDateString('1 month');
			$months   = new DatePeriod($start, $interval, $end);
        	$MonthlyAveragePriceMonthData = [];
        	foreach ($months as $k => $monthh) {
        		$month = $monthh->format("m");
        		$yearName = $monthh->format("Y");
        		$getUserMonthlyAveragePrice = DB::table('quotes') 
	            //->leftjoin('quote_schedules','quotes.id','quote_schedules.quote_id')
	            ->leftjoin('users','quotes.user_id','users.id')
	            ->select('quotes.id')
	            ->whereDate('quotes.created_at','>=', date($yearName."-".$month."-01"))
            	->whereDate('quotes.created_at','<=', date($yearName."-".$month."-t"))
	            ->where('quotes.kam_status', '!=', 2)
	            ->where('users.zone',$getuser->zone)
	            ->whereNull('quotes.deleted_at')
	            ->get();

	            //dd($getUserMonthlyAveragePrice);	
	            $quote_quantity = $quote_kamprice = $average_kam_price = 0;
	            //$MonthlyAveragePriceMonthData = [];
		        foreach ($getUserMonthlyAveragePrice as $key => $value) {
		            	
		        	$getUserMonthlyAveragePriceQuteSheduleQuantity = DB::table('quote_schedules')
	   		 	 	->select('quote_schedules.quantity') 
		            ->where('quote_schedules.quote_id',$value->id)
		            ->sum('quote_schedules.quantity');
		            $getUserMonthlyAveragePriceQuteSheduleKamprice = DB::table('quote_schedules')
	   		 	 	->select('quote_schedules.kam_price') 
		            ->where('quote_schedules.quote_id',$value->id)
		            ->sum('quote_schedules.kam_price');

		            $quote_quantity = $quote_quantity + $getUserMonthlyAveragePriceQuteSheduleQuantity;
		            $quote_kamprice = $quote_kamprice + $getUserMonthlyAveragePriceQuteSheduleKamprice;
		            
		        }
		        if($quote_quantity == 0){
		        	$average_kam_price = 0;
		        }else{
		        	$average_kam_price = $quote_kamprice / $quote_quantity;
		        }
		        
		        $MonthlyAveragePriceMonthData[$k]['total_quantity'] = $quote_quantity;
		        $MonthlyAveragePriceMonthData[$k]['average_kam_price'] = round($average_kam_price);
		        
				$dateObj   = DateTime::createFromFormat('!m', $month);
				$monthName = $dateObj->format('F');
		        $MonthlyAveragePriceMonthData[$k]['month'] = $month." ".$monthName." ".$yearName;
		    }
		    $data['monthly_average_price_data'] = $MonthlyAveragePriceMonthData;

	        // monthly volume graph with average net price end

	        // ----------------------------------------------------------
   		 }
   		 else if ($getuser->user_type == 'Sales' || $getuser->user_type == 'SM'|| $getuser->user_type == 'OPT') { 


   		 	// dd($fromdate,$todate);
   		 	// Show data according to financial year.....
   		 	$volumeCon = DB::table('quotes')
   		 	 	->select('quantity') 
	            ->where('quotes.kam_status',4)
	            ->whereDate('quotes.created_at','>=', $fromdate)
                ->whereDate('quotes.created_at','<=', $todate) 
	            ->whereNull('quotes.deleted_at') 
	            ->sum('quotes.quantity');	         
	        $data['volumeconfirmed'] = $volumeCon;

	        $volumeUnderNego = DB::table('quotes')
   		 	 	->select('quantity') 
	            ->where('quotes.kam_status',6) 
	            ->whereDate('quotes.created_at','>=', $fromdate)
                ->whereDate('quotes.created_at','<=', $todate)
	            ->whereNull('quotes.deleted_at')
	            // ->groupBy('rfq_no')
	            ->sum('quotes.quantity');	         
	        $data['volume_under_negotiation'] = $volumeUnderNego;
 			

	        $getrfqno = DB::table('quotes')
	        	->select('quotes.id')
	            ->where('quotes.kam_status',4) 
	            ->whereDate('quotes.created_at','>=', $fromdate)
                ->whereDate('quotes.created_at','<=', $todate) 
	            ->whereNull('quotes.deleted_at')
	            ->groupBy('rfq_no')
	            ->get(); 
	             $explantconordersum = 0;
	            foreach ($getrfqno as $key => $valuesum) 
	            {
	            	$getqutsedno = DB::table('quote_schedules') 
	            	->where('quote_schedules.quote_id',$valuesum->id)
		            ->where('quote_schedules.pickup_type','=','PLANT') 
		            ->get(); 
		            
		            foreach ($getqutsedno as $key => $sumofqua) {
		            	 
		            	$explantconordersum+= $sumofqua->quantity;
		            }	            	
	            }
	            
	            	         
	        $data['ex_plant_confirmed_orders'] = $explantconordersum;

	        $getdepotrfqno = DB::table('quotes')
	        	->select('quotes.id')
	            ->where('quotes.kam_status',4)
	            ->whereDate('quotes.created_at','>=', $fromdate)
                ->whereDate('quotes.created_at','<=', $todate)
	            ->whereNull('quotes.deleted_at')
	            ->groupBy('rfq_no')
	            ->get(); 
	            $exdepotconordersum = 0;
	            foreach ($getdepotrfqno as $key => $valsum) 
	            {
	            	$getdepotrfq = DB::table('quote_schedules') 
	            	->where('quote_schedules.quote_id',$valsum->id)
		            ->where('quote_schedules.pickup_type','=','DEPOT') 
		            ->get(); 
		            
		            foreach ($getdepotrfq as $key => $sumofqua) {
		            	 
		            	$exdepotconordersum+= $sumofqua->quantity;
		            }	            	
	            }
	            
	            	         
	        $data['ex_Depot_confirmed_orders'] = $exdepotconordersum;

	        $getdaprfq = DB::table('quotes')
	        	->select('quotes.id')
	            ->where('quotes.kam_status',4)  
	            ->whereDate('quotes.created_at','>=', $fromdate)
                ->whereDate('quotes.created_at','<=', $todate)
	            ->whereNull('quotes.deleted_at')
	            ->groupBy('rfq_no')
	            ->get(); 
	            $dapconordersum = 0;
	            foreach ($getdaprfq as $key => $valdapsum) 
	            {
	            	$getnewdaprfq = DB::table('quote_schedules') 
	            	->where('quote_schedules.quote_id',$valdapsum->id)
		            ->where('quote_schedules.delivery','=','DAP (Delivered at Place)') 
		            ->get(); 
		            
		            foreach ($getnewdaprfq as $key => $sumofdapqua) {
		            	 
		            	$dapconordersum+= $sumofdapqua->quantity;
		            }	            	
	            }
	            
	            	         
	        $data['DAP_confirmed_orders'] = $dapconordersum;

	        // End of Show data according to financial year.....



	        // Show data according to month .....
	         

	        $getrfqno = DB::table('quotes')
	        	->select('quotes.id')
	            ->where('quotes.kam_status',4) 
	            ->whereDate('quotes.created_at','>=', $fromdate)
                ->whereDate('quotes.created_at','<=', $todate) 
	            ->whereNull('quotes.deleted_at')
	            ->groupBy('rfq_no')
	            ->get(); 
	             $explantconordersum = 0;
	            foreach ($getrfqno as $key => $valuesum) 
	            {
	            	$getqutsedno = DB::table('quote_schedules') 
	            	->where('quote_schedules.quote_id',$valuesum->id)
		            ->where('quote_schedules.pickup_type','=','PLANT') 
		            ->get(); 
		            
		            foreach ($getqutsedno as $key => $sumofqua) {
		            	 
		            	$explantconordersum+= $sumofqua->quantity;
		            }	            	
	            }
	            
	            	         
	        $data['ex_plant_con_orders_chrt_mon'] = $explantconordersum;

	        $getdepotrfqno = DB::table('quotes')
	        	->select('quotes.id')
	            ->where('quotes.kam_status',4)
	            ->whereDate('quotes.created_at','>=', $fromdate)
                ->whereDate('quotes.created_at','<=', $todate)
	            ->whereNull('quotes.deleted_at')
	            ->groupBy('rfq_no')
	            ->get(); 
	            $exdepotconordersum = 0;
	            foreach ($getdepotrfqno as $key => $valsum) 
	            {
	            	$getdepotrfq = DB::table('quote_schedules') 
	            	->where('quote_schedules.quote_id',$valsum->id)
		            ->where('quote_schedules.pickup_type','=','DEPOT') 
		            ->get(); 
		            
		            foreach ($getdepotrfq as $key => $sumofqua) {
		            	 
		            	$exdepotconordersum+= $sumofqua->quantity;
		            }	            	
	            }
	            
	            	         
	        $data['ex_Depot_con_orders_chrt_mon'] = $exdepotconordersum;

	        $getdaprfq = DB::table('quotes')
	        	->select('quotes.id')
	            ->where('quotes.kam_status',4)  
	            ->whereDate('quotes.created_at','>=', $fromdate)
                ->whereDate('quotes.created_at','<=', $todate)
	            ->whereNull('quotes.deleted_at')
	            ->groupBy('rfq_no')
	            ->get(); 
	            $dapconordersum = 0;
	            foreach ($getdaprfq as $key => $valdapsum) 
	            {
	            	$getnewdaprfq = DB::table('quote_schedules') 
	            	->where('quote_schedules.quote_id',$valdapsum->id)
		            ->where('quote_schedules.delivery','=','DAP (Delivered at Place)') 
		            ->get(); 
		            
		            foreach ($getnewdaprfq as $key => $sumofdapqua) {
		            	 
		            	$dapconordersum+= $sumofdapqua->quantity;
		            }	            	
	            }
	            
	            	         
	        $data['DAP_con_orders_chrt_mon'] = $dapconordersum;

	        // End of Show data according to month .....

   		 }

   		 $data['mtdata'] = 'MT';
   		
         
        // $password = "123456";
        // $encrypted = CryptoJsAes::encrypt($data, $password);
            
        return response()->json(['status'=>1,'message' =>'success.','result' => $data],200);
   }

   

}

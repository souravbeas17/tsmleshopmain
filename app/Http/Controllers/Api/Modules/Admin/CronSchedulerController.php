<?php

namespace App\Http\Controllers\Api\Modules\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Models\Sentemailorder;
use App\Models\Models\Order;
use App\ServicesMy\MailService;
use DB;

class CronSchedulerController extends Controller
{
    public function rejectByValidtill()
    {
        
        try{
            
            $date = date('Y-m-d');
            // dd($date);

            $data = DB::table('quote_schedules')->leftjoin('quotes','quote_schedules.quote_id','quotes.id')->select('quote_schedules.*','quotes.rfq_no')
                 ->where('quote_schedules.quote_status','!=',1)->whereNull('quote_schedules.deleted_at')
                 ->whereNull('quotes.deleted_at')
                 ->get();
                 // dd($data);

                 foreach ($data as $key => $value) {
                    
                    $valid = $value->valid_till;
                    $vallid_till = date("Y-m-d", strtotime($valid));
                    // dd($vallid_till);
                 	if($vallid_till < $date)
                 	 {	
                 	   // dd($value->id);
                 	  $order = DB::table('orders')->where('rfq_no',$value->rfq_no)->get()->toArray();
                     
                 	  if(empty($order))
                 	  {
                 	  	
                 	  	  DB::table('quote_schedules')->where('id',$value->id)->update(['quote_status' => 2]);
                 	  }
                 	}

                      $arr = array();
                 	  $rfqs = DB::table('quote_schedules')->leftjoin('quotes','quote_schedules.quote_id','quotes.id')->select('quote_schedules.quote_status','quotes.rfq_no')
                 	         ->where('quotes.rfq_no',$value->rfq_no)
			                 ->whereNull('quote_schedules.deleted_at')
			                 ->whereNull('quotes.deleted_at')
			                 ->get();
                          foreach ($rfqs as $k => $v) {
                          	  if($v->quote_status == 2)
                          	  {
                                array_push($arr,$v->quote_status);
                            }
                          }
                        // dd(count($arr));

                        if(count($rfqs) == count($arr))
                        {
                              DB::table('quotes')->where('rfq_no',$value->rfq_no)->update(['kam_status' => 10]);
                        } 
                          
                 }


            // dd($data);
            return response()->json(['status'=>1,'message' =>'success.','result' => 1],200);

        }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
        }    
    }


 

    public function escalationForPending(Request $request)
    {

 
    $result = [];
        try{    

            $todaydate =  date('Y-m-d');  
            $sentorder = Sentemailorder::where('date',$todaydate)->first();

            if(!empty($sentorder))
            {
                $order = $sentorder->order+1;
                $pendingmail = DB::table('pending_mails')->where('id',$order)->first();

                if(empty($pendingmail))
                {
                    return response()->json(['status'=>1,'message' =>'success.','result' => 'All Mail sent'],200);
                }
            }else{

                $pendingmail = DB::table('pending_mails')->first();
            }
            
            $hourAgo= date('Y-m-d H:i:s', strtotime('-4 hour'));

            $quote = DB::table('quotes')
           ->leftjoin('users','quotes.user_id','users.id')
           ->leftjoin('rfq_status_refs','quotes.kam_status','rfq_status_refs.status')
           ->select('quotes.rfq_no','quotes.user_id','users.name','quotes.quantity','rfq_status_refs.st_text as status','quotes.updated_at','quotes.id','quotes.kam_status','quotes.quote_type','quotes.rfq_type','users.email')
           ->orderBy('quotes.updated_at','desc')->where('quotes.kam_status','!=',10)->where('quotes.kam_status','!=',4)->where('quotes.created_at','<=',$hourAgo)
           ->groupBy('quotes.rfq_no');
           
               $quote = $quote->whereNull('quotes.deleted_at')
           ->get()->toArray();
           // echo "<pre>";print_r($quote);exit();

          if(!empty($quote))
          {
          foreach ($quote as $key => $value) {

            $create = DB::table('quotes')->where('rfq_no',$value->rfq_no)->select('created_at')->first();
            
            $result[$key]['id'] = $value->id;
            $result[$key]['user'] = $value->email;

            $result[$key]['created_at'] = date("d/m/Y", strtotime($create->created_at));

            if($value->kam_status == 8 || $value->quote_type == 'SM')
              {
                  $var = 'Sales Head';
              }
              else if($value->kam_status == 7 || $value->quote_type == 'Sales')
              {
                  $var = 'Sales Planing';
              }
              else if($value->kam_status != 4 && $value->quote_type == 'C'){
                
                    $var = 'Cam';
              }
              else if($value->kam_status != 4 && $value->quote_type == 'Kam'){
                
                     $var = 'Customer';
              }
              else{
                    $var = ' ';

              } 
              $result[$key]['pending_with'] = $var;



          }
             

             $cc_email = "";

             $sub = 'RFQ Pending details';
     
             $html = 'mail.campendingmail';

             $data['rfq'] = 'Rfq is pending please check';

             $email = $pendingmail->email;
             // dd($email);
            (new MailService)->dotestMail($sub,$html,$email,$data,$cc_email);
         // Mail::send(new AcceptedRfqMail($data));

        }


            // ----------------- po pending ---------------------------------------
            $orders = Order::whereNull('cus_po_no')->get();
           // dd($pendingmail->email);
            if(!empty($orders))
            {
                 $cc_email = "";

                 $sub = 'PO Pending details';
         
                 $html = 'mail.campendingmail';

                 $data['rfq'] = 'Customer PO No. in PO is pending, please check';

                 $email = $pendingmail->email;
                (new MailService)->dotestMail($sub,$html,$email,$data,$cc_email);
            }
            // --------------------------------------------------------------------
             // dd($sentorder);
                 
            if(empty($sentorder))
            {
           
                 $arr['date'] = date('Y-m-d');
                 $arr['order'] = $pendingmail->id;
                 Sentemailorder::create($arr);
                 
            }else{

                 $arr['order'] = $pendingmail->id;
                 Sentemailorder::where('date',$todaydate)->update($arr);
            }
        // dd($result);

          return response()->json(['status'=>1,'message' =>'success.','result' => 'Mail sent'],200);
          
        
        }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
        }

    }
}

<?php

namespace App\Http\Controllers\Api\Modules\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ExportRfq;
use App\Exports\ExportAllRfq;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Models\Order;
use App\Models\Models\Guidelineprice;
use DB;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class RfqManagementController extends Controller
{
        /**
      * This for admin rfq management.
      *
      * @param  \App\Orders
      * @return \Illuminate\Http\Response
    */
    public function getRfqAdmin(Request $request)
    {

 
    $result = [];
        try{         
          
            $quote = DB::table('quotes')
           ->leftjoin('users','quotes.user_id','users.id')
           ->leftjoin('rfq_status_refs','quotes.kam_status','rfq_status_refs.status')
           ->select('quotes.rfq_no','quotes.user_id','users.name','quotes.quantity','rfq_status_refs.st_text as status','quotes.updated_at','quotes.id','quotes.kam_status','quotes.quote_type','quotes.rfq_type')
           ->orderBy('quotes.updated_at','desc')
           ->groupBy('quotes.rfq_no');
           
           if(!empty($request->rfq_no))
           {   
               $quote = $quote->where('quotes.rfq_no',$request->rfq_no);
           }

           if(!empty($request->date) && !empty($request->fromdate))
           {   
               $date1 = date_create($request->date);
               $date = date_format($date1,"Y-m-d");

               $date2 = date_create($request->fromdate);
               $date22 = date_format($date2,"Y-m-d");
               // dd($date);
               $quote = $quote->whereDate('quotes.updated_at','>=',$date)->whereDate('quotes.updated_at','<=',$date22);
           }

           if(!empty($request->role))
           {   
               if($request->role == 'Kam')
               {
                  $quote = $quote->where('quotes.kam_status','!=',4)->where('quotes.quote_type','=','C');
               }else if($request->role == 'SM')
               {
                   $quote = $quote->where('quotes.kam_status','=',8)->orWhere('quotes.quote_type','=','SM');
               }
               else if($request->role == 'Sales')
               {
                     $quote = $quote->where('quotes.kam_status','=',7)->orWhere('quotes.quote_type','=','Sales');
               }else
               {
                    $quote = $quote->where('quotes.kam_status','!=',4)->where('quotes.quote_type','=','Kam');
               }
               
           }

           // if(!empty($request->customer))
           // {

           //     $quote = $quote->where('orders.cus_po_no',$request->cus_po_no);
           // }

           
           $quote = $quote->whereNull('quotes.deleted_at')
           ->get()->toArray();
           // echo "<pre>";print_r($quote);exit();

          if(!empty($quote))
          {
          foreach ($quote as $key => $value) {

            $create = DB::table('quotes')->where('rfq_no',$value->rfq_no)->select('created_at')->first();
            
            $result[$key]['id'] = $value->id;
            $result[$key]['user'] = $value->name;
            $result[$key]['rfq_no'] = $value->rfq_no;
            $result[$key]['rfq_type'] = $value->rfq_type;
            $result[$key]['created_at'] = date("d/m/Y", strtotime($create->created_at));
            $result[$key]['quantity'] = $value->quantity;
            $date =  date_create($value->updated_at);
            $po_dt = date_format($date,"d/m/Y");
            $result[$key]['date'] = $po_dt;
            $result[$key]['status'] = $value->status;
            $result[$key]['kam_st'] = $value->kam_status;
            $result[$key]['quote_type'] = $value->quote_type;
            $date1 = date_create($value->updated_at);
            $date2 = date_create(date('Y-m-d'));
            $diff = date_diff($date1,$date2);
            $result[$key]['date_remaining'] = $diff->format("%a").' Days';

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
        }
        else{
          $result = [];
        }

          return response()->json(['status'=>1,'message' =>'success.','result' => $result],200);
          
        
        }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
        }

    }


      /**
      * This for admin rfq management.
      *
      * @param  \App\Orders
      * @return \Illuminate\Http\Response
    */
    public function quoteScheById($id)
    {

 
    $result = [];
        try{         
          
            $quote = DB::table('quotes')
             ->leftjoin('quote_schedules','quotes.id','quote_schedules.quote_id')
             ->leftjoin('sub_categorys','quote_schedules.sub_cat_id','sub_categorys.id')
             ->leftjoin('rfq_status_refs','quotes.kam_status','rfq_status_refs.status')
             ->select('quote_schedules.*','sub_categorys.sub_cat_name')
             ->orderBy('quotes.updated_at','desc')
               ->whereNull('quotes.deleted_at')
               ->where('quotes.rfq_no',$id)
               ->get()->toArray();
           // echo "<pre>";print_r($quote);exit();

          if(!empty($quote))
          {
          foreach ($quote as $key => $value) {
            
            $result[$key]['id'] = $value->id;
            $result[$key]['sub_cat'] = $value->sub_cat_name;
            $result[$key]['quantity'] = $value->quantity;
            $result[$key]['pro_size'] = $value->pro_size;
            $result[$key]['plant'] = $value->plant;
            $result[$key]['status'] = $value->quote_status;
            $result[$key]['expected_price'] = $value->expected_price;
            $result[$key]['kam_price'] = $value->kam_price;

          }
        }
        else{
          $result = [];
        }

          return response()->json(['status'=>1,'message' =>'success.','result' => $result],200);
          
        
        }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
        }

    }


   // ---------------------- sales mis excel ---------------------------------------
    public function exportExcelRfq(Request $request)
    {
        // dd($request->input('plant'));
 
    $result = [];
        try{         
          
            $quote = DB::table('quotes')
                    ->leftjoin('quote_schedules','quotes.id','quote_schedules.quote_id')
                    ->leftjoin('sub_categorys','quote_schedules.sub_cat_id','sub_categorys.id')
                    ->leftjoin('users','quotes.user_id','users.id')
                    ->leftjoin('address as bill','quote_schedules.bill_to','bill.id')
                    ->leftjoin('address as ship','quote_schedules.ship_to','ship.id')
                    ->select('quotes.rfq_no','quote_schedules.*','bill.city as bcity','ship.city as scity','users.org_name','users.user_code','sub_categorys.sub_cat_name')
                    ->whereNull('quotes.deleted_at')->whereNull('quote_schedules.deleted_at')
                    ->where('quotes.rfq_no',$request->rfq_no)
           ->get()->toArray();
           // echo "<pre>";print_r($quote);exit();

          if(!empty($quote))
          {
          foreach ($quote as $key => $value) {
            
            $transct = DB::table('sc_transactions')->where('schedule',$value->schedule_no)->where('rfq_no',$value->rfq_no)->select('code','value')->get()->toArray();
            
            $val = array_column($transct, 'value', 'code');
            // dd($transct);

            $result[$key]['code'] = $value->user_code;
            $result[$key]['user'] = $value->org_name;
            $result[$key]['rfq_no'] = $value->rfq_no;
            $result[$key]['sub_cat'] = $value->sub_cat_name;
            $result[$key]['pro_size'] = $value->pro_size;
            $result[$key]['quantity'] = $value->quantity;
            $result[$key]['expectedPrice'] = $value->expected_price;
            $result[$key]['tsmlPrice'] = $value->kam_price;
            $result[$key]['bill'] = $value->bcity;
            $result[$key]['ship'] = $value->scity;
            $result[$key]['to_date'] = date_format(date_create($value->to_date),"d-m-Y");
            $result[$key]['from_date'] = date_format(date_create($value->from_date),"d-m-Y");
            $result[$key]['valid_till'] = date_format(date_create($value->valid_till),"d-m-Y");
            $result[$key]['bpt'] = array_key_exists("BPT01",$val) ? $val['BPT01'] : 0;
            $result[$key]['qtypre'] = array_key_exists("QP01",$val) ? $val['QP01'] : 0;
            $result[$key]['del_cost'] = array_key_exists("DC01",$val) ? $val['DC01'] : 0;
            $result[$key]['int_rate'] = array_key_exists("IR01",$val) ? $val['IR01'] : 0;
            $result[$key]['cre_cost'] = array_key_exists("CC01",$val) ? $val['CC01'] : 0;
            $result[$key]['misc'] = array_key_exists("MC01",$val) ? $val['MC01'] : 0;
            $result[$key]['ppa'] = array_key_exists("PPA01",$val) ? $val['PPA01'] : 0;
            $result[$key]['final'] = array_key_exists("FP01",$val) ? $val['FP01'] : 0;

           

          }
            // echo "<pre>";print_r($result);exit();
            return Excel::download(new ExportRfq($result), 'rfqdump.xlsx');
        }
        else{
          $result = [];
        }

          return response()->json(['status'=>1,'message' =>'success.','result' => $result],200);
          
        
        }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
        }

    }
    // -------------------------------------------------------------------------------
    // -------------------------------------------------------------------------------
    public function getRfqAdminExport(Request $request)
    {

 
    $result = [];
        try{         
          
            $quote = DB::table('quote_schedules')
            ->leftjoin('quotes','quote_schedules.quote_id','quotes.id')
           ->leftjoin('users','quotes.user_id','users.id')
           ->leftjoin('rfq_status_refs','quotes.kam_status','rfq_status_refs.status')
           ->select('quotes.rfq_no','quotes.user_id','users.name','quote_schedules.quantity','rfq_status_refs.st_text as status','quotes.updated_at','quote_schedules.id','quotes.kam_status','quotes.quote_type','quote_schedules.schedule_no')
           ->orderBy('quote_schedules.updated_at','desc');
           // ->groupBy('quotes.rfq_no');
           
           if(!empty($request->rfq_no))
           {   
               $quote = $quote->where('quotes.rfq_no',$request->rfq_no);
           }

           if(!empty($request->date))
           {   
               $date1 = date_create($request->date);
               $date = date_format($date1,"Y-m-d");
               // dd($date);
               $quote = $quote->whereDate('quotes.updated_at',$date);
           }

           // if(!empty($request->customer))
           // {

           //     $quote = $quote->where('orders.cus_po_no',$request->cus_po_no);
           // }

           
           $quote = $quote->whereNull('quotes.deleted_at')->whereNull('quote_schedules.deleted_at')
           ->get()->toArray();
           // echo "<pre>";print_r($quote);exit();

          if(!empty($quote))
          {
          foreach ($quote as $key => $value) {
            
            if(!empty($value->rfq_no))
            {
            $create = DB::table('quotes')->where('rfq_no',$value->rfq_no)->select('created_at')->first();
            
            $po_details = Order::where('rfq_no',$value->rfq_no)->first();

            $transct = DB::table('sc_transactions')->where('schedule',$value->schedule_no)->where('rfq_no',$value->rfq_no)->select('code','value')->get()->toArray();

            $guideline = Guidelineprice::where('rfq_no',$value->rfq_no)->where('schldId',$value->schedule_no)->first();
            
            $val = array_column($transct, 'value', 'code');
            
            $result[$key]['id'] = $value->id;
            $result[$key]['user'] = $value->name;
            $result[$key]['rfq_no'] = $value->rfq_no;
            $result[$key]['created_at'] = date("d/m/Y", strtotime($create->created_at));
            $result[$key]['quantity'] = $value->quantity;
            $date =  date_create($value->updated_at);
            $po_dt = date_format($date,"d/m/Y");
            $result[$key]['date'] = $po_dt;
            $result[$key]['status'] = $value->status;
            $date1 = date_create($value->updated_at);
            $date2 = date_create(date('Y-m-d'));
            $diff = date_diff($date1,$date2);
            $result[$key]['date_remaining'] = $diff->format("%a").' Days';

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
              $result[$key]['po_no'] = (!empty($po_details)) ? $po_details->cus_po_no : '';
              $result[$key]['po_date'] = (!empty($po_details)) ? date("d/m/Y", strtotime($po_details->po_date)) : '';
            $result[$key]['bpt'] = array_key_exists("BPT01",$val) ? $val['BPT01'] : 0;
            $result[$key]['guide_bpt'] = (!empty($guideline)) ? $guideline->bpt_price : 0;
            $result[$key]['qtypre'] = array_key_exists("QP01",$val) ? $val['QP01'] : 0;
            $result[$key]['del_cost'] = array_key_exists("DC01",$val) ? $val['DC01'] : 0;
            $result[$key]['int_rate'] = array_key_exists("IR01",$val) ? $val['IR01'] : 0;
            $result[$key]['cre_cost'] = array_key_exists("CC01",$val) ? $val['CC01'] : 0;
            $result[$key]['misc'] = array_key_exists("MC01",$val) ? $val['MC01'] : 0;
            $result[$key]['ppa'] = array_key_exists("PPA01",$val) ? $val['PPA01'] : 0;
            $result[$key]['final'] = array_key_exists("FP01",$val) ? $val['FP01'] : 0;
            $result[$key]['g_qtypre'] = (!empty($guideline)) ? $guideline->price_premium : 0;
            $result[$key]['g_del_cost'] = (!empty($guideline)) ? $guideline->delivery_cost : 0;
            $result[$key]['g_int_rate'] = (!empty($guideline)) ? $guideline->interest_rate : 0;
            $result[$key]['g_cre_cost'] = (!empty($guideline)) ? $guideline->dayscost : 0;
            $result[$key]['g_misc'] = (!empty($guideline)) ? $guideline->misc_expense : 0;
            $result[$key]['g_ppa'] = (!empty($guideline)) ? $guideline->bpt_price : 0;
            $result[$key]['g_final'] = (!empty($guideline)) ? $guideline->totalsum : 0;

             }
          }
           $curr = get_current_user();
           // dd($result);
           
             $user = "D:"."\\"."allrfqdump.xlsx";
             // dd($user);
                $writer = WriterEntityFactory::createXLSXWriter();
              // $writer->openToFile($user);
                $writer->openToBrowser('allrfqdump.xlsx');

              $cells = [
                  WriterEntityFactory::createCell('Customer Name'),
                  WriterEntityFactory::createCell('RFQ. No.'),
                  WriterEntityFactory::createCell('RFQ Date'),
                  WriterEntityFactory::createCell('Quantity'),
                  WriterEntityFactory::createCell('Date'),
                  WriterEntityFactory::createCell('Status'),
                  WriterEntityFactory::createCell('Pending(days)'),
                  WriterEntityFactory::createCell('Pending With'),
                  WriterEntityFactory::createCell('PO No.'),
                  WriterEntityFactory::createCell('PO Date'),
                  WriterEntityFactory::createCell('Basic Price'),
                  WriterEntityFactory::createCell('Guideline Basic Price'),
                  WriterEntityFactory::createCell('Quality Premium'),
                  WriterEntityFactory::createCell('Delivery Cost'),
                  WriterEntityFactory::createCell('Interest Rate'),
                  WriterEntityFactory::createCell('Credit Cost For'),
                  WriterEntityFactory::createCell('Misc. Cost'),
                  WriterEntityFactory::createCell('Proposed Price Adjustment'),
                  WriterEntityFactory::createCell('Final Price'),
                  WriterEntityFactory::createCell('Guideline Quality Premium'),
                  WriterEntityFactory::createCell('Guideline Delivery Cost'),
                  WriterEntityFactory::createCell('Guideline Interest Rate'),
                  WriterEntityFactory::createCell('Guideline Credit Cost For'),
                  WriterEntityFactory::createCell('Guideline Misc. Cost'),
                  WriterEntityFactory::createCell('Guideline Proposed Price Adjustment'),
                  WriterEntityFactory::createCell('Guideline Final Price'),
              ];

              $singleRow = WriterEntityFactory::createRow($cells);
              $writer->addRow($singleRow);

              $style = (new StyleBuilder())->setFormat('mm/dd/yyyy')->build();
              $arr = $result;    
              foreach ($arr as $key => $value) {
                  $rowFromValues = WriterEntityFactory::createRowFromArray([
                      $value['user'],
                      $value['rfq_no'],
                      ($value['created_at']=="")?"":$this->convertDates($value['created_at']),
                      $value['quantity'],
                      ($value['date']=="")?"":$this->convertDates($value['date']),
                      $value['status'],
                      $value['date_remaining'],
                      $value['pending_with'],
                      $value['po_no'],
                      ($value['po_date']=="")?"":$this->convertDates($value['po_date']),
                      ($value['bpt']== 0)?"":$value['bpt'],
                      strval($value['guide_bpt']),
                      ($value['qtypre']== 0)?"":$value['qtypre'],
                      ($value['del_cost']== 0)?"":$value['del_cost'],
                      ($value['int_rate']==0)?"":$value['int_rate'],
                      ($value['cre_cost']== 0)?"":$value['cre_cost'],
                      ($value['misc']== 0)?"":$value['misc'],
                      ($value['ppa']== 0)?"":$value['ppa'],
                      ($value['final']== 0)?"":$value['final'],
                      strval($value['g_qtypre']),
                      strval($value['g_del_cost']),
                      strval($value['g_int_rate']),
                      strval($value['g_cre_cost']),
                      strval($value['g_misc']),
                      strval($value['g_ppa']),
                      strval($value['g_final']),
                  ], $style);
                 $writer->addRow($rowFromValues);
              }
              $writer->close();
           // return Excel::download(new ExportAllRfq($result), 'allrfqdump.xlsx');
        }
        else{
          $result = [];
        }

          return response()->json(['status'=>1,'message' =>'success.','result' => 'Excel downloaded'],200);
          
        
        }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
        }

    }
    // ----------------------------------------------------------------------------------------

    function convertDates($date)
    {
        $dates = Date::PHPToExcel($date);
        // dd($dates);
        return $dates;
    }
}

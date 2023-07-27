<?php

namespace App\Http\Controllers\Api\Modules\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\OtpVerification;
use App\Mail\Register;
use App\Models\User;
use App\Models\Address;
use App\Jobs\UserCreated;
use App\Models\Models\Order;
use App\Models\Models\Quote;
use Illuminate\Support\Facades\Hash;
use App\Models\Models\RegistrationLog;
use App\ServicesMy\MailService;
use App\Exports\ExportCust;
use Maatwebsite\Excel\Facades\Excel;
use JWTAuth;
use Validator;
use Response;
use Mail;
use DB;
use Nullix\CryptoJsAes\CryptoJsAes;

class AdminUserManageController extends Controller
{
  /**
      * This for customer list admin.
      *
      * @param  \App\Product  $product
      * @return \Illuminate\Http\Response
    */
    public function camCustomerList(Request $request)
    { 
      $response = [];
        try{         
          
          
              // $data = DB::table('users')
              //             ->leftjoin('address','users.id','address.user_id')                           
              //             ->select('users.id as user_id','users.id as user_id''address.*')
              //             ->get();
              $kzone = User::where('id',$request->kam_id)->first();
              
              $data = User::orderBy('id','ASC')
                          ->where('user_type','=','C')
                          ->where('zone','=',$kzone->zone)
                          ->where('user_code','!=',NULL)
                          // ->where('user_status','!=',3) 
                          ->get();
          
          
          $catelist = [];
            foreach ($data as $key => $value) 
            {               
              $catdata['user_id'] = $value->id; 
              $catdata['email'] = $value->email;
              $catdata['name'] = $value->name; 
              $catelist[] = $catdata;
            } 
          return response()->json(['status'=>1,'message' =>'success.','result' => $catelist],200);
          
        
        }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
        }
    }
    /**
      * This for expost customer data in excel.
      *
      * @param  \App\Product  $product
      * @return \Illuminate\Http\Response
    */
    public function exportPortalCustAdmin(Request $request)
    {
      // dd('exportPortalCustAdmin');
      $res = DB::table('address')
                        ->leftjoin('users','address.user_id','users.id')
                        ->select('users.phone as uphone','address.*')
                        ->where('users.reg_by','P')
                        // ->whereNotNull('address.cus_code')
                        ->get()
                        ->toArray();
         // dd($res);

        if(!empty($res))
          {
            foreach ($res as $key => $value) {
              
              $result[$key]['cus_code'] = $value->cus_code;
              $result[$key]['phone'] = $value->uphone;
              $result[$key]['company_name'] = $value->company_name;
              $result[$key]['addressone'] = $value->addressone;
              $result[$key]['addresstwo'] = $value->addresstwo;
              $result[$key]['city'] = $value->city;
              $result[$key]['state'] = $value->state;
              $result[$key]['pincode'] = $value->pincode;
              if($value->type == 'A')
              {
                 $type = 'Shipping';
              }else{
                $type = 'Billing';
              }
              $result[$key]['type'] = $type;

            }

              return Excel::download(new ExportCust($result), 'portal_cus_details.xlsx');
          }
    } 
  /**
      * This for expost customer data in excel.
      *
      * @param  \App\Product  $product
      * @return \Illuminate\Http\Response
    */
    public function exportExcelCustAdmin(Request $request)
    {
         $res = DB::table('address')
                        ->leftjoin('users','address.user_id','users.id')
                        ->select('users.phone as uphone','address.*')
                        // ->where('users.reg_by','U')
                        // ->whereNotNull('address.cus_code')
                        ->get()
                        ->toArray();
         // dd($res);

         if(!empty($res))
          {
          foreach ($res as $key => $value) {
            
            $result[$key]['cus_code'] = $value->cus_code;
            $result[$key]['phone'] = $value->uphone;
            $result[$key]['company_name'] = $value->company_name;
            $result[$key]['addressone'] = $value->addressone;
            $result[$key]['addresstwo'] = $value->addresstwo;
            $result[$key]['city'] = $value->city;
            $result[$key]['state'] = $value->state;
            $result[$key]['pincode'] = $value->pincode;
            if($value->type == 'A')
            {
               $type = 'Shipping';
            }else{
              $type = 'Billing';
            }
            $result[$key]['type'] = $type;

          }

            return Excel::download(new ExportCust($result), 'cus_details.xlsx');
    }
  }
    /**
      * This for customer list admin.
      *
      * @param  \App\Product  $product
      * @return \Illuminate\Http\Response
    */
    public function customerListAdmin(Request $request)
    { 
      $response = [];
        try{         
            
            $page = $request->page;
            $limit = $request->limit;

            if(empty($request->page) ){
                $page = 1;
            }
            if(empty( $request->limit) ){
                $limit = 10;
            }
            $offset = 0;
            if( $page > 1 ){
                $offset = ($page - 1) * $limit;
            }
            if( $request->cust_name)
            {      
             
                $catelist = User::orderBy('id','desc')
                    ->select('id as user_id','user_code','reg_by','email','name','user_status as status')
                    ->where('name','LIKE',"%{$request->cust_name}%") 
                    ->where('user_type','=','C') 
                    ->offset($offset)->limit($limit)
                    ->get()->toArray();

                    $totalData = User::orderBy('id','desc')
                    ->select('id as user_id','user_code','reg_by','email','name','user_status as status')
                    ->where('name','LIKE',"%{$request->cust_name}%") 
                    ->where('user_type','=','C') 
                    ->count();
            }else{
                $catelist = User::orderBy('id','asc')
                    ->select('id as user_id','user_code','reg_by','email','name','user_status as status')
                    ->where('user_type','=','C')
                    ->offset($offset)->limit($limit)
                    ->get()->toArray();

                $totalData = User::orderBy('id','asc')
                    ->select('id as user_id','user_code','reg_by','email','name','user_status as status')
                    ->where('user_type','=','C') 
                    ->count();
            }

            
         
          return response()->json(['status'=>1,'message' =>'success.','result' => $catelist,'totalData'=>$totalData],200);
          
        
        }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
        }
    }

    /**
     * This is for block user in admin. 
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
    */
    public function blockCustomerAdminSingle(Request $request,$user_id)
    {
      try{   

          $getuser = User::where('id',$user_id)->first();
            if(!empty($getuser))
            { 
            $input['user_status'] = 3;  

              $updateuser = User::where('id',$getuser->id)->update($input);

     
              return response()->json(['status'=>1,'message' =>'User block successfully.']);
               
            }
            else
            {
                return response()->json(['status'=>0,'message'=>'No data found'],200);
            } 
             
        }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
        }

    }

    /**
     * This is for block user in admin. 
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
    */
    public function UnblockCustomerAdminSingle(Request $request,$user_id)
    {
      try{   
          
          $getuser = User::where('id',$user_id)->first();
            if(!empty($getuser))
            { 
            $input['user_status'] = 2;  

              $updateuser = User::where('id',$getuser->id)->update($input);

     
              return response()->json(['status'=>1,'message' =>'User block successfully.']);
               
            }
            else
            {
                return response()->json(['status'=>0,'message'=>'No data found'],200);
            } 
             
        }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
        }

    }

    /**
     * This is for block user in admin. 
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
    */
    public function blockCustomerAdminMulti(Request $request)
    {
      try{   
          // dd('ok');

          $cust_id = $request->userIds; 
          // dd($cust_id);

          $input['user_status'] = $request->status;  

          $updateuser = User::whereIn('id',$cust_id)->update($input);

 
          return response()->json(['status'=>1,'message' =>'Users block successfully.']);
               
            
             
        }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
        }

    }

    /**
      * This for customer details admin.
      *
      * @param  \App\Product  $product
      * @return \Illuminate\Http\Response
    */
    public function customerDetailsAdmin(Request $request)
    { 
  
      $response = [];
        try{         
           
              // $data = DB::table('users')
              //             ->leftjoin('address','users.id','address.user_id')                           
              //             ->select('users.id as user_id','users.id as user_id''address.*')
              //             ->get();
            // $data = User::orderBy('id','desc')->where('user_type','=','C')->get();
            // dd($request->userId);
            $addressdata = DB::table('users')
                    ->leftjoin('address','users.id','address.user_id')
                    ->where('users.id',$request->userId) 
                    ->select('users.id as uid','users.org_name as com_name','users.zone as user_zone','users.user_code as user_code','address.*')
                    ->get();  
            // dd($addressdata);
            return response()->json(['status'=>1,'message' =>'success.','result' => $addressdata],200);
          
          // $getuser = DB::table('users')->where('users.id',$request->userId)->first();
          //   // dd($getuser->zone);
          // $kam_data = DB::table('users')
          //                     ->where(['user_type' => "Kam",'zone' => $getuser->zone])
          //                     ->select('users.id as kam_id','users.name as kam_name','users.email as kam_email')
          //                     ->first();

          // return response()->json(['status'=>1,'message' =>'success.','result' => $addressdata,'kamdata'=>$kam_data],200);
        
        }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
        }
    }

    /**
    	* This for admin cam magement.
     	*
     	* @param  \App\Product  $product
     	* @return \Illuminate\Http\Response
    */
   	public function camListAdmin(Request $request)
   	{
 
		$response = [];
        try{         
         	
         	if($request->cam_name && $request->user_status && $request->zone && $request->user_type)
	        {      
	        	 
	            $data = User::orderBy('id','desc')
	                            ->where('name','LIKE',"%{$request->cam_name}%")
	                            ->where('user_status',$request->user_status)
	                            ->where('zone',$request->zone)
                              ->where('user_type',$request->user_type)
                              ->where('user_type','!=','C') 
	                            ->get();
	        }
          elseif($request->cam_name && $request->user_status && $request->zone)
          {      
             
              $data = User::orderBy('id','desc')
                              ->where('name','LIKE',"%{$request->cam_name}%")
                              ->where('user_status',$request->user_status)
                              ->where('zone',$request->zone) 
                              ->where('user_type','!=','C')
                              ->get();
          }
	        elseif($request->zone && $request->user_status)
	        {  
	           
	            $data = User::orderBy('id','desc')
	                            ->where('zone',$request->zone)
	                            ->where('user_status',$request->user_status) 
                              ->where('user_type','!=','C')
	                            ->get();
	        }

	        elseif($request->cam_name && $request->user_status)
	        {  
	           
	            $data = User::orderBy('id','desc')
	                            ->where('name','LIKE',"%{$request->cam_name}%")
	                            ->where('user_status',$request->user_status) 
                              ->where('user_type','!=','C')
	                            ->get();
	        }
	        
	        elseif($request->cam_name && $request->zone)
	        {   
	        	   
	            $data = User::orderBy('id','desc')
	                            ->where('name','LIKE',"%{$request->cam_name}%") 
	                            ->where('zone',$request->zone) 
                              ->where('user_type','!=','C')
	                            ->get();
	        }
          elseif($request->user_type)
          {      
             
              $data = User::orderBy('id','desc')
                              ->where('name','LIKE',"%{$request->cam_name}%") 
                              ->where('user_type',$request->user_type) 
                              ->where('user_type','!=','C')
                              ->get();
          }
	        
	        elseif($request->cam_name)
	        { 	
	        	// dd('$request->cam_name'); 
	            $data = User::where('name','LIKE',"%{$request->cam_name}%") 
	                            ->orderBy('id','desc')
                              ->where('user_type','!=','C')
	                            ->get();
	                            // dd($data);
	        }
	        elseif($request->zone)
	        {
	        	      
	            $data = User::orderBy('id','desc') 
	                            ->where('zone',$request->zone)
                              ->where('user_type','!=','C') 
	                            ->get();
	        }
          elseif($request->user_status)
          {
                  
              $data = User::orderBy('id','desc') 
                              ->where('user_status',$request->user_status)
                              ->where('user_type','!=','C') 
                              ->get();
          }
	        else{
	        	 
	            $data = User::orderBy('id','desc')->where('user_type','!=','C')->get();
	        }
	        
        	$catelist = [];
            foreach ($data as $key => $value) 
            { 	            
              $catdata['user_id'] = $value->id;
	            $catdata['email'] = $value->email;
              $catdata['name'] = $value->name; 
              $catdata['contact'] = $value->phone;
              $catdata['zone'] = $value->zone;
              $catdata['user_status'] = $value->user_status;
              $catdata['user_type'] = $value->user_type;
	            $catelist[] = $catdata;
            } 
         	return response()->json(['status'=>1,'message' =>'success.','result' => $catelist],200);
          
        
        }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
        }

   	}

   	/**
     * This is for add cam in admin. 
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
    */
    public function addCamAdminb(Request $request)
    {

      \DB::beginTransaction();

        try{ 

          $validator = Validator::make($request->all(), [
          'email' => 'required|string|email|max:255|unique:users',
          'name' => 'required',
          'user_type' => 'required',
          'phone' => 'required', 
	      ]);
	      

          if ($validator->fails()) { 
              return response()->json(['status'=>0,'message' =>config('global.failed_msg'),'result' => $validator->errors()],config('global.failed_status'));
          }


            $digits    = array_flip(range('0', '9'));
            $lowercase = array_flip(range('a', 'z'));
            $uppercase = array_flip(range('A', 'Z')); 
            $special   = array_flip(str_split('@#$%&'));
            $combined  = array_merge($digits, $lowercase, $uppercase, $special);

            $spassword  = str_shuffle(array_rand($digits) .
                                     array_rand($lowercase) .
                                     array_rand($uppercase) . 
                                     array_rand($special) . 
                                     implode(array_rand($combined,6)));

            if ($request->user_type=='Kam') 
            {
              $input['zone'] = $request->zone;
            }
         	  $showpass = "Test@12345";
          	$input['email'] = $request->email; 
          	$input['name'] = $request->name;
          	$input['phone'] = $request->phone;
          	$input['user_type'] = $request->user_type;
          	$input['password'] =  $showpass;
            $input['login_attempt'] = 1;
          	$camData = User::create($input);

            $sub = "Account Details";
            $html = 'mail.newaccount';
            $email = $request->email;
            $data['otp'] = $showpass;
            $data['email'] = $request->email;
            $cc_email = "";
            

            (new MailService)->dotestMail($sub,$html,$email,$data,$cc_email); 
       

          	\DB::commit();

          	if($camData)
          	{
            	return response()->json(['status'=>1,'message' =>'User added successfully'],config('global.success_status'));
          	}
          	else
          	{ 
            	return response()->json(['status'=>1,'message' =>'Somthing went wrong','result' => []],config('global.success_status'));
          	} 
           

        }catch(\Exception $e){ 
          \DB::rollback(); 
          return response()->json(['status'=>0,'message' =>config('global.failed_msg'),'result' => $e->getMessage()],config('global.failed_status'));
        }
    }

    /**
     * This is for block user in admin. 
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
    */
    public function blockUserAdmin(Request $request,$user_id)
    {
    	try{         
          $getuser = User::where('id',$user_id)->first();
            if(!empty($getuser))
            { 
        		$input['user_status'] = 3;  

            	$updateuser = User::where('id',$getuser->id)->update($input);

     
            	return response()->json(['status'=>1,'message' =>'User block successfully.']);
            	 
            }
            else
            {
                return response()->json(['status'=>0,'message'=>'No data found'],200);
            } 
             
        }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
        }

    }

    /**
     * This is for un block user in admin. 
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
    */
    public function unBlockUserAdmin(Request $request,$user_id)
    {
    	try{         
            $getuser = User::where('id',$user_id)->first();
              if(!empty($getuser))
	            { 
	        		$input['user_status'] = 2;  

	            	$updateuser = User::where('id',$getuser->id)->update($input);

	     
	            	return response()->json(['status'=>1,'message' =>'User un-block successfully.']);
	            	 
	            }
	            else
	            {
	                return response()->json(['status'=>0,'message'=>'No data found'],200);
	            } 
             
          }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
          }

    }

    /**
     * This is for list of portal register customer. 
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
    */
    public function portalCustListAdmin(Request $request)
    {
        $response = [];
        try{         
          
          if($request->cust_name)
          {      
             
              $data = User::orderBy('id','desc')
                              ->where('name','LIKE',"%{$request->cust_name}%") 
                              ->where('user_type','=','C')
                              ->where('reg_by','=','P') 
                              ->get();
          }
           
          else{
              // $data = DB::table('users')
              //             ->leftjoin('address','users.id','address.user_id')                           
              //             ->select('users.id as user_id','users.id as user_id''address.*')
              //             ->get();
              $data = User::orderBy('id','desc')
                            ->where('user_type','=','C')
                            ->where('reg_by','=','P') 
                            ->get();
          }

          // dd($data);
          
          $catelist = [];
            foreach ($data as $key => $value) 
            {               
              $catdata['user_id'] = $value->id;
              $catdata['user_code'] = $value->user_code;
              $catdata['email'] = $value->email;
              $catdata['name'] = $value->name;
              $catdata['user_code'] = $value->user_code;
              $catelist[] = $catdata;
            } 
          return response()->json(['status'=>1,'message' =>'success.','result' => $catelist],200);
          
        
        }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
        }
    }

    /**
     * This is for list of portal register customer. 
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
    */
    public function adminUpCustSapCode(Request $request)
    {
      // dd('adminUpCustSapCode');
      try{         
            $getuser = User::where('id',$request->user_id)->first();
            if(!empty($getuser))
            { 
              $input['user_code'] = $request->cust_sap_code;
              $inputs['cus_code'] = $request->cust_sap_code;  

              $updateuser = User::where('id',$getuser->id)->update($input);

              $updateuser = DB::table('address')
                              ->where('user_id',$request->user_id)
                              ->update($inputs);
     
              return response()->json(['status'=>1,'message' =>'Customer SAP code updated successfully.']);
               
            }
            else
            {
              return response()->json(['status'=>0,'message'=>'No data found'],200);
            } 
             
          }catch(\Exception $e){
            $response['error'] = $e->getMessage();
            return response()->json([$response]);
          }

    }
}

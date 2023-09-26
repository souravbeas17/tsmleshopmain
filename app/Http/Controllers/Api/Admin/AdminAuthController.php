<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\User;
use App\Models\Admin;
use JWTAuth;
use Validator;
use App\ServicesMy\MailService;
use Nullix\CryptoJsAes\CryptoJsAes;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
      public function Adminregister(Request $request)
	  {
	     
	    // return $request->all();
	    
	      $validator = Validator::make($request->all(), [
	          'email' => 'required|string|email|max:255|unique:admins',
	          'name' => 'required',
	          'phone' => 'required',
	          'password'=> 'required'
	      ]);
	      if ($validator->fails()) {
	          return response()->json($validator->errors());
	      }
	      $user = Admin::create(array_merge(
	            $validator->validated(),
	            ['password' => bcrypt($request->password)]
	        ));

	      // dd($token);

	      return response()->json([
	          'success' => true,
	          'user' => $user,
	      ]);
	  }

   /**
    * Get a JWT via given credentials.
    *
    * @return \Illuminate\Http\JsonResponse
    */
   public function Adminlogin(Request $request)
   {
       // dd($request->all());
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password'=> 'required'
        ]);
        if ($validator->fails()) { 
            return response()->json($validator->errors());
        }
        
        // --------------otp validiy check -----------------------------
         $chk = Admin::where('email',$request->email)->first();

         $validity = $chk->expiry;

         $current = date('Y-m-d H:i:s');

         if($current > $validity)
         {
            // dd('expire');

            return response()->json(['status'=>0,'success' => false,'message' => array('OTP expired.')]);  
         }


        // -------------------------------------------------------------

        if($chk->otp == $request->otp)
        {
        $credentials = request(['email', 'password']);
        if (!$token = auth()->guard('admins')->attempt($credentials)) {
 
            return response()->json(['status'=>0,'success' => false,'message' => array('Invalid Email or Password .')]);  
        }

        $jwt_token =  $this->respondWithToken($token);
        
        return response()->json([
            'status'=>1,
            'success' => true,
            'name'  => Auth::guard('admins')->user()->name,
            'id'  => Auth::guard('admins')->id(),
            'token' => $jwt_token,
        ]);
    }else{

         return response()->json(['status'=>0,'success' => false,'message' => array('Invalid OTP.')]);  
    }
   }


   /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth('admins')->user());
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('admins')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->guard('admins')->factory()->getTTL() * 60
        ]);
    }

    // -------------- admin login otp -------------------------------

    public function adminloginotp(Request $request)
    {   
        try{


        $encrypted = json_encode($request->all());
        // $json = json_encode($encrypted1);
        $password = "123456";

        $decrypted = CryptoJsAes::decrypt($encrypted, $password);
        // dd($decrypted);
        $datestime = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +10 minutes"));
        $inputotp['expiry'] =$datestime; 
        $otp = random_int(100000, 999999);
        $inputotp['otp'] =$otp; 
        $categoryData = Admin::where('email',$decrypted['email'])->update($inputotp); 
        $sub = "OTP For Login";
        $html = 'mail.Otpverificationmail';
        $data['otp'] = $otp;
        $cc_email = "";
        $email = $decrypted['email'];
        // dd($email);
        
        (new MailService)->dotestMail($sub,$html,$email,$data,$cc_email); 

        return response()->json(['status'=>1,'message' =>'success.','result' => 'Otp sent to email id'],200);
             // return response()->json(['status'=>1,$response],200);
              
            
            }catch(\Exception $e){
                $response['error'] = $e->getMessage();
                return response()->json([$response]);
            }
    }


       /**
    * Get a JWT via given credentials.
    *
    * @return \Illuminate\Http\JsonResponse
    */
   public function adminForgetPass(Request $request)
   {
       // dd($request->all());

        $encrypted = json_encode($request->all());
        // $json = json_encode($encrypted1);
        $password = "123456";

        $decrypted = CryptoJsAes::decrypt($encrypted, $password);


        $validator = Validator::make($decrypted, [
            'email' => 'required|string|email|max:255'
            
        ]);
        if ($validator->fails()) { 
            return response()->json($validator->errors());
        }
        
        // --------------otp validiy check -----------------------------
         $chk = Admin::where('email',$decrypted['email'])->first();

         $validity = $chk->expiry;

         $current = date('Y-m-d H:i:s');

         if($current > $validity)
         {
            // dd('expire');

            return response()->json(['status'=>0,'success' => false,'message' => array('OTP expired.')]);  
         }


        // -------------------------------------------------------------

        if($chk->otp == $decrypted['otp'])
        {
             if($decrypted['pass'] == $decrypted['conpass'])
             {
                 $data['password'] = \Hash::make($decrypted['pass']);
                 Admin::where('email',$decrypted['email'])->update($data);

                    return response()->json([
                        'status'=>1,
                        'success' => true,
                        'message'  => 'Password updated sucessfully'
                        
                    ]);
            }else{

                       return response()->json([
                            'status'=>1,
                            'success' => true,
                            'message'  => 'Password and Confirm password does not match.'
                            
                          ]);

            }
        }

      else{

         return response()->json(['status'=>0,'success' => false,'message' => 'Invalid OTP.']);  
    }
   }
}

<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        // $this->reportable(function (Throwable $e) {
        //     //
        // });

        $this->renderable(function (ThrottleRequestsException $e) {
            $getPathInfo = $e->getTrace()[0]['args'][0]->getPathInfo();
            $explodes = explode('/', $getPathInfo);
            foreach ($explodes as $key => $value) {
                $pathInfo = $value;
            }
            if($pathInfo == 'chk_email'){
                return response()->json(['status'=>$e->getCode(),'message' =>'success','result'=>'Too many attempts'],200);
            }
     
        });
    }
}

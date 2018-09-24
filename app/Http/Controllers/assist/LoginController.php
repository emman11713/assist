<?php

namespace App\Http\Controllers\assist;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;;

class LoginController extends Controller
{
    //
    public function postLogin(Request $request){
        $accounttables = DB::table('account_tables')->where('username', $request->user)->first();        
        if($accounttables){
            if($accounttables->password == $request->pass){
                $request->session()->put('USERNAME',$accounttables->username);
                $url = "index.php/checknew";
                $data = array('status' => 'ok', 'url' => $url); 
                return $data;
            }else{
                return array('status' => 'fail1', 'url' => "x");
            }
        }else{
            return array('status' => 'fail2', 'url' => "x");
        }
    }
}

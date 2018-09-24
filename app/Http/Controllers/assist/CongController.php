<?php

namespace App\Http\Controllers\assist;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CongController extends Controller
{
    //
    public function money(Request $request){
        $username = $request->session()->get('USERNAME');
        $savemoney = DB::table('savemoney')->where('username',$username)->first();
        $money = DB::table('money')->where('username',$username)->first();
        $acc= DB::table('account_tables')->where('username',$username)->first();
        $assistdetails = DB::table('assist_details')->
                        select('firstname','middlename','lastname','formsofassistance',
                        DB::raw('SUM(amountofassistance) as totalamountofassistance, count(*) as totalcount'))->
                        where('id1',$acc->code)->groupBy('firstname','middlename','lastname','formsofassistance')
                        ->get();
        return view('assist.pages.congressman.money',['savemoney' => $savemoney,'money' => $money,'assistdetails' => $assistdetails]);
    }


    public function account(Request $request){
        $fcode = $request->t;
        return view('assist.pages.congressman.account', ['fcode' => $fcode]);
    }

    public function accountsubmit(Request $request){
        if($request->accountbtn=='Save Changes'){
            $username = $request->session()->get('USERNAME');
            $accounttables = DB::table('account_tables')->where('username', $username)->first();
            if($accounttables){
                if($accounttables->password == $request->passwordp){
                    if($request->newpasswordp == $request->confirmpasswordp){
                        DB::table('account_tables')
                        ->where('username', $username)
                        ->update(['password' => $request->newpasswordp]);
                        return redirect('/C/account?t=2');
                    }
                    else{
                        return redirect('/C/account?t=1');
                    }
                }
                else{
                    return redirect('/C/account?t=0');
                }
            }
            else{
                return redirect('/C/account?t=3');
            }
        }
    }


    
    public function username(Request $request){
        $fcode = $request->t;
        $acc= DB::table('account_tables')->where('username',$request->session()->get('USERNAME'))->first();
        return view('assist.pages.congressman.username', ['fcode' => $fcode, 'fullname' => $acc->name]); 
    }

    public function usernamesubmit(Request $request){
        if($request->accountbtn=='Save Changes'){
        $username = $request->session()->get('USERNAME');
        $acc = DB::table("account_tables")->where('username', $username)->first();
        if($acc){
            if($request->_pass == $acc->password){
                DB::table('account_tables')
                    ->where('username', $username)
                    ->update(['name' => $request->_fullname]);
                    return redirect('/C/username?t=2');
            }else{
                return redirect('/C/username?t=1');
            }
        }else{
            return redirect('/');
        }
    }
    }


    public function view(Request $request){
        $hiddenagenda = $request->ha;
        $account = DB::table('assist_details')->where('id', $hiddenagenda)->first();
        if($account->formsofassistance=="Cash For Work"){
            $back="cash";
        }else{
            $back=strtolower($account->formsofassistance);
        }
        $cong = DB::table('account_tables')->where('username',$request->session()->get("USERNAME"))->first();

        if("District ".$cong->district==$account->district || $cong->name == $account->referredby){
            return view('assist.pages.congressman.congviewrecord', ['account' => $account, 'back' => $back]);
        }else if($account){
            return redirect('/C/'.$back);
        }else{
            return redirect('/');
        }
    }



    public function Cburial(Request $request){
        $username = $request->session()->get('USERNAME');
        $acc = DB::table('account_tables')->where('username',$username)->first();

        $assistdetails = DB::table('assist_details')
                        ->where('formsofassistance','=','burial')
                        ->where(function($query) use ($acc){
                            $query->where('district',"=","District ".$acc->district)
                                  ->orWhere('referredby',"=",$acc->name);
                        })
                        ->orderByRaw('id DESC')
                        ->get();
        return view('assist.pages.congressman.congburial', ['assistdetails' => $assistdetails]);
    }


    public function Cmedical(Request $request){
        $username = $request->session()->get('USERNAME');
        $acc = DB::table('account_tables')->where('username',$username)->first();

        $assistdetails = DB::table('assist_details')
                        ->where('formsofassistance','=','medical')
                        ->where(function($query) use ($acc){
                            $query->where('district',"=","District ".$acc->district)
                                  ->orWhere('referredby',"=",$acc->name);
                        })
                        ->orderByRaw('id DESC')
                        ->get();
        return view('assist.pages.congressman.congmedical', ['assistdetails' => $assistdetails]);
    }


    public function Ceducational(Request $request){
        $username = $request->session()->get('USERNAME');
        $acc = DB::table('account_tables')->where('username',$username)->first();

        $assistdetails = DB::table('assist_details')
                        ->where('formsofassistance','=','educational')
                        ->where(function($query) use ($acc){
                            $query->where('district',"=","District ".$acc->district)
                                  ->orWhere('referredby',"=",$acc->name);
                        })
                        ->orderByRaw('id DESC')
                        ->get();
        return view('assist.pages.congressman.congeducational', ['assistdetails' => $assistdetails]);
    }



    public function Ctransportation(Request $request){
        $username = $request->session()->get('USERNAME');
        $acc = DB::table('account_tables')->where('username',$username)->first();

        $assistdetails = DB::table('assist_details')
                        ->where('formsofassistance','=','transportation')
                        ->where(function($query) use ($acc){
                            $query->where('district',"=","District ".$acc->district)
                                  ->orWhere('referredby',"=",$acc->name);
                        })
                        ->orderByRaw('id DESC')
                        ->get();
        return view('assist.pages.congressman.congtransportation', ['assistdetails' => $assistdetails]);
    }

    
    public function Ccash(Request $request){
        $username = $request->session()->get('USERNAME');
        $acc = DB::table('account_tables')->where('username',$username)->first();

        $assistdetails = DB::table('assist_details')
                        ->where('formsofassistance','=','Cash For Work')
                        ->where(function($query) use ($acc){
                            $query->where('district',"=","District ".$acc->district)
                                  ->orWhere('referredby',"=",$acc->name);
                        })
                        ->orderByRaw('id DESC')
                        ->get();
        return view('assist.pages.congressman.congcash', ['assistdetails' => $assistdetails]);
    }   

}

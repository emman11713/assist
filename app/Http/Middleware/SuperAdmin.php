<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\DB;
use Closure;

class SuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if($request->session()->has('USERNAME')){
            $role = DB::table('account_tables')->where('username',$request->session()->get('USERNAME'))->first();
            
            $burial = count(DB::table('assist_details')->where('formsofassistance',"Burial")->get());
            $medical = count(DB::table('assist_details')->where('formsofassistance',"Medical")->get());
            $educational = count(DB::table('assist_details')->where('formsofassistance',"Educational")->get());
            $transportation = count(DB::table('assist_details')->where('formsofassistance',"Transportation")->get());
            $cash = count(DB::table('assist_details')->where('formsofassistance',"Cash For Work")->get());

            $request->session()->put('burial',$burial);
            $request->session()->put('medical',$medical);
            $request->session()->put('educational',$educational);
            $request->session()->put('transportation',$transportation);
            $request->session()->put('cash',$cash);

            if($role->role == "superadmin"){
                return $next($request);
            }else{
                return redirect('/C/burial');
            }
        }else{
            return redirect('/');
        }
    }
}

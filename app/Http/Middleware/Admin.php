<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\DB;
use Closure;

class Admin
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


            $burial = count(DB::table('assist_details')->where('formsofassistance',"Burial")
                                                        ->where(function($query) use ($role){
                                                            $query->where('district',"=","District ".$role->district)
                                                                ->orWhere('referredby',"=",$role->name);
                                                        })->get());

            $medical = count(DB::table('assist_details')->where('formsofassistance',"Medical")
                                                        ->where(function($query) use ($role){
                                                            $query->where('district',"=","District ".$role->district)
                                                                ->orWhere('referredby',"=",$role->name);
                                                        })->get());

            $educational = count(DB::table('assist_details')->where('formsofassistance',"Educational")
                                                        ->where(function($query) use ($role){
                                                            $query->where('district',"=","District ".$role->district)
                                                                ->orWhere('referredby',"=",$role->name);
                                                        })->get());
            $transportation = count(DB::table('assist_details')->where('formsofassistance',"Transportation")
                                                        ->where(function($query) use ($role){
                                                            $query->where('district',"=","District ".$role->district)
                                                                ->orWhere('referredby',"=",$role->name);
                                                        })->get());
            $cash = count(DB::table('assist_details')->where('formsofassistance',"Cash For Work")
                                                        ->where(function($query) use ($role){
                                                            $query->where('district',"=","District ".$role->district)
                                                                ->orWhere('referredby',"=",$role->name);
                                                        })->get());

            $request->session()->put('burial',$burial);
            $request->session()->put('medical',$medical);
            $request->session()->put('educational',$educational);
            $request->session()->put('transportation',$transportation);
            $request->session()->put('cash',$cash);

            if($role->role == "admin"){
                $money = DB::table('money')->where('username',$request->session()->get('USERNAME'))->first();
                $request->session()->put('MONEY',number_format($money->realmoney, 2, '.', ','));
                
                return $next($request);
            }else{
                return redirect('/checknew');
            }
        }else{
            return redirect('/');
        }
    }
}

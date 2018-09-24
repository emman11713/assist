<?php

namespace App\Http\Controllers\assist;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Carbon\Carbon;

class TypeController extends Controller
{
    public function money(Request $request){
        $acc= DB::table('account_tables')->get();
        $year = DB::table('budget_records')->where('isActive','1')->get();
        $firstrelease = DB::table('first_release_remaining')->get();
        if(!count($year)>0){
            echo '<script>alert("There is no active year at the moment. Please add budget in order to view this page.");
            location.replace("budget");</script>';
        }
        $budgetrelease = DB::table('budget_release')->where('isActive','1')->first();
        if($budgetrelease){
            $release = $budgetrelease->release=="1"?"Release 1 is active":"Release 2 is Active";
            $release2 = $budgetrelease->release=="1"?"1":"2";
        }else{
            $release = "No Release Yet";
            $release2 = "";
        }
        $moneyusername= DB::table('money')->get();
        $sdo= DB::table('sdo')->get();
        return view('assist.pages.money', ['firstrelease'=>$firstrelease,'release2'=>$release2, 'acc' => $acc,'moneyusername' => $moneyusername,'sdo' => $sdo,'year'=>$year, 'release'=>$release]);
    }


    public function budget(Request $request){
        
        //budget record checker
        $budgetrecord = DB::table('budget_records')->where('isActive','1')->get();
        $noBudget = count($budgetrecord)>0 ? false : true;
        $savemoney = DB::table('savemoney')->where('username','QC')->first();
        


        //release checker
        if(!$noBudget){
            $budgetreleasecheck = DB::table('budget_release')->where('year',$budgetrecord->first()->year)->get();
            if(count($budgetreleasecheck)>0){
                $hasReleasedAll = count($budgetreleasecheck)==2 ? true : false;
                $budgetrelease = DB::table('budget_release')->where('isActive','1')->get();
                    if(count($budgetrelease)>0){
                        $release = $budgetrelease->first()->release == '1' ? '2' : '1';
                    }else{
                        $release = '1';
                    }
                $thisyear = $budgetrecord->first()->year;
                $remaining = (!$noBudget && $release=="2") ? $budgetrecord->first()->budgetWithRemaining-10000000 : "";
            }else{
                $hasReleasedAll = false;
                $budgetrelease = false;
                $thisyear = $budgetrecord->first()->year;
                $release = '1';
                $remaining = 0;
            }
            
        }else{
            $budgetreleasecheck = false;
            $hasReleasedAll = false;
            $budgetrelease = false;
            $thisyear = 0;
            $release = 0;
            $remaining = 0;
        }

        //get 2 releases data
        if($hasReleasedAll){
            $releases = DB::table("budget_release")->where('year',$budgetrecord->first()->year)->get();
            $release1 = $releases->where('release','1')->first()->moneyreleased;
            $active1 = $releases->where('release','1')->first()->isActive;
            $release2 = $releases->where('release','2')->first()->moneyreleased;
            $active2 = $releases->where('release','2')->first()->isActive;
            $remaining1 = number_format(DB::table('first_release_remaining')->where('username','QC')->first()->realmoney, 2, '.', ',');
        }else{
            $release1 = 0;
            $release2 = 0;
            $active1 = 0;
            $active2 = 0;
            $remaining1=0;
        }

        //get data
        $moneyusername= DB::table('money')->get();
        $congressmen = DB::table('account_tables')->where('role','admin')->get();
        $congarray = [];
        $i = 0;
        foreach($congressmen as $cong1){
            $congarray[$i] = $cong1->name;
            $i++;
        }
        return view('assist.pages.budget', [
                'hasReleasedAll' => $hasReleasedAll,
                'remaining' => $remaining,
                'year'=> $thisyear,
                'noBudget' => $noBudget,
                'budgetrelease' => $budgetrelease,
                'release' => $release,
                'moneyusername' => $moneyusername,
                'release2' => number_format($release2, 2, '.', ','),
                'release1' => number_format($release1, 2, '.', ','),
                'active1' => $active1,
                'active2' => $active2,
                'remaining1' => $remaining1,
                'congarray' => $congarray ]);
    }
    public function budgetPost(Request $request){



        
        if($request->what=='releaseAllocation'){
            $money= DB::table('money')->get();
            $lastyearremainings = DB::table('last_year_remainings')->where('isActive','1')->get();
            $budgetrecord= DB::table('budget_records')->where('isActive','1')->get();
            $remArray = [];

            
            if($request->release=="1"){

                DB::table('budget_release')->update(['isActive' => "0"]);
                $getlastid = DB::table('budget_release')->insertGetID([
                    'year' => $budgetrecord->first()->year,
                    'release' => $request->release,
                    'moneyreleased'=> $request->congqc,
                    'isActive' => "1",
                ]);

                foreach($money as $m){
                    if($m->username=='QC'){
                           $budget=$request->congqc;
                    }
                    if($m->username=='district1')
                            $budget=$request->cong1;
                    else if($m->username=='district2')
                            $budget=$request->cong2;
                    else if($m->username=='district3')
                            $budget=$request->cong3;
                    else if($m->username=='district4')
                            $budget=$request->cong4;
                    else if($m->username=='district5')
                            $budget=$request->cong5;
                    else if($m->username=='district6')
                            $budget=$request->cong6;
                    else if($m->username=='district7')
                            $budget=$request->cong7;
                    else if($m->username=='district8')
                            $budget=$request->cong8;
                    
                    DB::table('savemoney')
                        ->where('username', $m->username)
                        ->update(['realmoney' => $budget]);
                    DB::table('money')
                        ->where('username', $m->username)
                        ->update(['realmoney' => $budget]);


                    $firstreleaseremaining = DB::table('first_release_remaining')->where('username',$m->username)->first();
                    if($firstreleaseremaining){
                        DB::table('first_release_remaining')
                        ->where('username', $m->username)
                        ->update([
                            'realmoney' => $budget,
                            'releaseid' => $getlastid,
                        ]);
                    }else{
                        DB::table('first_release_remaining')
                        ->insert([
                            'username' => $m->username,
                            'realmoney' => $budget,
                            'releaseid' => $getlastid,
                        ]);
                    }
                }

            }else if($request->release=="2"){


                $firstrelease = DB::table('first_release_remaining')->where('username','QC')->first();
                foreach($lastyearremainings as $r){
                    $remArray[$r->username] = $r->realmoney;
                }

                foreach($money as $m){
                    if($m->username=='QC'){
                           $budget=$m->realmoney+$request->congqc+$remArray[$m->username];
                    }
                    if($m->username=='district1')
                            $budget=$m->realmoney+$request->cong1+$remArray[$m->username];
                    else if($m->username=='district2')
                            $budget=$m->realmoney+$request->cong2+$remArray[$m->username];
                    else if($m->username=='district3')
                            $budget=$m->realmoney+$request->cong3+$remArray[$m->username];
                    else if($m->username=='district4')
                            $budget=$m->realmoney+$request->cong4+$remArray[$m->username];
                    else if($m->username=='district5')
                            $budget=$m->realmoney+$request->cong5+$remArray[$m->username];
                    else if($m->username=='district6')
                            $budget=$m->realmoney+$request->cong6+$remArray[$m->username];
                    else if($m->username=='district7')
                            $budget=$m->realmoney+$request->cong7+$remArray[$m->username];
                    else if($m->username=='district8')
                            $budget=$m->realmoney+$request->cong8+$remArray[$m->username];
                    
                    DB::table('savemoney')
                        ->where('username', $m->username)
                        ->update(['realmoney' => $budget]);
                    DB::table('money')
                        ->where('username', $m->username)
                        ->update(['realmoney' => $budget]);
                }

                if($firstrelease->realmoney=='0'){
                    DB::table('budget_release')->update(['isActive' => "0"]);
                    DB::table('budget_release')->insert([
                        'year' => $budgetrecord->first()->year,
                        'release' => $request->release,
                        'moneyreleased'=> $request->congqc,
                        'isActive' => "1",
                    ]);
                }else{
                    DB::table('budget_release')->insert([
                        'year' => $budgetrecord->first()->year,
                        'release' => $request->release,
                        'moneyreleased'=> $request->congqc,
                        'isActive' => "0",
                    ]);
                }
                
            }

            return array('status' => 'ok');
        }









        else if($request->what=="saveBudget"){
            $money = DB::table('money')->get();
                

            //LAST REMAINING
            $lastremaining = DB::table('last_year_remainings')->where('isActive','1')->get();
            DB::table('last_year_remainings')->update(['isActive'=>'0']);


            if(count($lastremaining)>0){
                foreach($money as $m){
                    DB::table('last_year_remainings')->insert([
                        'username'=>$m->username,
                        'realmoney'=>$m->realmoney,
                        'isActive'=>'1'
                    ]); 
                }
                $totalbudget = $money->where('username','QC')->first()->realmoney + $request->budget; 
                
            }else{
                foreach($money as $m){
                    DB::table('last_year_remainings')->insert([
                        'username'=>$m->username,
                        'realmoney'=>$m->realmoney,
                        'isActive'=>'1'
                    ]);
                }
                $totalbudget = $request->budget;
            }

            DB::table('money')->update(['realmoney'=>'0']);
            DB::table('budget_records')->update(['isActive' => '0']);
            DB::table('budget_records')->insert([
                'budget' => $request->budget,
                'year' => $request->year,
                'budgetWithRemaining' => $totalbudget,
                'isActive' => '1',
            ]);

            return array('status' => 'ok', 'm' => $request->year);
        }







        else if($request->what=="resetYear"){
            $account = DB::table("account_tables")
                                ->where('username',$request->session()->get("USERNAME"))
                                ->where('password',$request->password)
                                ->get();
            if(count($account)>0){
                $status = 'ok';
                DB::table("budget_records")->update(['isActive' => '0']);
                DB::table("budget_release")->update(['isActive' => '0']);
                $sdomoney = DB::table('sdo')->where('sdoactivate','1')->first()->sdomoney;
                $money = DB::table('money')->where('username','QC')->first();
                DB::table('sdo')->where('sdoactivate','1')->update(['sdomoney'=>'0']);
                DB::table('money')->where('username','QC')->update(['realmoney'=>$sdomoney+$money->realmoney]);
                DB::table('sdo')->update(['sdoactivate'=>'0']);
                DB::table('sdo')->update(['sdoliquidation'=>'0']);
                DB::table('sdo')->where('id','1')->update(['sdoactivate'=>'1']);
            }else{
                $status = 'errorpw';
            }
            return array('status' => $status);
        }




        else if($request->what=="activateRelease2"){
            $budgetrelease = DB::table('budget_release')->where('isActive','1')->first();
            if($budgetrelease->release=='1'){
                $firstrelease = DB::table('first_release_remaining')->where('releaseid',$budgetrelease->id)->get();
                if($firstrelease->where('username','QC')->first()->realmoney=='0'){
                    DB::table('budget_release')->update(['isActive'=>'0']);
                    DB::table('budget_release')
                            ->where('year',$budgetrelease->year)
                            ->where('release','2')
                            ->update(['isActive'=>'1']);
                    $status = 'ok';
                    $message = "";
                }else{
                    $status = 'failed';
                    $message = "";
                }
                
            }else{
                return redirect('budget');
            }

            return array('status'=>$status, 'message'=>$message);
        }
        
    
    }

    public function account(Request $request){
        $fcode = $request->t;
        return view('assist.pages.account', ['fcode' => $fcode]);
        
    }
    public function username(Request $request){
        $fcode = $request->t;
        $acc= DB::table('account_tables')->where('username',$request->session()->get('USERNAME'))->first();
        return view('assist.pages.username', ['fcode' => $fcode, 'fullname' => $acc->name]); 
    }

    public function usernamesubmit(Request $request){
        if($request->accountbtn=="Save Changes"){
        $username = $request->session()->get('USERNAME');
        $acc = DB::table("account_tables")->where('username', $username)->first();
        if($acc){
            if($request->_pass == $acc->password){
                DB::table('account_tables')
                    ->where('username', $username)
                    ->update(['name' => $request->_fullname]);
                    return redirect('/username?t=2');
            }else{
                return redirect('/username?t=1');
            }
        }else{
            return redirect('/');
        }
        }
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
                        return redirect('/account?t=2');
                    }
                    else{
                        return redirect('/account?t=1');
                    }
                }
                else{
                    return redirect('/account?t=0');
                }
            }
            else{
                return redirect('/account?t=3');
            }
        }
    }

    public function burial(Request $request){
        $assistdetails = DB::table('assist_details')->where('formsofassistance', 'Burial')->orderByRaw('id DESC')->get();
        return view('assist.pages.burial', ['assistdetails' => $assistdetails]);
    }
    
    public function medical(Request $request){
        $assistdetails = DB::table('assist_details')->where('formsofassistance', 'Medical')->orderByRaw('id DESC')->get();
        return view('assist.pages.medical', ['assistdetails' => $assistdetails]);
    }
    
    public function educational(Request $request){
        $assistdetails = DB::table('assist_details')->where('formsofassistance', 'Educational')->orderByRaw('id DESC')->get();
        return view('assist.pages.educational', ['assistdetails' => $assistdetails]);
    }
    
    public function transportation(Request $request){
        $assistdetails = DB::table('assist_details')->where('formsofassistance', 'Transportation')->orderByRaw('id DESC')->get();
        return view('assist.pages.transportation', ['assistdetails' => $assistdetails]);
    }

    public function cash(Request $request){
        $assistdetails = DB::table('assist_details')->where('formsofassistance', 'Cash For Work')->orderByRaw('id DESC')->get();
        return view('assist.pages.cash', ['assistdetails' => $assistdetails]);
    }

    public function addrecord(Request $request){

        $username = $request->session()->get('USERNAME');
        $account = DB::table('account_tables')->where('username', $username)->first();
        $money = DB::table('money')->get();
        $barangaydistrict = DB::table('barangay_district')->orderByRaw('barangay ASC')->get();
        $sdovalidation = DB::table('sdo')->where('sdoactivate', 1)->first();
        $accounttables = DB::table('account_tables')->orderByRaw('name')->get();


        $budgetrecord2temp = DB::table('budget_records')->orderByRaw('id DESC')->get();
        $budgetrecord2 = $budgetrecord2temp->first();
        if(count($budgetrecord2temp)>0){
            $savemoneyqc=$budgetrecord2->budgetWithRemaining*.010;

            //first release checker
            $firstrelease = DB::table('first_release_remaining')->get();
            $budgetrelease = DB::table('budget_release')->where('isActive','1')->first();
            if(!$budgetrelease){
                return redirect('budget');
            }
            
        }else{
            return redirect('budget');
        }


        $formsofassistancelimiter = DB::table('budget_release')->where('isActive', '1')->first();
        if(!$formsofassistancelimiter){
            return redirect('budget');
        }
        if($sdovalidation->sdomoney>0)
            return view('assist.pages.addrecord', ['account' => $account, 
                                                    'accounttables' => $accounttables, 
                                                    'barangaydistrict' => $barangaydistrict,
                                                    'savemoneyqc' => $savemoneyqc,
                                                    'ctr' => 1, 
                                                    'formsofassistancelimiter' => $formsofassistancelimiter,
                                                    'firstrelease' => $firstrelease,
                                                    'money' => $money]);
        else
            return redirect('checknew');
    }

    public function view(Request $request){
        $hiddenagenda = $request->ha;
        $account = DB::table('assist_details')->where('id', $hiddenagenda)->first();
        if($account->formsofassistance=="Cash For Work"){
            $back="cash";
        }else{
            $back=strtolower($account->formsofassistance);
        }
        if($account){
            return view('assist.pages.view', ['account' => $account, 'back' => $back]);
        }else{
            return redirect('/');
        }
    }

    

    public function update(Request $request){
        $hiddenagenda = $request->ha;
        $account = DB::table('assist_details')->where('id', $hiddenagenda)->first();
        if($account){
            return view('assist.pages.updaterecord', ['account' => $account]);
        }else{
            return redirect('/');
        }
    }


    public function addrecordpost(Request $request){
        if($request->btn=="Add Record"){
        //DUPLICATE PREVENTION
            $temp1 = DB::table('account_reference')
                            ->where('firstname',$request->firstname)
                            ->where('middlename',$request->middlename)
                            ->where('lastname',$request->lastname)
                            ->where('barangay',$request->barangay)
                            ->where('dateofbirth',$request->dateofbirth)
                            ->first();
            if($temp1){
                if($request->formsofassistance=="Medical"){
                    echo "<script>alert('This applicant is already registered!');
                    location.replace('medical');</script>";
                }
                else if($request->formsofassistance=="Cash For Work"){
                    echo "<script>alert('This applicant is already registered!');
                    location.replace('cash');</script>";
                }
                else if($request->formsofassistance=="Burial"){
                    echo "<script>alert('This applicant is already registered!');
                    location.replace('burial');</script>";
                }
                else if($request->formsofassistance=="Transportation"){
                    echo "<script>alert('This applicant is already registered!');
                    location.replace('transportation');</script>";
                }
                else if($request->formsofassistance=="Educational"){
                    echo "<script>alert('This applicant is already registered!');
                    location.replace('educational');</script>";
                }
            }
        //DUPLICATE PREVENTION

        $dt = Carbon::now();
        $datenow = $dt->toDateString();

        // CREATION OF UNIQUE 
        $date1 = date_create($datenow);
        $datenow2 = date_format($date1,'dMy');
        $congressman = DB::table('account_tables')->where('username',$request->referredby)->first();
        $availableday = DB::table('assist_details')->orderByRaw('id DESC')->first();
        if($availableday){
            $availableid = $availableday->id+1;
        }else{
            $availableid = 1;
        }
        $id1= $congressman->code;
        $id2= strtoupper($datenow2);
        $id3= sprintf('%06d', $availableid);

        $referredbyname = $congressman->name;

        $budgetrelease = DB::table('budget_release')->where('isActive','1')->first()->release;
        if($budgetrelease=='2'){
            $money = DB::table('money')->where('username',$request->referredby)->first();
        }else if($budgetrelease=='1'){
            $money = DB::table('first_release_remaining')->where('username',$request->referredby)->first();
        }

        $budgetrecord2 = DB::table('budget_records')->orderByRaw('id DESC')->first();

        $sdo = DB::table('sdo')->where('sdoactivate','1')->first();
        


        if($sdo->sdomoney>=$request->amountofassistance){
        if($money->realmoney>=$request->amountofassistance){
        if($request->bill>=$request->amountofassistance){


        if($sdo->sdomoney>$request->amountofassistance){
            $sdomoney=$sdo->sdomoney-$request->amountofassistance;
            DB::table('sdo')
                    ->where('id', $sdo->id)
                    ->update(['sdomoney' => $sdomoney]);
            }
        else if($sdo->sdomoney==$request->amountofassistance){
            $sdomoney2=$sdo->sdomoney-$request->amountofassistance;
            $sdomoney=0;
            
            
            if($sdo->id==1){
            $sdoactivate=2;
            }
            elseif($sdo->id==2){
            $sdoactivate=3;
            }
            elseif($sdo->id==3){
            $sdoactivate=1;
            }
            DB::table('sdo')
                ->where('id', $sdo->id)
                ->update(['sdomoney' => 0, 'sdoactivate' => 0,'sdoliquidation' => 1]);
            DB::table('sdo')
                ->where('id', $sdoactivate)
                ->update(['sdoactivate' => 1]);
        }

        $realmoneycongressman=$money->realmoney - $request->amountofassistance;

        DB::table('money')
                ->where('username', $request->referredby)
                ->update(['realmoney' => $realmoneycongressman]);
        
        //first release checker
        $budgetrelease = DB::table('budget_release')->where('isActive','1')->first()->release;
        if($budgetrelease=='1'){
            $firstreleasemoney = DB::table('first_release_remaining')->get();
            DB::table('first_release_remaining')
                ->where('username', $request->referredby)
                ->update(['realmoney' => $firstreleasemoney->where('username',$request->referredby)->first()->realmoney-$request->amountofassistance]);
            DB::table('first_release_remaining')
                ->where('username', 'QC')
                ->update(['realmoney' => $firstreleasemoney->where('username','QC')->first()->realmoney-$request->amountofassistance]); 
        }
        
        if($request->formsofassistance=='Burial' )
            $limit=50000;
        else if($request->formsofassistance=='Medical' ){
            if($request->bill>=1000000 )
                $limit=$budgetrecord2->budgetWithRemaining*.010;
            else
                $limit=30000;
        }
        else if($request->formsofassistance=='Educational' )
            $limit=10000;
        else if($request->formsofassistance=='Transportation' )
            $limit=20000;

        $getlastid = DB::table('account_reference')->insertGetID(
                [
                'lastname' => $request->lastname,
                'firstname' => $request->firstname, 
                'middlename' => $request->middlename, 
                'amountofassistance' => $request->amountofassistance,
                'barangay' => $request->barangay,
                'dateofbirth' => $request->dateofbirth,
                ]);

        if($request->formsofassistance=='Cash For Work' )
            $canassist = '1';
        else
            $canassist = $request->amountofassistance<$limit ? '1' : '0';

        /*if($request->amountofassistance<$limitmedical){
            $getlastid = DB::table('account_reference')->insertGetID(
                [
                'lastname' => $request->lastname,
                'firstname' => $request->firstname, 
                'middlename' => $request->middlename, 
                'formsofassistance' => $request->formsofassistance, 
                'amountofassistance' => $request->amountofassistance,
                'barangay' => $request->barangay,
                'dateofbirth' => $request->dateofbirth,
                'lastdate' => $datenow,
                ]);
        }
        else{
            $getlastid = DB::table('account_reference')->insertGetID(
                [
                'lastname' => $request->lastname,
                'firstname' => $request->firstname, 
                'middlename' => $request->middlename, 
                'formsofassistance' => $request->formsofassistance, 
                'amountofassistance' => $request->amountofassistance,
                'barangay' => $request->barangay,
                'dateofbirth' => $request->dateofbirth,
                'lastdate' => $datenow,
                ]);
        }*/




        if($request->formsofassistance=="Burial"){
            /*$validator=$request->validate([
                'lastname' => 'required',
                'firstname' => 'required',
                'middlename' => 'required',
                'houseno' => 'required',
                'street' => 'required',
                'barangay' => 'required',
                'district' => 'required',
                'dateofbirth' => 'required',
                'formsofassistance' => 'required',
                'deceasedlastname' => 'required',
                'deceasedfirstname' => 'required',
                'deceasedmiddlename' => 'required',
                'date' => 'required',
                'serviceprovider' => 'required',
                'dateofdeath' => 'required',    
                'amountofassistance' => 'required',
                'referredby' => 'required',
            ]);*/
            DB::table('assist_details')->insert(
                [
                'lastname' => $request->lastname,
                'firstname' => $request->firstname, 
                'middlename' => $request->middlename, 
                'houseno' => $request->houseno, 
                'street' => $request->street,  
                'barangay' => $request->barangay, 
                'district' => $request->district, 
                'dateofbirth' => $request->dateofbirth,
                'formsofassistance' => $request->formsofassistance, 
                'beneficiarylastname' => $request->deceasedlastname,
                'beneficiaryfirstname' => $request->deceasedfirstname,
                'beneficiarymiddlename' => $request->deceasedmiddlename,
                'serviceprovider' => $request->serviceprovider,
                'dateofdeath' => $request->dateofdeath,
                'bill' => $request->bill,
                'amountofassistance' => $request->amountofassistance,
                'referredby' => $referredbyname,
                'notes' => $request->notes,
                'id1' => $id1,
                'id2' => $id2,
                'id3' => $id3,
                'beneficiaryid' => $getlastid,
                'canassist' => $canassist,
                'date' => $dt,
                ]);
                echo "<script>alert('Record has been added!')</script>";
                echo "<script>location.replace('burial')</script>";
            
            }

        else if($request->formsofassistance=="Medical"){
            DB::table('assist_details')->insert(
                [
                'lastname' => $request->lastname,
                'firstname' => $request->firstname, 
                'middlename' => $request->middlename, 
                'houseno' => $request->houseno, 
                'street' => $request->street,  
                'barangay' => $request->barangay, 
                'district' => $request->district, 
                'dateofbirth' => $request->dateofbirth, 
                'formsofassistance' => $request->formsofassistance, 
                'beneficiarylastname' => $request->patientlastname,
                'beneficiaryfirstname' => $request->patientfirstname,
                'beneficiarymiddlename' => $request->patientmiddlename,
                'hospitalname' => $request->hospitalname,
                'disease' => $request->disease,
                'bill' => $request->bill,
                'amountofassistance' => $request->amountofassistance,
                'referredby' => $referredbyname,
                'notes' => $request->notes,
                'id1' => $id1,
                'id2' => $id2,
                'id3' => $id3,
                'beneficiaryid' => $getlastid,
                'canassist' => $canassist,
                'date' => $dt,
                ]);
                echo "<script>alert('Record has been added!')</script>";
                echo "<script>location.replace('medical')</script>";
            }

        else if($request->formsofassistance=="Educational"){
            DB::table('assist_details')->insert(
                [
                'lastname' => $request->lastname,
                'firstname' => $request->firstname, 
                'middlename' => $request->middlename, 
                'houseno' => $request->houseno, 
                'street' => $request->street,  
                'barangay' => $request->barangay, 
                'district' => $request->district, 
                'dateofbirth' => $request->dateofbirth, 
                'formsofassistance' => $request->formsofassistance, 
                'beneficiarylastname' => $request->studentlastname,
                'beneficiaryfirstname' => $request->studentfirstname,
                'beneficiarymiddlename' => $request->studentmiddlename,
                'school' => $request->school,
                'course' => $request->course,
                'year' => $request->year,
                'bill' => $request->bill,
                'amountofassistance' => $request->amountofassistance,
                'referredby' => $referredbyname,
                'notes' => $request->notes,
                'id1' => $id1,
                'id2' => $id2,
                'id3' => $id3,
                'beneficiaryid' => $getlastid,
                'canassist' => $canassist,
                'date' => $dt,
                ]);
                echo "<script>alert('Record has been added!')</script>";
                echo "<script>location.replace('educational')</script>";
            }

        else if($request->formsofassistance=="Transportation"){
            DB::table('assist_details')->insert(
                [
                'lastname' => $request->lastname,
                'firstname' => $request->firstname, 
                'middlename' => $request->middlename, 
                'houseno' => $request->houseno, 
                'street' => $request->street,  
                'barangay' => $request->barangay, 
                'district' => $request->district, 
                'dateofbirth' => $request->dateofbirth,  
                'formsofassistance' => $request->formsofassistance,
                'beneficiarylastname' => $request->beneficiarylastnamet, 
                'beneficiaryfirstname' => $request->beneficiaryfirstnamet, 
                'beneficiarymiddlename' => $request->beneficiarymiddlenamet,
                'bill' => $request->bill,
                'amountofassistance' => $request->amountofassistance,
                'referredby' => $referredbyname,
                'notes' => $request->notes,
                'id1' => $id1,
                'id2' => $id2,
                'id3' => $id3,
                'beneficiaryid' => $getlastid,
                'canassist' => $canassist,
                'date' => $dt,
                ]);
                
                echo "<script>alert('Record has been added!')</script>";
                echo "<script>location.replace('transportation')</script>";
            }

        else if($request->formsofassistance=="Cash For Work"){
            DB::table('assist_details')->insert(
                [
                'lastname' => $request->lastname,
                'firstname' => $request->firstname, 
                'middlename' => $request->middlename, 
                'houseno' => $request->houseno, 
                'street' => $request->street,  
                'barangay' => $request->barangay, 
                'district' => $request->district, 
                'dateofbirth' => $request->dateofbirth,  
                'formsofassistance' => $request->formsofassistance,
                'beneficiarylastname' => $request->beneficiarylastnamec, 
                'beneficiaryfirstname' => $request->beneficiaryfirstnamec, 
                'beneficiarymiddlename' => $request->beneficiarymiddlenamec,
                'bill' => $request->bill,
                'amountofassistance' => $request->amountofassistance,
                'referredby' => $referredbyname,
                'notes' => $request->notes,
                'id1' => $id1,
                'id2' => $id2,
                'id3' => $id3,
                'beneficiaryid' => $getlastid,
                'canassist' => $canassist,
                'date' => $dt,
                ]);
                
                echo "<script>alert('Record has been added!')</script>";
                echo "<script>location.replace('cash')</script>";
            }
        }
        else{
            if($request->formsofassistance =="Medical"){
                echo "<script>alert('Assistance cannot be added. The amount of assistance should not exceed the total bill.');
                location.replace('new');</script>";
            }else{
                echo "<script>alert('Assistance cannot be added. The amount of assistance should not exceed the total bill.');
                window.history.back();</script>";
            }
        }
        }
        else{
            if($request->formsofassistance =="Medical"){
                echo "<script>alert('Assistance cannot be added. Remaining budget for congressman ".strtoupper($congressman->name)." is only $money->realmoney.');
                location.replace('new');</script>";
            }else{
                echo "<script>alert('Assistance cannot be added. Remaining budget for congressman ".strtoupper($congressman->name)." is only $money->realmoney.');
                window.history.back();</script>";
            }
            
        }
        }
        else{
            if($request->formsofassistance =="Medical"){
                echo "<script>alert('Assistance cannot be added. Remaining budget for SDO is only Php$sdo->sdomoney.');
                location.replace('new');</script>";
            }else{
                echo "<script>alert('Assistance cannot be added. Remaining budget for SDO is only Php$sdo->sdomoney.');
                window.history.back();</script>";
            }
        }
        }
    }
}

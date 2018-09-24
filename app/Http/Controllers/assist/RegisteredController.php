<?php

namespace App\Http\Controllers\assist;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;;
use Carbon\Carbon;

class RegisteredController extends Controller
{

    public function viewrecords(Request $request){
        $bid = $request->bid;
        if($bid){
                $allrecords = DB::table('assist_details')
                ->where('beneficiaryid',$bid)
                ->get();
                $beneficiary = DB::table('account_reference')
                ->where('id',$bid)
                ->first();
                $sdo = DB::table('sdo')->get();
                if($beneficiary){
                    return view('assist.pages.viewrecords',[
                                'allrecords' => $allrecords,
                                'beneficiary' => $beneficiary,
                                'sdo' => $sdo
                                ]);
                }else{
                    return redirect('/checknew');
                }
        }else{
            return redirect('/checknew');
        }
    }

    public function checknew(Request $request){
        $beneficiaries = DB::table('account_reference')->get();
        $sdo = DB::table('sdo')->get();
        $money = DB::table('money')->get();
        $assistdetails = DB::table('assist_details')->orderByRaw('id DESC')->get();
        return view('assist.pages.checknew', ['beneficiaries' => $beneficiaries, 
                                                'sdo' => $sdo, 
                                                'money' => $money,
                                                'assistdetails' => $assistdetails]);
    }
    public function checknewpost(Request $request){
        if($request->savechanges=="Save Money"){
        
        $qc = DB::table('money')->where('username','QC')->first();
        $sdofirstmoney = DB::table('sdo')->where('id',$request->numbercashadvance)->first();
        
        if($sdofirstmoney->sdoactivate == '1' && $sdofirstmoney->sdoliquidation == '0'){
        if($sdofirstmoney->sdomoney<0){
        $sdolastmoney = $request->cashadvance+$sdofirstmoney->sdomoney;
        }
        elseif($sdofirstmoney->sdomoney>=0){
        $sdolastmoney = $request->cashadvance-$sdofirstmoney->sdomoney;
        }
        
        $realmoney=$qc->realmoney;

        if($realmoney<$request->cashadvance){
        $ctrmoney=0;
        }
        elseif($realmoney>=$request->cashadvance){
        $realmoney=$realmoney-$request->cashadvance;
        $ctrmoney=1;
        }

        
            if($ctrmoney==1){
                $budgetrelease = DB::table('budget_release')->where('isActive','1')->get();
                if(count($budgetrelease)>0){
                    if($budgetrelease->first()->release=='1'){
                        $firstrelease = DB::table('first_release_remaining')->where('username','QC')->get();
                        if(!($firstrelease->first()->realmoney > 0)){
                            // NO MONEY
                            $SDOok = "not ok";
                        }else{
                            // HAS MONEY
                            if(!($firstrelease->first()->realmoney >= $request->cashadvance)){
                                //CASH ADVANCE > REMAINING OF FIRST RELEASE
                                $SDOok = "release 1 not ok"; 
                            }else{
                                // CASH ADVANCE < REMAINING OF FIRST RELEASE
                                $SDOok = "ok"; 
                            }
                        }
                    }else if($budgetrelease->first()->release=='2'){
                        // SECOND RELEASE
                        $SDOok = "ok";
                    }   
                }else{
                    // NO RELEASE ACTIVE
                    $SDOok = "no release";
                } 

                if($SDOok == "ok"){
                    DB::table('sdo')
                    ->where('id', $request->numbercashadvance)
                    ->update(['sdomoney' => $sdolastmoney]);

                    DB::table('money')
                    ->where('username', 'QC')
                    ->update(['realmoney' => $realmoney]);

                    return redirect('checknew');

                }else if($SDOok=="not ok"){
                    echo "<script>alert('The Release 1 has been fully disbursed. Please activate Release 2');
                    location.replace('budget');</script>";
                }else if($SDOok=="release 1 not ok"){
                    echo "<script>alert('Unable to add money. The cash advance is cannot be higher than the remaining of ".$firstrelease->first()->realmoney." Release 1');
                    location.replace('checknew');</script>";
                }else{
                    echo "<script>alert('There is currently no active release.');
                    location.replace('budget');</script>";
                }
                            
                
            }
            else{
                echo "<script>alert('The Remaining Total Budget is ".$qc->realmoney."');
                location.replace('budget');</script>";
            }
        }
        else{
            echo "<script>alert('Unable to add money to SDO ".$request->numbercashadvance."');
            location.replace('checknew');</script>";
        }
        }
        
        if($request->savechanges=="Save Liquidation"){
            $sdoliquidation = DB::table('sdo')->where('id',$request->sdoselected2)->first();
            if($sdoliquidation->sdoliquidation == '1'){
                DB::table('control_number')
                    ->insert(['controlnumber' => $request->controlnumber,'date' => Carbon::now()]);
                DB::table('sdo')
                    ->where('id', $request->sdoselected2)
                    ->update(['sdoliquidation' => 0]);
                return redirect('checknew');
            }
            else
                echo "<script>alert('SDO ".$request->sdoselected2." is not valid to liquidate');
                location.replace('checknew');</script>";
        }
    }
    
    public function alreadyregistered(Request $request){
        $personid = $request->bid;
        $aform = $request->form;
        if($personid){
            $username = $request->session()->get('USERNAME');
            $money = DB::table('money')->get();
            $beneficiary = DB::table('account_reference')->where('id',$personid)->first();
            $barangaydistrict = DB::table('barangay_district')->get();
            $sdovalidation = DB::table('sdo')->where('sdoactivate', 1)->first();
            $account = DB::table('account_tables')->where('username', $username)->first();
            $accounttables = DB::table('account_tables')->orderByRaw('name')->get();
            $assistdetail2 = DB::table("assist_details")->where('beneficiaryid',$personid)->where('formsofassistance',$aform)->orderByRaw('id DESC')->first();
            $assistdetail = DB::table("assist_details")->where('beneficiaryid',$personid)->orderByRaw('id DESC')->first();
            $budgetrecord2 = DB::table('budget_records')->orderByRaw('id DESC')->first();
            $savemoneyqc=$budgetrecord2->budgetWithRemaining*.010;

            //first release checker
            $firstrelease = DB::table('first_release_remaining')->get();
            $budgetrelease = DB::table('budget_release')->where('isActive','1')->first();
            if($budgetrelease){
                if($budgetrelease->release=='1'){
                    if($aform == 'Medical' || $aform == 'Burial'){
                        $okRelease = 'ok';
                    }else{
                        $okRelease = 'notok';
                    }
                }else{
                    $okRelease = 'irrelevant';
                }
            }else{
                return redirect('budget');
            }
            
            $formsofassistancelimiter = DB::table('budget_release')->where('isActive', '1')->first();
            
            if($beneficiary && $aform){
                if($aform=='Burial'||$aform=='Medical'||$aform=='Educational'||$aform=='Transportation'||$aform=='Cash For Work'){
                    if($aform=='Burial' )
                        $limit2=50000;
                    else if($aform=='Medical'){
                        if($assistdetail2){
                            if($assistdetail2->bill>=1000000 ){
                                $limit2=$budgetrecord2->budgetWithRemaining*.010;
                            }
                            else{
                                $limit2=30000;
                            }    
                        }else{
                            $limit2=30000;
                        }                                                        
                    }
                    else if($aform=='Educational' )
                        $limit2=10000;
                    else if($aform=='Transportation' )
                        $limit2=20000;
                    else if($aform=='Cash For Work' )
                        $limit2=0;
                    
                    if($assistdetail2){ // has 1st record
                        if(Carbon::now()->diffInMonths($assistdetail2->date)>3){
                            $ok = 'ok';
                        }
                        else if($assistdetail2->canassist == '1')
                            $ok = 'okform';
                        else if($assistdetail2->canassist == '0')
                            $ok = 'not ok';
                    }
                    else if(!$assistdetail2){ //other assistance
                        $ok = 'ok';
                    }
                    if($sdovalidation->sdomoney>0)
                        return view('assist.pages.alreadyregistered', ['account' => $account, 
                                                                'accounttables' => $accounttables, 
                                                                'beneficiary' => $beneficiary,
                                                                'ok' => $ok,
                                                                'money' => $money,
                                                                'barangaydistrict' => $barangaydistrict,
                                                                'savemoneyqc' => $savemoneyqc,
                                                                'assistdetail' => $assistdetail,
                                                                'assistdetail2' => $assistdetail2,
                                                                'formsofassistancelimiter' => $formsofassistancelimiter,
                                                                'limit2' => $limit2,
                                                                'okRelease' => $okRelease,
                                                                'firstrelease' => $firstrelease,
                                                                'budgetrelease' => $budgetrelease->release,
                                                                'aform' => $aform]);
                    else
                        return redirect('checknew'); // NO MORE MONEY!!
                    }
                return redirect('checknew');
            }
            else{
                return redirect('checknew');
            }
        }else{
            return redirect('checknew');
        }
    }

    public function alreadyregisteredpost(Request $request){
        // DAVEMACINTOSQUIAMBAO
        //Dito yung save na... check mo yung addrecordpost sa typecontroller
        if($request->btn=="Confirm Assistance"){
            $personid = $request->beneficiaryid;
            $aform = $request->aform;
            $dt = Carbon::now();
            $datenow = $dt->toDateString();
            
           
            
            // CREATION OF UNIQUE 
            $date1 = date_create($datenow);
            $datenow2 = date_format($date1,'dMy');
            $congressman = DB::table('account_tables')->where('username',$request->referredby)->first();
            $availableday = DB::table('assist_details')->orderByRaw('id DESC')->get()->first();
            $id1= $congressman->code;
            $id2= strtoupper($datenow2);
            $id3= sprintf('%06d', $availableday->id+1);

            $referredbyname = $congressman->name;

            $beneficiary = DB::table('account_reference')->where('id',$personid)->first();
            
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
            if((int)$request->maxbill>=$request->amountofassistance){


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
                
                $realmoneycongressman=$money->realmoney-$request->amountofassistance;
    
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
                
                if($request->aform=='Burial' )
                    $limit=50000;
                else if($request->aform=='Medical' ){
                    if($request->bill>=1000000 )
                        $limit=$budgetrecord2->budgetWithRemaining*.010;
                    else
                        $limit=30000;
                }
                else if($request->aform=='Educational' )
                    $limit=10000;
                else if($request->aform=='Transportation' )
                    $limit=20000;


                $beneficiaryamountofassistance = $request->amountofassistance + $beneficiary->amountofassistance;

                DB::table('account_reference')
                        ->where('id', $personid)
                        ->update(['amountofassistance' => $beneficiaryamountofassistance]);
                
                if($request->ok=='okform'){    
                    $canassist = '0';
                }
                else{
                    if($request->aform=='Cash For Work' )
                        $canassist = '1';
                    else
                        $canassist = $request->amountofassistance<$limit ? '1' : '0';
                }

                if($aform=="Burial"){
                    /*$validator=$request->validate([
                        'houseno' => 'required',
                        'street' => 'required',
                        'barangay' => 'required',
                        'district' => 'required',
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
                        'formsofassistance' => $aform, 
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
                        'beneficiaryid' => $request->beneficiaryid,
                        'canassist' => $canassist,
                        'date' => $dt,
                        ]);
                    
                    
                        echo "<script>alert('Record has been added!')</script>";
                        echo "<script>location.replace('burial')</script>";
                }
        
                else if($aform=="Medical"){
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
                        'formsofassistance' => $aform, 
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
                        'beneficiaryid' => $request->beneficiaryid,
                        'canassist' => $canassist,
                        'date' => $dt,
                        ]);
                        
                        echo "<script>alert('Record has been added!')</script>";
                        echo "<script>location.replace('medical')</script>";
                }
        
                else if($aform=="Educational"){
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
                        'formsofassistance' => $aform, 
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
                        'beneficiaryid' => $request->beneficiaryid,
                        'canassist' => $canassist,
                        'date' => $dt,
                        ]);
                        
                        echo "<script>alert('Record has been added!')</script>";
                        echo "<script>location.replace('educational')</script>";
                }
        
                else if($aform=="Transportation"){
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
                        'formsofassistance' => $aform,
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
                        'beneficiaryid' => $request->beneficiaryid,
                        'canassist' => $canassist,
                        'date' => $dt,
                        ]);
                        echo "<script>alert('Record has been added!')</script>";
                        echo "<script>location.replace('transportation')</script>";
                }
        
                else if($aform=="Cash For Work"){
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
                        'formsofassistance' => $aform,
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
                        'beneficiaryid' => $request->beneficiaryid,
                        'canassist' => $canassist,
                        'date' => $dt,
                        ]);
                        echo "<script>alert('Record has been added!')</script>";
                        echo "<script>location.replace('cash')</script>";
                }

            }
            else{
                if($aform =="Medical"){
                    echo "<script>alert('Assistance cannot be added. The amount of assistance should not exceed the total bill.');
                    location.replace('alreadyregistered?bid=".$request->beneficiaryid."&form=".$aform."');</script>";
                }else{
                    echo "<script>alert('Assistance cannot be added. The amount of assistance should not exceed the total bill.');
                    window.history.back();</script>";
                }
            }
            }
            else{
                if($aform =="Medical"){
                    echo "<script>alert('You cannot add because the amount money of congressmen $congressman->name  is: $money->realmoney');
                    location.replace('alreadyregistered?bid=".$request->beneficiaryid."&form=".$aform."');</script>";
                }else{
                    echo "<script>alert('You cannot add because the amount money of congressmen $congressman->name  is: $money->realmoney');
                    window.history.back();</script>";
                }
            }
            }
            else{
                if($aform =="Medical"){
                    echo "<script>alert('You cannot add because the remaining money of SDO is only Php $sdo->sdomoney');
                    location.replace('alreadyregistered?bid=".$request->beneficiaryid."&form=".$aform."');</script>";
                }else{
                    echo "<script>alert('You cannot add because the remaining money of SDO is only Php $sdo->sdomoney');
                    window.history.back();</script>";
                }
                
            }
        }
    }
}
<?php

namespace App\Http\Controllers\assist;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;;
use PDF;
use NumConvert;

class PrintController extends Controller
{
    //
    public function printrec(Request $request){
        $user = DB::table('account_tables')->where('username',$request->session()->get("USERNAME"))->first();
        $record= DB::table('assist_details')->where('id',$request->id)->first();
        
        if($record){
                if($user->role == 'admin'){
                    if($record->district==$user->district){
                        $cont='1';
                    }else{
                        $cont='0';
                    }
                }else if($user->role == 'superadmin'){
                    $cont='1';
                }
                
                if($cont=="1"){
                    try{
                        $amt = (int)$record->amountofassistance;
                        $amountofassistance = NumConvert::word($amt);
                    }catch(Exception $e){
                        $amountofassistance = $record->amountofassistance;
                    }
                    
                    $record2= DB::table("account_reference")->where('id',$record->beneficiaryid)->first();
    
                    $d = date('d', strtotime($record2->lastdate)).date('S', strtotime($record2->lastdate));;
                    $m = date('F', strtotime($record2->lastdate));
                    $y = date('Y', strtotime($record2->lastdate));
                    $address = $record->houseno .", ". $record->street .", ". $record->barangay;
    
                    
                    
                    $pdf = PDF::loadView('assist.print.singlerecord',['record' => $record, 
                                                            'amountofassistance' => $amountofassistance, 
                                                            'd' => $d,
                                                            'm' => $m,
                                                            'y' => $y,
                                                            'address' => $address]);
                    return $pdf->stream();
                }else{
                    return redirect('/');
                }
                
        }else{
            return redirect('/');
        }
    }


    public function printall(Request $request){
        $record= DB::table('assist_details')->where('id',$request->id)->first();
        $pdf = PDF::loadView('assist.print.allbeneficiaryrecord',['record' => $record]);
        return $pdf->stream();
    }
    
    public function printgis(Request $request){
        $pdf = PDF::loadView('assist.print.printGIS')->setOrientation('landscape');
        return $pdf->stream();
    }

    public function printfindings(Request $request){
        $pdf = PDF::loadView('assist.print.printfindings');
        return $pdf->stream();
    }
}

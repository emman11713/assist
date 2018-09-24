<?php 
namespace App\Http\Controllers\assist; 
use App\Http\Controllers\Controller; 
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\DB; 

class LogoutController extends Controller{ 
   // 
   public function index(Request $request){
        $request->session()->pull('USERNAME');
        return redirect('/');
   }
} 

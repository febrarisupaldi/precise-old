<?php

namespace App\Http\Controllers\Api\OEM;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;

class MaterialBalanceController extends Controller
{
    private $materialBalance;
    public function stock_card(Request $request){
        $start    = $request->get('start');
        $end      = $request->get('end');
        $material = $request->get('material');

        $validator = Validator::make($request->all(), [
            'start' => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'   => 'required|date_format:Y-m-d|after_or_equal:start',
            'material' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->materialBalance = DB::connection('mysql2')->select(
                'call precise.oem_get_material_stock_card(:start,:end,:material)', 
                [
                    'start' => $start,
                    'end'   => $end,
                    'material'=> $material
                ]
            );
            return response()->json(['data'=>$this->materialBalance]);
        }
    }

    public function stock_mutation(Request $request){
        $start    = $request->get('start');
        $end      = $request->get('end');
        $customer = $request->get('customer');

        $validator = Validator::make($request->all(), [
            'start' => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'   => 'required|date_format:Y-m-d|after_or_equal:start',
            'customer' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->materialBalance = DB::select(
                'call precise.oem_get_material_stock_mutation(:start,:end,:customer)', 
                [
                    'start' => $start,
                    'end'   => $end,
                    'customer'=> $customer
                ]
            );
            return response()->json(['data'=>$this->materialBalance]);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Master\HelperController;

class TechnicalStandardController extends Controller
{
    private $technicalStd;
    public function index(){
        
    }

    public function show($id){
        $this->technicalStd = DB::table('precise.techincal_std_hd as hd')
            ->where('hd.technical_std_hd_id', $id)
            ->select(
                'technical_std_hd_id',
                'hd.product_item_id',
                'pi.item_code',
                'pi.`item_name',
                'pi.item_alias',
                'default_tonnage', 
                'int_weight_def',
                'ext_weight_def',
                'int_cycle_time_def',
                'ext_cycle_time_def', 
                'int_runner_weight_def',
                'ext_runner_weight_def',
                'int_material_weight_def',
                'ext_material_weight_def', 
                'hd.created_on',
                'hd.created_by',
                'hd.updated_on',
                'hd.updated_by'
            )
            ->leftJoin('precise.product_item as pi','hd.product_item_id','=','pi.item_id')
            ->get();

        return response()->json($this->technicalStd);
    }

    public function showByProductKind(Request $request){
        $kind = $request->get('id');
        $validator = Validator::make(
            $request->all(),
            [
                'id'    => 'required'
            ]
        );
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $product_kind = explode("-", $kind);
            $this->technicalStd =  DB::table('precise.techincal_std_hd as hd')
                ->whereIn('pi.kind_code', $product_kind)
                ->select(
                    'technical_std_hd_id',
                    'hd.product_item_id',
                    'pi.item_code',
                    'pi.item_name',
                    'pi.item_alias',
                    'default_tonnage', 
                    'int_weight_def',
                    'ext_weight_def',
                    'int_cycle_time_def',
                    'ext_cycle_time_def', 
                    'int_runner_weight_def',
                    'ext_runner_weight_def',
                    'int_material_weight_def',
                    'ext_material_weight_def', 
                    'hd.created_on',
                    'hd.created_by',
                    'hd.updated_on',
                    'hd.updated_by'
                )
                ->leftJoin('precise.product_item as pi','hd.product_item_id','=','pi.item_id')
                ->get();

            return response()->json(["data"=>$this->technicalStd]);
        }
    }

    public function joined(Request $request){
        $kind = $request->get('id');
        $validator = Validator::make(
            $request->all(),
            [
                'id'    => 'required'
            ]
        );
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $product_kind = explode("-", $kind);
            $this->technicalStd = DB::table("precise.technical_std_hd as hd")
                ->whereIn('pi.kind_code', $product_kind)
                ->select(
                    'hd.technical_std_hd_id',
                    'hd.product_item_id',
                    'pi.item_code',
                    'pi.item_name',
                    'pi.item_alias',
                    'default_tonnage', 
                    'technical_std_code',
                    'dt.process_type_id',
                    'ppt.process_code',
                    'ppt.process_description',
                    'technical_std_dt_description', 
                    'dt.mold_hd_id',
                    'mold_numberj',
                    'mold_name', 
                    'dt.machine_id',
                    'machine_code',
                    'machine_name',
                    'int_weight_def', 
                    'dt.int_weight_std',
                    'dt.int_weight_min',
                    'dt.int_weight_max',
                    'ext_weight_def',
                    'dt.ext_weight_std',
                    'dt.ext_weight_min',
                    'dt.ext_weight_max',
                    'int_cycle_time_def', 
                    'dt.int_cycle_time_std',
                    'dt.int_cycle_time_min',
                    'dt.int_cycle_time_max',
                    'ext_cycle_time_def', 
                    'dt.ext_cycle_time_std',
                    'dt.ext_cycle_time_min',
                    'dt.ext_cycle_time_max',
                    'int_runner_weight_def', 
                    'dt.int_runner_weight_std',
                    'dt.int_runner_weight_min',
                    'dt.`int_runner_weight_max',
                    'ext_runner_weight_def',
                    'dt.ext_runner_weight_std',
                    'dt.ext_runner_weight_min',
                    'dt.ext_runner_weight_max',
                    'int_material_weight_def', 		
                    'dt.int_material_weight_std',
                    'dt.int_material_weight_min',
                    'dt.int_material_weight_max',
                    'ext_material_weight_def', 
                    'dt.ext_material_weight_std',
                    'dt.ext_material_weight_min',
                    'dt.ext_material_weight_max',
                    'hd.created_on',
                    'hd.created_by',
                    'hd.updated_on',
                    'hd.updated_by'
                )
                ->join('precise.technical_std_dt as dt','hd.technical_std_hd_id','=','dt.technical_std_hd_id')
                ->leftJoin('precise.product_item as pi','hd.product_item_id','=','pi.item_id')
                ->leftJoin('precise.production_process_type as ppt','dt.process_type_id','=','ppt.process_type_id')
                ->leftJoin('precise.mold_hd as mh','dt.mold_hd_id','=','mh.mold_mh_id')
                ->leftJoin('precise.machine as m','dt.machine_id','=','m.machine_id')
                ->get();
            
            return response()->json(["data"=>$this->technicalStd]);
        }
    }

    public function check(Request $request){
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'value'=> 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            if($type == "code"){
                $this->technicalStd = DB::table('precise.technical_std_dt')
                    ->where('technical_std_code', $value)
                    ->count();
            }else if($type == "product_item"){
                $this->technicalStd = DB::table('precise.technical_std_hd')
                    ->where('product_item_id', $value)
                    ->count();
            }
            return response()->json(['status' => 'ok', 'message' => $this->technicalStd]);
        }
    }

    public function create(Request $request){
        $data = $request->json()->all();
        $validator = Validator::make(json_decode(json_encode($data),true),[
            
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            
        }
    }
}

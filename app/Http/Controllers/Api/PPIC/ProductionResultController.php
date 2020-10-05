<?php

namespace App\Http\Controllers\Api\PPIC;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;
use App\Http\Controllers\Api\Master\HelperController;

class ProductionResultController extends Controller
{
    private $productionResult;
    
    public function index(Request $request){
        $start = $request->get('start');
        $end = $request->get('end');
        $wc = $request->get('workcenter');
        $validator = Validator::make($request->all(), [
            'start'     => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'       => 'required|date_format:Y-m-d|after_or_equal:start',
            'workcenter'=> 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $workcenter = explode("-", $wc);
            $this->productionResult = DB::table('precise.production_result_hd as pr')
                ->whereIn('wo.workcenter_id',$workcenter)
                ->whereBetween('pr.result_date', [$start, $end])
                ->select(
                    'pr.result_hd_id',
                    'pr.result_date',
                    'pr.result_shift',
                    'w.workcenter_code',
                    'w.workcenter_name',  
                    'pr.work_order_hd_id',
                    'pr.PrdNumber', 
                    'pr.ResultSeq',
                    'pr.created_on',
                    'pr.created_by',
                    'pr.updated_on',
                    'pr.updated_by'
                )
                ->leftJoin('precise.work_order as wo','pr.work_order_hd_id','=','wo.work_order_hd_id')
                ->leftJoin('precise.workcenter as w','wo.workcenter_id','=','w.workcenter_id')
                ->get();

            return response()->json(["data" => $this->productionResult]);
        }
    }

    public function show($id){
        $master = DB::table("precise.production_result_hd as pr")
            ->where("pr.result_hd_id", $id)
            ->select(
                'pr.result_hd_id',
                'pr.result_date',
                'pr.result_shift',
                'pr.work_order_hd_id',
                'pr.PrdNumber', 
                'wo.workcenter_id',
                'w.workcenter_code',
                'w.workcenter_name',
                'pr.ResultSeq',
                'wh.warehouse_id',
                'wh.warehouse_code',
                'wh.warehouse_name',
                'pr.created_on',
                'pr.created_by',
                'pr.updated_on',
                'pr.updated_by'
             )
            ->leftJoin('precise.production_result_dt as prd','pr.result_hd_id','=','prd.result_hd_id')
            ->leftJoin('precise.warehouse as wh','prd.result_warehouse','=','wh.warehouse_id')
            ->leftJoin('precise.work_order as wo', 'pr.work_order_hd_id','=','wo.work_order_hd_id')
            ->leftJoin('precise.workcenter as w','wo.workcenter_id','=','w.workcenter_id')
            ->first();

        $detail = DB::table('precise.production_result_dt as dt')
        ->where('dt.result_hd_id', $master->result_hd_id)
        ->select(
            'dt.result_dt_id',
            'dt.result_hd_id', 
            'dt.PrdNumber',
            'dt.ResultSeq',
            'dt.ProductCode',
            'p.product_code',
            'p.product_name',
            'dt.ProductCode AS old_product_code', 
            'dt.product_id', 
            'dt.result_qty',
            'dt.result_warehouse',
            'dt.InvtNmbr',
            'dt.InvtType',
            'dt.trans_hd_id',
            'p.uom_code',
            'dt.created_on',
            'dt.created_by',
            'dt.updated_on', 
            'dt.updated_by'
        )
        ->leftJoin('precise.product as p','dt.product_id','=','p.product_id')
        ->get();

        $this->productionResult = 
        array(
            "result_hd_id"        => $master->result_hd_id,
            "result_date"         => $master->result_date,
            "result_shift"        => $master->result_shift,
            "work_order_hd_id"    => $master->work_order_hd_id,
            "warehouse_id"        => $master->warehouse_id,
            "warehouse_code"      => $master->warehouse_code,
            "warehouse_name"      => $master->warehouse_name,
            "PrdNumber"           => $master->PrdNumber,
            "workcenter_id"       => $master->workcenter_id,
            "workcenter_code"     => $master->workcenter_code,
            "workcenter_name"     => $master->workcenter_name,
            "ResultSeq"           => $master->ResultSeq,
            "created_on"          => $master->created_on,
            "created_by"          => $master->created_by,
            "updated_on"          => $master->updated_on,
            "updated_by"          => $master->updated_by,
            "detail"              => $detail
        );
        
        return response()->json($this->productionResult);
    }

    public function create(Request $request){
        $data = $request->json()->all();
        $validator = Validator::make(json_decode(json_encode($data),true),[
            'result_date'           =>'required',
            'result_shift'          =>'required',
            'work_order_hd_id'      =>'required|exists:work_order,work_order_hd_id',
            'PrdNumber'             =>'required',
            'created_by'            =>'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try
            {
                $transNum = DB::select('SELECT precise.get_transaction_number(5, :rDate) AS transNumber', ['rDate' => $data['result_date']]);

                $transType = DB::table('precise.warehouse_trans_type')
                ->where('trans_type_name', 'Production Result')
                ->select('trans_type_id', 'trans_type_code')
                ->first();
                $object = (object)$transNum;
                foreach ($transNum as $key => $value)
                {
                    $object->$key = $value;
                }
                              
                $object1 = $object->$key;
               
                DB::raw(DB::select('call precise.`system_increment_transaction_counter`(5, :rDate)', ['rDate' => $data['result_date']]));

                $transhd = DB::table('precise.warehouse_trans_hd')
                ->insertGetId([
                    'trans_number'       => $object1->transNumber,
                    'trans_type'         => $transType->trans_type_id,
                    'trans_date'         => $data['result_date'],
                    'trans_from'         => $data['warehouse_id'],
                    'work_order_id'      => $data['work_order_hd_id'],
                    'work_order_number'  => $data['PrdNumber'],
                    'created_by'         => $data['created_by']
                ]);

                foreach($data['detail'] as $transdt){
                    $whDt[] = [
                        'trans_hd_id'           => $transhd,
                        'trans_number'          => $object1->transNumber,
                        'trans_type'            => $transType->trans_type_id,
                        'trans_seq'             => 1,
                        'product_id'            => $transdt['product_id'],
                        'trans_in_qty'          => $transdt['result_qty'],
                        'trans_out_qty'         => 0.0000,
                        'trans_uom'             => $transdt['uom_code'],
                        'trans_in_qty_t'        => $transdt['result_qty'],
                        'trans_out_qty_t'       => 0.0000,
                        'trans_uom_t'           => $transdt['uom_code'],
                        'trans_price'           => 0.0000,
                        'trans_qty_price'       => 0.0000,
                        'trans_ppn_percent'     => 0.0000,
                        'trans_ppn_amount'      => 0.0000,
                        'created_by'            => $data['created_by']
                    ];
                }

                DB::table('precise.warehouse_trans_dt')
                ->insert($whDt);
                $resSeq = 0;
                $resultSeq = DB::table('precise.production_result_hd')
                ->where('work_order_hd_id', $data['work_order_hd_id'])
                ->select(
                    'ResultSeq'
                )
                ->orderBy('ResultSeq', 'DESC')
                ->first();
                if($resultSeq != null)
                {
                    $resSeq = $resultSeq->ResultSeq + 1;
                }
                else
                {
                    $resSeq = 1;
                }
               

                $id = DB::table('precise.production_result_hd')
                ->insertGetId([
                    'result_date'       => $data['result_date'],
                    'result_shift'      => $data['result_shift'],
                    'work_order_hd_id'  => $data['work_order_hd_id'],
                    'PrdNumber'         => $data['PrdNumber'],
                    'ResultSeq'         => $resSeq,
                    'created_by'        => $data['created_by']
                ]);

                foreach($data['detail'] as $d){
                    $dt[] = [
                        'result_hd_id'          => $id,
                        'PrdNumber'             => $d['PrdNumber'],
                        'ResultSeq'             => $resSeq,
                        'ProductCode'           => $d['product_code'],
                        'product_id'            => $d['product_id'],
                        'result_qty'            => $d['result_qty'],
                        'result_warehouse'      => $d['result_warehouse'],
                        'InvtNmbr'              => $object1->transNumber,
                        'InvtType'              => $transType->trans_type_code,
                        'trans_hd_id'           => $transhd,
                        'created_by'            => $data['created_by']
                    ];
                }

                DB::table('precise.production_result_dt')
                ->insert($dt);

                $trans = DB::table('precise.production_result_hd')
                        ->where('result_hd_id', $id)
                        ->select('PrdNumber')
                        ->first();

                DB::commit();
                return response()->json(['status' => 'ok', 'message' => $id], 200);
            }
            catch(\Exception $e){
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }
   
    public function update(Request $request){
        $data = $request->json()->all();
        $validator = Validator::make(json_decode(json_encode($data),true),[
            'result_hd_id'          =>'required',
            'result_date'           =>'required',
            'result_shift'          =>'required',
            'work_order_hd_id'      =>'required|exists:work_order,work_order_hd_id',
            'PrdNumber'             =>'required',
            'ResultSeq'             =>'required',
            'updated_by'            =>'required',
            'reason'                =>'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try
            {
                QueryController::reason($data);
                               
                foreach($data['detail'] as $transdt){
                    DB::table('precise.warehouse_trans_hd')
                    ->where('trans_hd_id', $transdt['trans_hd_id'])
                    ->update([
                        'trans_date'         => $data['result_date'],
                        'trans_from'         => $data['warehouse_id'],
                        'work_order_id'      => $data['work_order_hd_id'],
                        'work_order_number'  => $data['PrdNumber'],
                        'updated_by'         => $data['updated_by']
                    ]);
    
                    DB::table('precise.warehouse_trans_dt')
                        ->where('trans_hd_id', $transdt['trans_hd_id'])
                        ->update([
                            'product_id'            => $transdt['product_id'],
                            'trans_in_qty'          => $transdt['result_qty'],
                            'trans_uom'             => $transdt['uom_code'],
                            'trans_in_qty_t'        => $transdt['result_qty'],
                            'trans_uom_t'           => $transdt['uom_code'],
                            'updated_by'            => $data['updated_by']                            
                    ]);
                }
                
                $resSeq = 0;
                if($data['work_order_hd_id'] != $data['old_work_order_hd_id']){
                    $resultSeq = DB::table('precise.production_result_hd')
                    ->where('work_order_hd_id', $data['work_order_hd_id'])
                    ->select(
                        'ResultSeq'
                    )
                    ->orderBy('ResultSeq', 'DESC')
                    ->first();
                
                    if($resultSeq != null)
                    {
                        $resSeq = $resultSeq->ResultSeq + 1;                                
                    }
                    else
                    {
                        $resSeq = 1;
                    }
                }
                else{
                    $resSeq = $data['ResultSeq'];
                }
                


                DB::table('precise.production_result_hd')
                ->where('result_hd_id',$data['result_hd_id'])
                ->update([
                    'result_date'         => $data['result_date'],
                    'result_shift'        => $data['result_shift'],
                    'work_order_hd_id'    => $data['work_order_hd_id'],
                    'PrdNumber'           => $data['PrdNumber'],
                    'ResultSeq'           => $resSeq,
                    'updated_by'          => $data['updated_by']
                ]);

                if($data['inserted'] != null)
                {
                    foreach($data['inserted'] as $d)
                    {
                        $dt[] = [
                            'result_hd_id'          => $d['result_hd_id'],
                            'PrdNumber'             => $d['PrdNumber'],
                            'ResultSeq'             => $resSeq,
                            'ProductCode'           => $d['product_code'],
                            'product_id'            => $d['product_id'],
                            'result_qty'            => $d['result_qty'],
                            'result_warehouse'      => $d['result_warehouse'],
                            'InvtNmbr'              => $d['InvtNmbr'],
                            'InvtType'              => $d['InvtType'],
                            'trans_hd_id'           => $d['trans_hd_id'],
                            'created_by'            => $data['created_by']
                        ];
                    }
                    DB::table('precise.production_result_dt')
                    ->insert($dt);
                }

                if($data['updated'] != null)
                {
                    foreach($data['updated'] as $d)
                    {
                        DB::table('precise.production_result_dt')
                        ->where('result_dt_id',$d['result_dt_id'])
                        ->update([
                            'result_hd_id'          => $d['result_hd_id'],
                            'PrdNumber'             => $d['PrdNumber'],
                            'ResultSeq'             => $resSeq,
                            'ProductCode'           => $d['product_code'],
                            'product_id'            => $d['product_id'],
                            'result_qty'            => $d['result_qty'],
                            'result_warehouse'      => $d['result_warehouse'],
                            'InvtNmbr'              => $d['InvtNmbr'],
                            'InvtType'              => $d['InvtType'],
                            'trans_hd_id'           => $d['trans_hd_id'],
                            'updated_by'            => $data['updated_by']
                        ]);
                    }
                }

                if($data['deleted'] != null)
                {
                    $delete = array();
                    foreach($data['deleted'] as $del){
                        $delete[] = $del['result_dt_id'];
                    }

                    DB::table('precise.production_result_dt')
                    ->whereIn('result_dt_id', $delete)
                    ->delete();
                }

                DB::commit();
                return response()->json(['status' => 'ok', 'message' => 'Production Result have been updated'], 200);
                
            }
            catch(\Exception $e){
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }
    
    public function destroy($id)
    {
        DB::beginTransaction();
        try{
            $helper = new HelperController();
            $helper->reason("delete");

            $transid = DB::table('precise.production_result_dt')
            ->where('result_hd_id', $id)
            ->select(
                'trans_hd_id'
            )
            ->first();
            
            DB::table('precise.warehouse_trans_dt')
            ->where('trans_hd_id', $transid->trans_hd_id)
            ->delete();

            DB::table('precise.warehouse_trans_hd')
            ->where('trans_hd_id', $transid->trans_hd_id)
            ->delete();

            DB::table('precise.production_result_dt')
            ->where('result_hd_id', $id)
            ->delete();

            DB::table('precise.production_result_hd')
            ->where('result_hd_id', $id)
            ->delete();

            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'Production Result have been deleted'], 200);
        }catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function check(Request $request){
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            if ($type == "number") {
                $this->productionResult = DB::table('precise.production_result_hd')->where([
                    'PrdNumber' => $value
                ])->count();
            }
            return response()->json(['status' => 'ok', 'message' => $this->productionResult]);
        }
    }
}

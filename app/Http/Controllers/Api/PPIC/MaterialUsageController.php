<?php

namespace App\Http\Controllers\Api\PPIC;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;
use App\Http\Controllers\Api\Master\HelperController;

class MaterialUsageController extends Controller
{
    private $materialUsage;
    
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
            $this->materialUsage = DB::table('precise.material_usage as mu')
                ->whereIn('wo.workcenter_id',$workcenter)
                ->whereBetween('mu.production_date', [$start, $end])
                ->select(
                    'mu.usage_id',
                    'mu.production_date',
                    'mu.bom_factor',
                    'w.workcenter_code',
                    'w.workcenter_name',  
                    'mu.work_order_hd_id',
                    'mu.material_id',
                    'p.product_code',
                    'p.product_name',
                    'mu.material_qty',
                    'mu.material_uom',
                    'mu.warehouse_id',
                    'wh.warehouse_code',
                    'wh.warehouse_name',
                    'mu.bom_hd_id',
                    'b.bom_code',
                    'b.bom_name',
                    'mu.PrdNumber', 
                    'mu.usage_description',
                    'mu.InvtNmbr',
                    'mu.InvtType',
                    'mu.trans_hd_id',
                    'mu.created_on',
                    'mu.created_by',
                    'mu.updated_on',
                    'mu.updated_by'
                )
                ->leftJoin('precise.work_order as wo','mu.work_order_hd_id','=','wo.work_order_hd_id')
                ->leftJoin('precise.workcenter as w','wo.workcenter_id','=','w.workcenter_id')
                ->leftJoin('precise.product as p','mu.material_id','=','p.product_id')
                ->leftJoin('precise.warehouse as wh','mu.warehouse_id','=','wh.warehouse_id')
                ->leftJoin('precise.bom_hd as b','mu.bom_hd_id','=','b.bom_hd_id')
                ->get();

            return response()->json(["data" => $this->materialUsage]);
        }
    }

    public function show($id){
        $master = DB::table("precise.material_usage as mu")
            ->where("mu.usage_id", $id)
            ->select(
                'mu.usage_id',
                'mu.production_date',
                'mu.work_order_hd_id',
                'mu.PrdNumber', 
                'mu.PrdSeq',
                'wo.workcenter_id',
                'w.workcenter_code',
                'w.workcenter_name',                
                'mu.warehouse_id',
                'wh.warehouse_code',
                'wh.warehouse_name',
                'mu.bom_factor',
                'mu.bom_hd_id',
                'b.bom_code',
                'b.bom_name',
                'mu.InvtNmbr',
                'mu.InvtType',
                'mu.trans_hd_id',
                'mu.usage_description',
                'mu.created_on',
                'mu.created_by',
                'mu.updated_on',
                'mu.updated_by'
             )
            ->leftJoin('precise.work_order as wo','mu.work_order_hd_id','=','wo.work_order_hd_id')
            ->leftJoin('precise.workcenter as w','wo.workcenter_id','=','w.workcenter_id')
            ->leftJoin('precise.warehouse as wh','mu.warehouse_id','=','wh.warehouse_id')
            ->leftJoin('precise.bom_hd as b','mu.bom_hd_id','=','b.bom_hd_id')
            ->first();

        $detail = DB::table('precise.material_usage as dt')
        ->where('dt.trans_hd_id', $master->trans_hd_id)
        ->select(
            'dt.usage_id', 
            'dt.material_id',
            'p.product_code',
            'p.product_name',
            'b.material_qty as bom_qty',
            'dt.material_qty',
            'dt.material_uom',
            'dt.material_std_qty',
            'dt.material_std_uom'
        )
        ->leftJoin('precise.product as p','dt.material_id','=','p.product_id')
        ->leftJoin('bom_dt as b', function($join)
        {
            $join->on('dt.bom_hd_id','=','b.bom_hd_id')
            ->on('dt.material_id','=','b.material_id');
        })
        ->get();

        $this->materialUsage = 
        array(
            "usage_id"            => $master->usage_id,
            "production_date"     => $master->production_date,
            "work_order_hd_id"    => $master->work_order_hd_id,
            "PrdNumber"           => $master->PrdNumber,
            "PrdSeq"              => $master->PrdSeq,
            "workcenter_id"       => $master->workcenter_id,
            "workcenter_code"     => $master->workcenter_code,
            "workcenter_name"     => $master->workcenter_name,
            "warehouse_id"        => $master->warehouse_id,
            "warehouse_code"      => $master->warehouse_code,
            "warehouse_name"      => $master->warehouse_name,
            "bom_factor"          => $master->bom_factor,
            "bom_hd_id"           => $master->bom_hd_id,
            "bom_code"            => $master->bom_code,
            "bom_name"            => $master->bom_name,
            "InvtNmbr"            => $master->InvtNmbr,
            "InvtType"            => $master->InvtType,
            "trans_hd_id"         => $master->trans_hd_id,
            "usage_description"   => $master->usage_description,
            "created_on"          => $master->created_on,
            "created_by"          => $master->created_by,
            "updated_on"          => $master->updated_on,
            "updated_by"          => $master->updated_by,
            "detail"              => $detail
        );
        
        return response()->json($this->materialUsage);
    }

    public function create(Request $request){
        $data = $request->json()->all();
        $validator = Validator::make(json_decode(json_encode($data),true),[
            'production_date'       =>'required',
            'bom_factor'            =>'required',
            'bom_hd_id'             =>'required|exists:bom_hd,bom_hd_id',
            'work_order_hd_id'      =>'required|exists:work_order,work_order_hd_id',
            'PrdNumber'             =>'required',
            'warehouse_id'          =>'required|exists:warehouse,warehouse_id',
            'created_by'            =>'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try
            {
                $transNum = DB::select('SELECT precise.get_transaction_number(6, :rDate) AS transNumber', ['rDate' => $data['production_date']]);

                $transType = DB::table('precise.warehouse_trans_type')
                ->where('trans_type_name', 'Production Usage')
                ->select('trans_type_id','trans_type_code')
                ->first();
                $object = (object)$transNum;
                foreach ($transNum as $key => $value)
                {
                    $object->$key = $value;
                }
                              
                $object1 = $object->$key;
               
                DB::raw(DB::select('call precise.`system_increment_transaction_counter`(6, :rDate)', ['rDate' => $data['production_date']]));

                $transhd = DB::table('precise.warehouse_trans_hd')
                ->insertGetId([
                    'trans_number'       => $object1->transNumber,
                    'trans_type'         => $transType->trans_type_id,
                    'trans_date'         => $data['production_date'],
                    'trans_from'         => $data['warehouse_id'],
                    'work_order_id'      => $data['work_order_hd_id'],
                    'work_order_number'  => $data['PrdNumber'],
                    'created_by'         => $data['created_by']
                ]);
                $trans_seq = 1;
                foreach($data['detail'] as $transdt){
                    $whDt[] = [
                        'trans_hd_id'           => $transhd,
                        'trans_number'          => $object1->transNumber,
                        'trans_type'            => $transType->trans_type_id,
                        'trans_seq'             => $trans_seq,
                        'product_id'            => $transdt['material_id'],
                        'trans_in_qty'          => 0.0000,
                        'trans_out_qty'         => $transdt['material_qty'],
                        'trans_uom'             => $transdt['material_uom'],
                        'trans_in_qty_t'        => 0.0000,
                        'trans_out_qty_t'       => $transdt['material_qty'],
                        'trans_uom_t'           => $transdt['material_uom'],
                        'trans_price'           => 0.0000,
                        'trans_qty_price'       => 0.0000,
                        'trans_ppn_percent'     => 0.0000,
                        'trans_ppn_amount'      => 0.0000,
                        'created_by'            => $data['created_by']
                    ];
                    $trans_seq = $trans_seq + 1;
                }

                DB::table('precise.warehouse_trans_dt')
                ->insert($whDt);
                $PrdSeq = 0;
                $workOrderSeq = DB::table('precise.material_usage')
                ->where('work_order_hd_id', $data['work_order_hd_id'])
                ->select(
                    'PrdSeq'
                )
                ->orderBy('PrdSeq', 'DESC')
                ->first();
                if($workOrderSeq != null)
                {
                    $PrdSeq = $workOrderSeq->PrdSeq + 1;
                }
                else
                {
                    $PrdSeq = 1;
                }                        

                foreach($data['detail'] as $d){
                    $dt[] = [
                        'production_date'       => $data['production_date'],
                        'work_order_hd_id'      => $data['work_order_hd_id'],
                        'PrdNumber'             => $data['PrdNumber'],
                        'PrdSeq'                => $PrdSeq,
                        'usage_description'     => $data['usage_description'],
                        'bom_hd_id'             => $data['bom_hd_id'],
                        'bom_factor'            => $data['bom_factor'],                        
                        'material_id'           => $d['material_id'],
                        'material_qty'          => $d['material_qty'],
                        'material_uom'          => $d['material_uom'],
                        'material_std_qty'      => $d['material_qty'],
                        'material_std_uom'      => $d['material_uom'],
                        'warehouse_id'          => $data['warehouse_id'],
                        'InvtNmbr'              => $object1->transNumber,
                        'InvtType'              => $transType->trans_type_code,
                        'trans_hd_id'           => $transhd,
                        'created_by'            => $data['created_by']
                    ];
                    $PrdSeq = $PrdSeq + 1;
                }

                DB::table('precise.material_usage')
                ->insert($dt);

                $trans = DB::table('precise.material_usage')
                        ->where('trans_hd_id', $transhd)
                        ->select('usage_id')
                        ->first();

                DB::commit();
                return response()->json(['status' => 'ok', 'message' => $trans->usage_id], 200);
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
            'usage_id'              =>'required',
            'production_date'       =>'required',
            'bom_hd_id'             =>'required|exists:bom_hd,bom_hd_id',
            'bom_factor'            =>'required',
            'work_order_hd_id'      =>'required|exists:work_order,work_order_hd_id',
            'warehouse_id'          =>'required|exists:warehouse,warehouse_id',
            'PrdNumber'             =>'required',
            'PrdSeq'                =>'required',
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
                
                DB::table('precise.warehouse_trans_hd')
                ->where('trans_hd_id', $data['trans_hd_id'])
                ->update([
                    'trans_date'         => $data['production_date'],
                    'trans_from'         => $data['warehouse_id'],
                    'work_order_id'      => $data['work_order_hd_id'],
                    'work_order_number'  => $data['PrdNumber'],
                    'updated_by'         => $data['updated_by']
                ]);
                
                DB::table('precise.warehouse_trans_dt')
                ->where('trans_hd_id', $data['trans_hd_id'])
                ->delete();
                
                DB::table('precise.material_usage')
                ->where('trans_hd_id', $data['trans_hd_id'])
                ->delete();
    
                $transType = DB::table('precise.warehouse_trans_type')
                ->where('trans_type_name', 'Production Usage')
                ->select('trans_type_id','trans_type_code')
                ->first();
                
                $tran_Seq = 0;
                $tranSeq = DB::table('precise.warehouse_trans_dt')
                ->where('trans_hd_id', $data['trans_hd_id'])
                ->select(
                    'trans_seq'
                )
                ->orderBy('trans_seq', 'DESC')
                ->first();

                if($tranSeq != null)
                {
                    $tran_Seq = $tranSeq->trans_seq + 1;                                 
                }
                else
                {
                    $tran_Seq = 1;
                }
               
                $PrdSeq = 0;
                $workOrderSeq = DB::table('precise.material_usage')
                ->where('work_order_hd_id', $data['work_order_hd_id'])
                ->select(
                    'PrdSeq'
                )
                ->orderBy('PrdSeq', 'DESC')
                ->first();
            
                if($workOrderSeq != null)
                {
                    $PrdSeq = $workOrderSeq->PrdSeq + 1;                                
                }
                else
                {
                    $PrdSeq = 1;
                }
                
                foreach($data['detail'] as $transdt){
                    $whDt[] = [
                        'trans_hd_id'           => $data['trans_hd_id'],
                        'trans_number'          => $data['InvtNmbr'],
                        'trans_type'            => $transType->trans_type_id,
                        'trans_seq'             => $tran_Seq,
                        'product_id'            => $transdt['material_id'],
                        'trans_in_qty'          => 0.0000,
                        'trans_out_qty'         => $transdt['material_qty'],
                        'trans_uom'             => $transdt['material_uom'],
                        'trans_in_qty_t'        => 0.0000,
                        'trans_out_qty_t'       => $transdt['material_qty'],
                        'trans_uom_t'           => $transdt['material_uom'],
                        'trans_price'           => 0.0000,
                        'trans_qty_price'       => 0.0000,
                        'trans_ppn_percent'     => 0.0000,
                        'trans_ppn_amount'      => 0.0000,
                        'created_by'            => $data['updated_by'],
                        'updated_by'            => $data['updated_by']
                    ];
                    $tran_Seq = $tran_Seq + 1;
                }

                DB::table('precise.warehouse_trans_dt')
                ->insert($whDt);

                foreach($data['detail'] as $d)
                {
                    $dt[] = [
                    'production_date'       => $data['production_date'],
                    'work_order_hd_id'      => $data['work_order_hd_id'],
                    'PrdNumber'             => $data['PrdNumber'],
                    'PrdSeq'                => $PrdSeq,
                    'usage_description'     => $data['usage_description'],
                    'bom_hd_id'             => $data['bom_hd_id'],
                    'bom_factor'            => $data['bom_factor'],                        
                    'material_id'           => $d['material_id'],
                    'material_qty'          => $d['material_qty'],
                    'material_uom'          => $d['material_uom'],
                    'material_std_qty'      => $d['material_qty'],
                    'material_std_uom'      => $d['material_uom'],
                    'warehouse_id'          => $data['warehouse_id'],
                    'InvtNmbr'              => $data['InvtNmbr'],
                    'InvtType'              => $data['InvtType'],
                    'trans_hd_id'           => $data['trans_hd_id'],
                    'created_by'            => $data['updated_by'],
                    'updated_by'            => $data['updated_by']
                    ];
                    $PrdSeq = $PrdSeq + 1;
                }
                DB::table('precise.material_usage')
                ->insert($dt); 
               
                $trans = DB::table('precise.material_usage')
                ->where('trans_hd_id', $data['trans_hd_id'])
                ->select('usage_id')
                ->first();
                                
                DB::commit();
                return response()->json(['status' => 'ok', 'message' => $trans->usage_id], 200);
                
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

            $transid = DB::table('precise.material_usage')
            ->where('usage_id', $id)
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

            DB::table('precise.material_usage')
            ->where('trans_hd_id', $transid->trans_hd_id)
            ->delete();

            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'Material Usage have been deleted'], 200);
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
                $this->materialUsage = DB::table('precise.material_usage')->where([
                    'PrdNumber' => $value
                ])->count();
            }
            return response()->json(['status' => 'ok', 'message' => $this->materialUsage]);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\PPIC;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;

class ProductionResultController extends Controller
{
    private $productionResult;
    
    public function index(Request $request){
        $start = $request->get('start');
        $end = $request->get('end');
        $validator = Validator::make($request->all(), [
            'start'     => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'       => 'required|date_format:Y-m-d|after_or_equal:start'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->productionResult = DB::table('precise.production_result_hd as pr')
                ->whereBetween('pr.result_date', [$start, $end])
                ->select(
                    'pr.result_hd_id',
                    'pr.result_date',
                    'pr.result_shift', 
                    'pr.work_order_hd_id',
                    'pr.PrdNumber', 
                    'pr.ResultSeq',
                    'pr.created_on',
                    'pr.created_by',
                    'pr.updated_on',
                    'pr.updated_by'
                )->leftJoin('precise.work_order as wo','pr.work_order_hd_id','=','wo.work_order_hd_id')
                ->get();

            return response()->json(["data" => $this->productionResult]);
        }
    }

    public function show($id){
        $master = DB::table("precise.production_result_hd as wo")
            ->where("wo.result_hd_id", $id)
            ->select(
                'wo.result_hd_id',
                'wo.result_date',
                'wo.result_shift',
                'wo.work_order_hd_id',
                'wo.PrdNumber', 
                'wo.ResultSeq',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
             )
            ->first();

        $detail = DB::table('precise.production_result_dt as dt')
        ->where('dt.result_hd_id', $master->result_hd_id)
        ->select(
            'dt.result_dt_id',
            'dt.result_hd_id', 
            'dt.PrdNumber',
            'dt.ResultSeq',
            'dt.ProductCode', 
            'dt.product_id', 
            'dt.result_qty',
            'dt.result_warehouse',
            'dt.InvtNmbr',
            'dt.InvtType',
            'dt.trans_hd_id',
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
            "PrdNumber"           => $master->PrdNumber,
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
            'result_date'           =>'required|date_format:Y-m-d',
            'result_shift'          =>'required',
            'work_order_hd_id'      =>'required|exists:work_order,work_order_hd_id',
            'PrdNumber'             =>'required',
            'ResultSeq'             =>'required',
            'created_by'            =>'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try
            {
                $transhd = DB::table('precise.warehouse_trans_hd')
                ->insertGetId([
                    'trans_number'       => $data['trans_number'],
                    'trans_type'         => $data['trans_type'],
                    'trans_date'         => $data['result_date'],
                    'trans_from'         => $data['trans_from'],
                    'trans_description'  => $data['trans_description'],
                    'work_order_id'      => $data['work_order_hd_id'],
                    'work_order_number'  => $data['PrdNumber'],
                    'created_by'         => $data['created_by']
                ]);

                foreach($data['detail'] as $transdt){
                    $whDt[] = [
                        'trans_hd_id'           => $transhd,
                        'trans_number'          => $transdt['trans_number'],
                        'trans_type'            => $transdt['trans_type'],
                        'trans_seq'             => $transdt['trans_seq'],
                        'product_id'            => $transdt['product_id'],
                        'trans_in_qty'          => $transdt['result_qty'],
                        'trans_out_qty'         => $transdt['trans_out_qty'],
                        'trans_uom'             => $transdt['trans_uom'],
                        'trans_in_qty_t'        => $transdt['result_qty'],
                        'trans_out_qty_t'       => $transdt['trans_out_qty_t'],
                        'trans_uom_t'           => $transdt['trans_uom_t'],
                        'trans_price'           => $transdt['trans_price'],
                        'trans_qty_price'       => $transdt['trans_qty_price'],
                        'trans_ppn_percent'     => $transdt['trans_ppn_percent'],
                        'trans_ppn_amount'      => $transdt['trans_ppn_amount'],
                        'InvtSubLedger'         => $transdt['InvtSubLedger'],
                        'InvtRespCode'          => $transdt['InvtRespCode'],
                        'created_by'            => $transdt['created_by']
                    ];
                }

                DB::table('precise.warehouse_trans_dt')
                ->insert($whDt);


                $id = DB::table('precise.production_result_hd')
                ->insertGetId([
                    'result_date'       => $data['result_date'],
                    'result_shift'      => $data['result_shift'],
                    'work_order_hd_id'  => $data['work_order_hd_id'],
                    'PrdNumber'         => $data['PrdNumber'],
                    'ResultSeq'         => $data['ResultSeq'],
                    'created_by'        => $data['created_by']
                ]);

                foreach($data['detail'] as $d){
                    $dt[] = [
                        'result_hd_id'          => $id,
                        'PrdNumber'             => $d['PrdNumber'],
                        'ResultSeq'             => $d['ResultSeq'],
                        'ProductCode'           => $d['ProductCode'],
                        'product_id'            => $d['product_id'],
                        'result_qty'            => $d['result_qty'],
                        'result_warehouse'      => $d['result_warehouse'],
                        'InvtNmbr'              => $d['InvtNmbr'],
                        'InvtType'              => $d['InvtType'],
                        'trans_hd_id'           => $d['trans_hd_id'],
                        'created_by'            => $d['created_by']
                    ];
                }

                DB::table('precise.production_result_dt')
                ->insert($dt);

                $trans = DB::table('precise.production_result_hd')
                        ->where('result_hd_id', $id)
                        ->select('PrdNumber')
                        ->first();

                DB::commit();
                return response()->json(['status' => 'ok', 'message' => $trans->PrdNumber], 200);
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
            'result_hd_id'          => 'required',
            'result_date'           =>'required|date_format:Y-m-d',
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
                DB::table('precise.production_result_hd')
                ->where('result_hd_id',$data['result_hd_id'])
                ->update([
                    'result_date'         => $data['result_date'],
                    'result_shift'        => $data['result_shift'],
                    'work_order_hd_id'    => $data['work_order_hd_id'],
                    'PrdNumber'           => $data['PrdNumber'],
                    'ResultSeq'           => $data['ResultSeq'],
                    'updated_by'          => $data['updated_by']
                ]);

                if($data['inserted'] != null)
                {
                    foreach($data['inserted'] as $d)
                    {
                        $dt[] = [
                            'result_hd_id'          => $d['result_hd_id'],
                            'PrdNumber'             => $d['PrdNumber'],
                            'ResultSeq'             => $d['ResultSeq'],
                            'ProductCode'           => $d['ProductCode'],
                            'product_id'            => $d['product_id'],
                            'result_qty'            => $d['result_qty'],
                            'result_warehouse'      => $d['result_warehouse'],
                            'InvtNmbr'              => $d['InvtNmbr'],
                            'InvtType'              => $d['InvtType'],
                            'trans_hd_id'           => $d['trans_hd_id'],
                            'created_by'            => $d['created_by']
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
                            'ResultSeq'             => $d['ResultSeq'],
                            'ProductCode'           => $d['ProductCode'],
                            'product_id'            => $d['product_id'],
                            'result_qty'            => $d['result_qty'],
                            'result_warehouse'      => $d['result_warehouse'],
                            'InvtNmbr'              => $d['InvtNmbr'],
                            'InvtType'              => $d['InvtType'],
                            'trans_hd_id'           => $d['trans_hd_id'],
                            'updated_by'            => $d['updated_by']
                        ]);
                    }
                }

                if($data['deleted'] != null)
                {
                    $delete = array();
                    foreach($data['deleted'] as $del){
                        $delete[] = $del['production_result_dt'];
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

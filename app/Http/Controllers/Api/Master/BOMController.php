<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class BOMController extends Controller
{
    private $bom;

    public function show($id)
    {
        $this->bom = DB::table('bom_hd_id as s')
            ->where('bom_hd_id', $id)
            ->select(
                'a.bom_dt_id',
                'a.material_id',
                'b.product_code as Kode material',
                'b.product_name as Nama materal',
                'a.material_qty as Qty material',
                'a.material_uom as UOM material',
                'a.created_on as Tanggal input',
                'a.created_by as User input',
                'a.updated_on as Tanggal update',
                'a.updated_by as User update'
            )
            ->leftJoin('precise.product b', 'a.material_id', '=', 'b.product_id')
            ->get();
        return response()->json(['data' => $this->bom]);
    }

    public function showByWorkcenter($id)
    {
        $value = explode("-", $id);
        $this->bom = DB::table('precise.bom_hd as a')
            ->selectRaw("a.bom_hd_id, a.bom_code, a.bom_name, a.bom_description
            , concat(d.workcenter_code,'-',d.workcenter_name) as 'Workcenter'
            , c.product_code, c.product_name, a.product_qty , a.product_uom
            , case a.is_active 
                when 0 then 'Tidak aktif'
                when 1 then 'Aktif'
              end as 'is_active'
            , a.start_date, a.expired_date, a.usage_priority 
            , a.created_on, a.created_by, a.updated_on, a.updated_by ")
            ->leftJoin('product_workcenter as b', 'a.product_id', '=', 'b.product_id')
            ->leftJoin('product as c', 'a.product_id', '=', 'c.product_id')
            ->leftJoin('workcenter as d', 'b.workcenter_id', '=', 'd.workcenter_id')
            ->whereIn('b.workcenter_id', $value)
            ->get();
        return response()->json(['data' => $this->bom]);
    }

    public function showByProduct($id)
    {
        $this->bom = DB::table('precise.bom_hd')
            ->where('product_id', $id)
            ->select(
                'bom_hd_id',
                'bom_code',
                'bom_name'
            )
            ->get();
        return response()->json(['data' => $this->bom]);
    }

    public function create(Request $request){
        $data = $request->json()->all();
        $validator = Validator::make(json_decode(json_encode($data),true),[
            'bom_code'           => 'required|unique:bom_hd,bom_code',
            'bom_name'           => 'required|unique:bom_hd,bom_name',
            'product_id'         => 'required|exists:product,product_id',
            'product_qty'        => 'required',
            'start_date'         => 'required|date_format:Y-m-d',
            'expired_date'       => 'required|date_format:Y-m-d',
            'usage_priority'     => 'required',
            'created_by'         => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try
            {
                
                $id = DB::table('precise.bom_hd')
                ->insertGetId([
                    'bom_code'          => $data['bom_code'],
                    'bom_name'          => $data['bom_name'],
                    'bom_description'   => $data['bom_description'],
                    'product_id'        => $data['product_id'],
                    'product_qty'       => $data['product_qty'],
                    'product_uom'       => $data['product_uom'],
                    'start_date'        => $data['start_date'],
                    'expired_date'      => $data['expired_date'],
                    'usage_priority'    => $data['usage_priority'],
                    'created_by'        => $data['created_by']
                ]);

                foreach($data['detail'] as $d){
                    $dt[] = [
                        'bom_hd_id'             => $id,
                        'material_id'           => $d['material_id'],
                        'material_qty'          => $d['material_qty'],
                        'material_uom'          => $d['material_uom'],
                        'created_by'            => $d['created_by']
                    ];
                }

                DB::table('precise.bom_dt')
                ->insert($dt);

                $trans = DB::table('precise.bom_hd')
                        ->where('bom_hd_id', $id)
                        ->select('bom_code')
                        ->first();

                DB::commit();
                return response()->json(['status' => 'ok', 'message' => $trans->bom_code], 200);
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
            'bom_hd_id'          => 'required',
            'bom_code'           => 'required|unique:bom_hd,bom_code',
            'bom_name'           => 'required|unique:bom_hd,bom_name',
            'product_id'         => 'required|exists:product,product_id',
            'product_qty'        => 'required',
            'start_date'         => 'required|date_format:Y-m-d',
            'expired_date'       => 'required|date_format:Y-m-d',
            'usage_priority'     => 'required',
            'updated_by'         => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try
            {
                QueryController::reason($data);
                DB::table("precise.bom_hd")
                ->where("bom_hd_id",$data['bom_hd_id'])
                ->update([
                    'bom_code'              => $data['bom_code'],
                    'bom_name'              => $data['bom_name'],
                    'bom_description'       => $data['bom_description'],
                    'product_id'            => $data['product_id'],
                    'product_qty'           => $data['product_qty'],
                    'product_uom'           => $data['product_uom'],
                    'is_active'             => $data['is_active'],
                    'start_date'            => $data['start_date'],
                    'expired_date'          => $data['expired_date'],
                    'usage_priority'        => $data['usage_priority'],
                    'updated_by'            => $data['updated_by']
                ]);

                if($data['inserted'] != null)
                {
                    foreach($data['inserted'] as $d)
                    {
                        $dt[] = [
                            'bom_hd_id'             => $d['bom_hd_id'],
                            'material_id'           => $d['material_id'],
                            'material_qty'          => $d['material_qty'],
                            'material_uom'          => $d['material_uom'],
                            'created_by'            => $d['created_by']
                        ];
                    }

                    DB::table('precise.bom_dt')
                    ->insert($dt);

                    if($data['updated'] != null)
                    {
                        foreach($data['updated'] as $d)
                        {
                            DB::table("precise.bom_dt")
                            ->where("bom_dt_id",$d['bom_dt_id'])
                            ->update([
                                'bom_hd_id'         => $d['bom_hd_id'],
                                'material_id'       => $d['material_id'],
                                'material_qty'      => $d['material_qty'],
                                'material_uom'      => $d['material_uom'],
                                'updated_by'        => $d['updated_by']
                            ]);
                        }
                    }

                    if($data['deleted'] != null)
                    {
                        $delete = array();
                        foreach($data['deleted'] as $del){
                            $delete[] = $del['bom_dt_id'];
                        }

                        DB::table('precise.bom_dt')
                        ->whereIn('bom_dt_id', $delete)
                        ->delete();
                    }

                    DB::commit();
                    return response()->json(['status' => 'ok', 'message' => 'BOM have been updated'], 200);
                }
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

            DB::table('precise.bom_dt')
            ->where('bom_hd_id', $id)
            ->delete();

            DB::table('precise.bom_hd')
            ->where('bom_hd_id', $id)
            ->delete();

            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'BOM have been deleted'], 200);
        }catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}

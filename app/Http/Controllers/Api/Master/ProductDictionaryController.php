<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;

class ProductDictionaryController extends Controller
{
    private $productDictionary;
    public function index()
    { 
        $this->productDictionary = DB::table('precise.product_dictionary as a')
            ->select(
                'a.dictionary_id',
                'b.product_code',
                'b.product_name',
                'c.item_code',
                'c.item_name',
                'd.design_code',
                'd.design_name',
                'h.product_varian_code',
                'h.product_varian_name',
                'e.process_code',
                'f.product_brand_name', 
                DB::raw("
                    concat(g.color_type_code, ' - ', g.color_type_name) as 'color_type'
                "),
                'a.packing_qty', 
                DB::raw("
                    CASE 
                        WHEN a.is_active_sell = '1' THEN 'Aktif'
                        WHEN a.is_active_sell = '0' THEN 'Tidak aktif'
                    ELSE NULL
                    END as 'active_sell',
                    CASE 
                        WHEN a.is_active_production = '1' THEN 'Aktif'
                        WHEN a.is_active_production = '0' THEN 'Tidak aktif'
                        ELSE NULL
                    END as 'active_production'
                "),
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )->leftJoin('precise.product as b','a.product_id','=','b.product_id')
            ->leftJoin('precise.product_item as c','a.item_id','=','c.item_id')
            ->leftJoin('precise.product_design as d','a.design_id','=','d.design_id')
            ->leftJoin('precise.production_process_type as e','a.process_type_id','=','e.process_type_id')
            ->leftJoin('precise.product_brand as f','a.brand_id','=','f.product_brand_id')
            ->leftJoin('precise.color_type as g','a.color_id','=','g.color_type_id')
            ->leftJoin('precise.product_varian as h','a.varian_id','=','h.product_varian_id')
            ->get();

        return response()->json(["data"=>$this->productDictionary], 200);
    }

    public function show($id)
    {
        
        $this->productDictionary = DB::table('precise.product_dictionary as a')
            ->where('b.product_id',$id)
            ->select(
                'a.dictionary_id',
                'b.product_id',
                'b.product_code',
                'b.product_name',
                'c.item_id',
                'c.item_code',
                'd.design_id',
                'd.design_code',
                'a.varian_id',
                'h.product_varian_code',
                'h.product_varian_name',
                'e.process_type_id',
                'e.process_code',
                'a.brand_id',
                'f.product_brand_name',
                'a.color_id',
                'g.color_type_code',
                'a.packing_qty',
                'a.is_active_sell',
                'a.is_active_production',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )->leftJoin('precise.product as b','a.product_id','=','b.product_id')
            ->leftJoin('precise.product_item as c','a.item_id','=','c.item_id')
            ->leftJoin('precise.product_design as d','a.design_id','=','d.design_id')
            ->leftJoin('precise.production_process_type as e','a.process_type_id','=','e.process_type_id')
            ->leftJoin('precise.product_brand as f','a.brand_id','=','f.product_brand_id')
            ->leftJoin('precise.color_type as g','a.color_id','=','g.color_type_id')
            ->leftJoin('precise.product_varian as h','a.varian_id','=','h.product_varian_id')
            ->first();

        return response()->json($this->productDictionary, 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id'        => 'required|exists:product,product_id',
            'item_id'           => 'required|exists:product_item,item_id',
            'design_id'         => 'required|exists:product_design,design_id',
            'varian_id'         => 'required|exists:product_varian,product_varian_id',
            'process_type_id'   => 'required|exists:production_process_type,process_type_id',
            'color_id'          => 'required|exists:color_type,color_type_id',
            'packing_qty'       => 'required|numeric',
            'created_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->productDictionary = DB::table('precise.product_dictionary')
                ->insert([
                    'product_id'        => $request->product_id,
                    'item_id'           => $request->item_id,
                    'design_id'         => $request->design_id,
                    'varian_id'         => $request->varian_id,
                    'process_type_id'   => $request->process_type_id,
                    'brand_id'          => $request->brand_id,
                    'color_id'          => $request->color_id,
                    'packing_qty'       => $request->packing_qty,
                    'created_by'        => $request->created_by
                ]);
            if ($this->productDictionary == 0) {
                return response()->json(['status' => 'error', 'message' => 'Failed to create productDictionary, Contact your administrator']);
            } else {
                return response()->json(['status' => 'ok', 'message' => 'productDictionary has been created']);
            }
        }
    }

    public function update(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'dictionary_id'     => 'required|exists:product_dictionary,dictionary_id',
            'product_id'        => 'required|exists:product,product_id',
            'item_id'           => 'required|exists:product_item,item_id',
            'design_id'         => 'required|exists:product_design,design_id',
            'varian_id'         => 'required|exists:product_varian,product_varian_id',
            'process_type_id'   => 'required|exists:production_process_type,process_type_id',
            'color_id'          => 'required|exists:color_type,color_type_id',
            'packing_qty'       => 'required|numeric',
            'reason'            => 'required',
            'updated_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try {
                QueryController::reasonAction("update");
                $this->productDictionary = DB::table('precise.product_dictionary')
                    ->where('dictionary_id', $request->dictionary_id)
                    ->update([
                        'product_id'        => $request->product_id,
                        'item_id'           => $request->item_id,
                        'design_id'         => $request->design_id,
                        'varian_id'         => $request->varian_id,
                        'process_type_id'   => $request->process_type_id,
                        'brand_id'          => $request->brand_id,
                        'color_id'          => $request->color_id,
                        'packing_qty'       => $request->packing_qty,
                        'updated_by'        => $request->updated_by
                    ]);
                if($this->productDictionary == 0) {
                    DB::rollback();
                    return response()->json(['status' => 'error', 'message' => 'Failed to update productDictionary, Contact your administrator']);
                } else {
                    DB::commit();
                    return response()->json(['status' => 'ok', 'message' => 'productDictionary has been updated']);
                }
            }catch(\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            QueryController::reasonAction("delete");
            $this->productDictionary = DB::table('precise.product_dictionary')
                ->where('dictionary_id', $id)
                ->delete();

            if ($this->productDictionary == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'Failed to delete productDictionary, Contact your administrator']);
            } else {
                DB::commit();
                return response()->json(['status' => 'ok', 'message' =>'productDictionary has been deleted']);
            }
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}

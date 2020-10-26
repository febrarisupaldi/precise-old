<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;

class ProductGroupController extends Controller
{
    private $productGroup;
    public function index()
    {
        $this->productGroup = DB::table('product_group as pg')
            ->select(
                'pg.product_group_id',
                'pg.product_group_code',
                'pg.product_group_name',
                'pt.product_type_code',
                'stc.coa_code as stock_coa_code',
                'hc.coa_code as hpp_coa_code',
                'sc.coa_code as sales_coa_code',
                'src.coa_code as sales_return_coa_code',
                'dc.coa_code as discount_coa_code',
                'upc.coa_code as unbilled_purchase_coa_code',
                'udc.coa_code as unbilled_delivery_coa_code',
                'pg.created_on',
                'pg.created_by',
                'pg.updated_on',
                'pg.updated_by'
            )->leftJoin('precise.product_type as pt', 'pg.product_type_id', '=', 'pt.product_type_id')
            ->leftJoin('precise.coa as stc', 'pg.stock_coa_id', '=', 'stc.coa_id')
            ->leftJoin('precise.coa as hc', 'pg.hpp_coa_id', '=', 'hc.coa_id')
            ->leftJoin('precise.coa as sc', 'pg.sales_coa_id', '=', 'sc.coa_id')
            ->leftJoin('precise.coa as src', 'pg.sales_return_coa_id', '=', 'src.coa_id')
            ->leftJoin('precise.coa as dc', 'pg.discount_coa_id', '=', 'dc.coa_id')
            ->leftJoin('precise.coa as upc', 'pg.unbilled_purchase_coa_id', '=', 'upc.coa_id')
            ->leftJoin('precise.coa as udc', 'pg.unbilled_delivery_coa_id', '=', 'udc.coa_id')
            ->get();

        return response()->json(["data" => $this->productGroup]);
    }

    public function show($id)
    {
        $this->productGroup = DB::table('product_group as pg')
            ->where('pg.product_group_id', $id)
            ->select
            (
                'pg.product_group_id',
                'pg.product_group_code',
                'pg.product_group_name',
                'pg.product_type_id',
                'pt.product_type_code',	 
                'pg.stock_coa_id',
                'stc.coa_code AS stock_coa_code',
                'pg.hpp_coa_id',
                'hc.coa_code AS hpp_coa_code',	
                'pg.sales_coa_id',
                'sc.coa_code AS sales_coa_code',
                'pg.sales_return_coa_id',
                'src.coa_code AS sales_return_coa_code',
                'pg.discount_coa_id',
                'dc.coa_code AS discount_coa_code',
                'pg.unbilled_purchase_coa_id',
                'upc.coa_code AS unbilled_purchase_coa_code',
                'pg.unbilled_delivery_coa_id',
                'udc.coa_code AS unbilled_delivery_coa_code',
                'pg.created_on',
                'pg.created_by',
                'pg.updated_on',
                'pg.updated_by' 	 
            )->leftJoin('precise.product_type as pt', 'pg.product_type_id', '=', 'pt.product_type_id')
            ->leftJoin('precise.coa as stc', 'pg.stock_coa_id', '=', 'stc.coa_id')
            ->leftJoin('precise.coa as hc', 'pg.hpp_coa_id', '=', 'hc.coa_id')
            ->leftJoin('precise.coa as sc', 'pg.sales_coa_id', '=', 'sc.coa_id')
            ->leftJoin('precise.coa as src', 'pg.sales_return_coa_id', '=', 'src.coa_id')
            ->leftJoin('precise.coa as dc', 'pg.discount_coa_id', '=', 'dc.coa_id')
            ->leftJoin('precise.coa as upc', 'pg.unbilled_purchase_coa_id', '=', 'upc.coa_id')
            ->leftJoin('precise.coa as udc', 'pg.unbilled_delivery_coa_id', '=', 'udc.coa_id')
            ->first();

        return response()->json($this->productGroup);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_group_code'        => 'required|unique:product_group,product_group_code',
            'product_group_name'        => 'required',
            'product_type_id'           => 'required|exists:product_type,product_type_id',
            'stock_coa_id'              => 'required|numeric',
            'hpp_coa_id'                => 'required|numeric',
            'sales_coa_id'              => 'required|numeric',
            'sales_return_coa_id'       => 'required|numeric',
            'discount_coa_id'           => 'required|numeric',
            'unbilled_purchase_coa_id'  => 'required|numeric',
            'unbilled_delivery_coa_id'  => 'required|numeric',
            'created_by'                => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->productGroup = DB::table('product_group')
                ->insert([
                    'product_group_code'        => $request->product_group_code,
                    'product_group_name'        => $request->product_group_name,
                    'product_type_id'           => $request->product_type_id,
                    'stock_coa_id'              => $request->stock_coa_id,
                    'hpp_coa_id'                => $request->hpp_coa_id,
                    'sales_coa_id'              => $request->sales_coa_id,
                    'sales_return_coa_id'       => $request->sales_return_coa_id,
                    'discount_coa_id'           => $request->discount_coa_id,
                    'unbilled_purchase_coa_id'  => $request->unbilled_purchase_coa_id,
                    'unbilled_delivery_coa_id'  => $request->unbilled_delivery_coa_id,
                    'created_by'                => $request->created_by
                ]);

            if ($this->productGroup == 0) {
                return response()->json(['status' => 'error', 'message' => 'Failed to insert new product group']);
            } else {
                return response()->json(['status' => 'ok', 'message' => 'product group has been inserted']);
            }
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_group_id'          => 'required',
            'product_group_code'        => 'required',
            'product_group_name'        => 'required',
            'product_type_id'           => 'required|exists:product_type,product_type_id',
            'stock_coa_id'              => 'required|numeric',
            'hpp_coa_id'                => 'required|numeric',
            'sales_coa_id'              => 'required|numeric',
            'sales_return_coa_id'       => 'required|numeric',
            'discount_coa_id'           => 'required|numeric',
            'unbilled_purchase_coa_id'  => 'required|numeric',
            'unbilled_delivery_coa_id'  => 'required|numeric',
            'updated_by'                => 'required',
            'reason'                    => 'required'
        ]);


        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try {
                QueryController::reasonAction("update");
                $this->productGroup = DB::table('product_group')
                    ->where('product_group_id', $request->product_group_id)
                    ->update([
                        'product_group_code'        => $request->product_group_code,
                        'product_group_name'        => $request->product_group_name,
                        'product_type_id'           => $request->product_type_id,
                        'stock_coa_id'              => $request->stock_coa_id,
                        'hpp_coa_id'                => $request->hpp_coa_id,
                        'sales_coa_id'              => $request->sales_coa_id,
                        'sales_return_coa_id'       => $request->sales_return_coa_id,
                        'discount_coa_id'           => $request->discount_coa_id,
                        'unbilled_purchase_coa_id'  => $request->unbilled_purchase_coa_id,
                        'unbilled_delivery_coa_id'  => $request->unbilled_delivery_coa_id,
                        'updated_by'                => $request->updated_by
                    ]);

                if ($this->productGroup == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => 'Failed to update product group']);
                } else {
                    DB::commit();
                    return response()->json(['status' => 'ok', 'message' => 'product group has been updated']);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    public function check(Request $request)
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            if ($type == "code") {
                $this->checkProductGroup = DB::table('product_group')
                    ->where([
                        'product_group_code' => $value
                    ])->count();
            } else if ($type == "name") {
                $this->checkProductGroup = DB::table('product_group')
                    ->where([
                        'product_group_name' => $value
                    ])->count();
            }
            return response()->json(['status' => 'ok', 'message' => $this->checkProductGroup]);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ProductDesignController extends Controller
{
    private $productDesign, $checkProductDesign;
    public function index()
    {
        $this->productDesign = DB::table('product_design as a')
            ->select(
                'design_id',
                'design_code',
                'design_name',
                'design_description',
		        'a.appearance_id',
                'appearance_name',
		        'a.license_type_id',
                'license_type_name',
		        'a.color_type_id',
                DB::raw("concat(d.color_type_code, ' - ', d.color_type_name) as 'color_type',
                    case a.is_active_sell
                        when 0 then 'Tidak aktif'
                        when 1 then 'Aktif' 
                    end as 'is_active_sell'
                    , case a.is_active_production
                        when 0 then 'Tidak aktif'
                        when 1 then 'Aktif' 
                    end as 'is_active_production'
		            , case a.is_active
                        when 0 then 'Tidak aktif'
                        when 1 then 'Aktif' 
                    end as 'is_active'"),
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )->leftJoin('precise.product_appearance as b', 'a.appearance_id', '=', 'b.appearance_id')
            ->leftJoin('precise.product_license_type as c', 'a.license_type_id', '=', 'c.license_type_id')
            ->leftJoin('precise.color_type as d', 'a.color_type_id', '=', 'd.color_type_id')
            ->get();
        return response()->json(["data" => $this->productDesign]);
    }

    public function show($id)
    {
        $this->productDesign = DB::table('product_design as a')
            ->where('design_id', $id)
            ->select(
                'design_id',
                'design_code',
                'design_name',
                'design_description',
                'appearance_id',
                'license_type_id',
                'color_type_id',
                'is_active_sell',
                'is_active_production',
		        'is_active'
            )
            //->get();
	        ->first();
        //return response()->json(["data" => $this->productDesign]);
	return response()->json( $this->productDesign);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'design_code' => 'required|unique:product_design',
            'appearance_id' => 'required|exists:product_appearance,appearance_id',
	    'license_type_id' => 'required|exists:product_license_type,license_type_id',
            'color_type_id' => 'required|exists:color_type,color_type_id',
            'created_by' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->checkProductDesign = DB::table('product_design')
                ->insert([
                    'design_code' => $request->design_code,
                    'design_name' => $request->design_name,
                    'design_description' => $request->design_description,
                    'appearance_id' => $request->appearance_id,
		    'license_type_id' => $request->license_type_id,      
                    'color_type_id' => $request->color_type_id,
                    'created_by' => $request->created_by
                ]);

            if ($this->checkProductDesign == 0) {
                return response()->json(['status' => 'error', 'message' => 'Failed to insert new product design']);
            } else {
                return response()->json(['status' => 'ok', 'message' => 'product design has been inserted']);
            }
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'design_id' => 'required',
            'design_code' => 'required',
            'appearance_id' => 'required|exists:product_appearance,appearance_id',
            'license_type_id' => 'required|exists:product_license_type,license_type_id',
            'color_type_id' => 'required|exists:color_type,color_type_id',
            'is_active_sell' => 'boolean',
            'is_active_production' => 'boolean',
	    'is_active' => 'boolean',
            'updated_by' => 'required',
            'reason' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try {
                $helper = new HelperController();
                $helper->reason("update");
                $this->checkProductDesign = DB::table('product_design')
                    ->where('design_id', $request->design_id)
                    ->update([
                        'design_code' => $request->design_code,
                        'design_name' => $request->design_name,
                        'design_description' => $request->design_description,
                        'appearance_id' => $request->appearance_id,
                        'license_type_id' => $request->license_type_id,
 			'color_type_id' => $request->color_type_id,
                        'is_active_sell' => $request->is_active_sell,
                        'is_active_production' => $request->is_active_production,
			'is_active' => $request->is_active,
                        'updated_by' => $request->updated_by
                    ]);

                if ($this->checkProductDesign == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => 'Failed to update new product design']);
                } else {
                    DB::commit();
                    return response()->json(['status' => 'ok', 'message' => 'product design has been updated']);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $helper = new HelperController();
            $helper->reason("delete");
            $this->checkProductDesign = DB::table('product_design')
                ->where('design_id', $id)
                ->delete();

            if ($this->checkProductDesign == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Failed to delete product design, Contact your administrator']);
            } else {
                DB::commit();
                return response()->json(['status' => 'ok', 'message' => 'Product design has been deleted']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
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
                $this->checkProductDesign = DB::table('product_design')
                    ->where([
                        'design_code' => $value
                    ])->count();
            } else if ($type == "name") {
                $this->checkProductDesign = DB::table('product_design')
                    ->where([
                        'design_name' => $value
                    ])->count();
            }
            return response()->json(['status' => 'ok', 'message' => $this->checkProductDesign]);
        }
    }
}

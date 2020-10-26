<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;

class ProductController extends Controller
{
    private $product;

    public function index(Request $request)
    {
        $pk = $request->get('id');
        $validator = Validator::make($request->all(), [
            'id'   => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $id = explode("-", $pk);
            $this->product = DB::table('product as a')
                ->select(
                    'product_id',
                    'product_code',
                    'product_name',
                    'product_alias',
                    DB::raw(
                        "
                            concat(c.product_type_code, ' - ', c.product_type_name) as 'Tipe produk',
                            concat(b.product_group_code, ' - ', b.product_group_name)  as 'Group produk'
                        "),
                    'uom_code',
                    'a.created_on' ,
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by'
                )->leftJoin('product_group as b', 'a.product_group_id', '=', 'b.product_group_id')
                ->leftJoin('product_type as c', 'b.product_type_id', '=', 'c.product_type_id')
                ->whereIn('b.product_group_id', $id)
                ->orderBy('a.product_code')
                ->get();
            return response()->json(['data' => $this->product]);
        }
    }

    public function show($id)
    {
        $this->product = DB::table('precise.product as p')
            ->where('product_id', $id)
            ->select(
                'p.product_id',
                'p.product_code',
                'p.product_name',
                'p.product_alias',
                'pg.product_group_id',
                'pg.product_group_code',
                'pg.product_group_name',
                'p.uom_code',
                'p.product_barcode',
                'p.product_serial_number',
                'p.product_std_price',
                'p.is_active',
                'p.created_on',
                'p.created_by',
                'p.updated_on',
                'p.updated_by'
            )
            ->leftJoin('precise.product_group as pg','p.product_group_id','=','pg.product_group_id')
            ->first();

        return response()->json($this->product);
    }

    public function showByProductType(Request $request)
    {
        $pk = $request->get('id');
        $validator = Validator::make($request->all(), [
            'id'   => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $id = explode("-", $pk);
            $this->product = DB::table('precise.product as p')
                ->select(
                    'p.product_id',
                    'p.product_code',
                    'p.product_name',
                    'p.product_alias',
                    'pg.product_group_id',
                    'pg.product_group_code',
                    'pg.product_group_name',
                    'p.product_barcode',
                    'p.product_serial_number',
                    'p.uom_code',
                    'p.product_std_price',
                    'p.is_active',
                    'a.created_on',
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by'
                )
                ->leftJoin('product_group as pg', 'p.product_group_id', '=', 'pg.product_group_id')
                ->whereIn('pg.product_type_id', $id)
                ->orderBy('a.product_code')
                ->get();
            return response()->json(['data' => $this->product]);
        }
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'product_code'              => 'required|unique:product,product_code',
            'product_name'              => 'required',
            'product_alias'             => 'nullable',
            'product_group_id'          => 'required|exists:product_group,product_group_id',
            'product_barcode'           => 'required',
            'product_serial_number'     => 'required',
            'uom_code'                  => 'required|exists:uom,uom_code',
            'product_std_price'         => 'nullable|numeric',
            'created_by'                => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->product = DB::table('precise.product')
                ->insert([
                    'product_code'          =>$request->product_code,
                    'product_name'          =>$request->product_name,
                    'product_alias'         =>$request->product_alias,
                    'product_group_id'      =>$request->product_group_id, 
                    'product_barcode'       =>$request->product_barcode,
                    'product_serial_number' =>$request->product_serial_number,
                    'uom_code'              =>$request->uom_code,
                    'product_std_price'     =>$request->product_std_price,
                    'created_by'            =>$request->created_by
                ]);

            if ($this->product == 0) {
                return response()->json(['status' => 'error', 'message' => 'Failed to insert new product']);
            } else {
                return response()->json(['status' => 'ok', 'message' => 'product has been inserted']);
            }
        }
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id'                => 'required|exists:product,product_id',
            'product_code'              => 'required',
            'product_name'              => 'required',
            'product_alias'             => 'nullable',
            'product_group_id'          => 'required|exists:product_group,product_group_id',
            'product_barcode'           => 'required',
            'product_serial_number'     => 'required',
            'uom_code'                  => 'required|exists:uom,uom_code',
            'product_std_price'         => 'nullable|numeric',
            'created_by'                => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try {
                QueryController::reasonAction("update");
                $this->product = DB::table('precise.product')
                    ->where('product_id', $request->product_id)
                    ->update([
                        'product_code'          =>$request->product_code,
                        'product_name'          =>$request->product_name,
                        'product_alias'         =>$request->product_alias,
                        'product_group_id'      =>$request->product_group_id, 
                        'product_barcode'       =>$request->product_barcode,
                        'product_serial_number' =>$request->product_serial_number,
                        'uom_code'              =>$request->uom_code,
                        'product_std_price'     =>$request->product_std_price,
                        'updated_by'            =>$request->created_by
                    ]);

                if ($this->product == 0) {
                    DB::rollback();
                    return response()->json(['status' => 'error', 'message' => 'Failed to update new product']);
                } else {
                    DB::commit();
                    return response()->json(['status' => 'ok', 'message' => 'product has been updated']);
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
            $this->product = DB::table('precise.product')
                ->where('product_id', $id)
                ->delete();

            if ($this->product == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'Failed to delete product, Contact your administrator']);
            } else {
                DB::commit();
                return response()->json(['status' => 'ok', 'message' =>'product has been deleted']);
            }
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function check(Request $request)
    {
        $type   = $request->get('type');
        $value  = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type'  => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            if ($type == "code") {
                $this->product = DB::table('precise.product')
                    ->where('product_code', $value)
                    ->count();
            }

            return response()->json(['status' => 'ok', 'message' => $this->product]);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;

class SteelTypeController extends Controller
{
    private $steelType;
    public function index()
    { 
        $this->steelType = DB::table('precise.steel_type')
            ->select(
                'steel_type_id',
                'steel_type_name',
                'is_active',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();

        return response()->json(["data"=>$this->steelType], 200);
    }

    public function show($id)
    {
        $this->steelType = DB::table('precise.steel_type')
            ->where('steel_type_id',$id)
            ->select(
                'steel_type_id',
                'steel_type_name',
                'is_active',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();

        return response()->json($this->steelType, 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'steel_type_name'   => 'required|unique:steel_type,steel_type_name',
            'created_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->steelType = DB::table('precise.steel_type')
                ->insert([
                    'steel_type_name'   => $request->steel_type_name,
                    'created_by'        => $request->created_by
                ]);
            if ($this->steelType == 0) {
                return response()->json(['status' => 'error', 'message' => 'Failed to create Steel type, Contact your administrator']);
            } else {
                return response()->json(['status' => 'ok', 'message' => 'Steel type has been created']);
            }
        }
    }

    public function update(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'steel_type_id'     => 'required|exists:steel_type,steel_type_id',
            'steel_type_name'   => 'required',
            'is_active'         => 'required|boolean',
            'updated_by'        => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try {
                QueryController::reasonAction("update");
                $this->steelType = DB::table('precise.steel_type')
                    ->where('steel_type_id', $request->steel_type_id)
                    ->update([
                        'steel_type_name'   => $request->steel_type_name,
                        'is_active'         => $request->is_active,
                        'updated_by'        => $request->updated_by
                    ]);
                if($this->steelType == 0) {
                    DB::rollback();
                    return response()->json(['status' => 'error', 'message' => 'Failed to update Steel type, Contact your administrator']);
                } else {
                    DB::commit();
                    return response()->json(['status' => 'ok', 'message' => 'Steel type has been updated']);
                }
            }catch(\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
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
                $this->steelType = DB::table('precise.steel_type')
                    ->where('steel_type_name', $value)
                    ->count();
            }

            return response()->json(['status' => 'ok', 'message' => $this->steelType]);
        }
    }
}

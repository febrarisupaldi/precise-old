<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;

class CoolingMethodController extends Controller
{
    private $coolingMethod;
    public function index()
    { 
        $this->coolingMethod = DB::table('precise.cooling_method')
            ->select(
                'cooling_method_id',
                'cooling_method_name',
                'cooling_method_description',
                'is_active',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();
        
        return response()->json(["data"=>$this->coolingMethod],200);
    }

    public function show($id)
    {
        $this->coolingMethod = DB::table('precise.cooling_method')
            ->where('cooling_method_id', $id)
            ->select(
                'cooling_method_id',
                'cooling_method_name',
                'cooling_method_description',
                'is_active',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->first();
        
        return response()->json($this->coolingMethod,200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cooling_method_name'   => 'required|unique:cooling_method,cooling_method_name',
            'created_by'            => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->coolingMethod = DB::table('precise.cooling_method')
                ->insert([
                    'cooling_method_name'       => $request->cooling_method_name,
                    'cooling_method_description'=> $request->desc,
                    'created_by'                => $request->created_by
                ]);

            if ($this->coolingMethod == 0) {
                return response()->json(['status' => 'error', 'message' => 'Failed to create cooling method, Contact your administrator']);
            } else {
                return response()->json(['status' => 'ok', 'message' => 'Cooling Method has been created']);
            }
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cooling_method_id'     => 'required|exists:cooling_method,cooling_method_id',
            'cooling_method_name'   => 'required',
            'is_active'             => 'required|boolean',
            'reason'                => 'required',
            'updated_by'            => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try {
                QueryController::reasonAction("update");
                $this->coolingMethod = DB::table('precise.cooling_method')
                    ->where('cooling_method_id', $request->cooling_method_id)
                    ->update([
                        'cooling_method_name'       => $request->cooling_method_name,
                        'cooling_method_description'=> $request->desc,
                        'is_active'                 => $request->is_active,
                        'updated_by'                => $request->updated_by
                    ]);

                if ($this->coolingMethod == 0) {
                    DB::rollback();
                    return response()->json(['status' => 'error', 'message' => 'Failed to update cooling method, Contact your administrator']);
                } else {
                    DB::commit();
                    return response()->json(['status' => 'ok', 'message' => 'Cooling Method has been updated']);
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
            if ($type == "name") {
                $this->coolingMethod = DB::table('precise.cooling_method')
                    ->where('cooling_method_name', $value)
                    ->count();
            }

            return response()->json(['status' => 'ok', 'message' => $this->coolingMethod]);
        }
    }
}

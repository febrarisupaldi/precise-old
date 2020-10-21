<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;

class MachineTonnageController extends Controller
{
    private $machineTonnage;
    public function index()
    { 
        $this->machineTonnage = DB::table('precise.machine_tonnage')
            ->select(
                'tonnage_group',
                'tonnage_min',
                'tonnage_max',
                'is_active',	
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();

        return response()->json(["data"=>$this->machineTonnage], 200);
    }

    public function show($id)
    {
        $this->machineTonnage = DB::table('precise.machine_tonnage')
            ->where('tonnage_group',$id)
            ->select(
                'tonnage_group',
                'tonnage_min',
                'tonnage_max',
                'is_active',	
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->first();

        return response()->json($this->machineTonnage, 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tonnage_group' => 'required|unique:machine_tonnage,tonnage_group',
            'tonnage_min'   => 'required|numeric',
            'tonnage_max'   => 'required|numeric',
            'created_by'    => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->machineTonnage = DB::table('precise.machine_tonnage')
                ->insert([
                    'tonnage_group'     => $request->tonnage_group,
                    'tonnage_min'       => $request->tonnage_min,
                    'tonnage_max'       => $request->tonnage_max,
                    'created_by'        => $request->created_by
                ]);
            if ($this->machineTonnage == 0) {
                return response()->json(['status' => 'error', 'message' => 'Failed to create Machine tonnage, Contact your administrator']);
            } else {
                return response()->json(['status' => 'ok', 'message' => 'Machine tonnage has been created']);
            }
        }
    }

    public function update(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'tonnage_group' => 'required|exists:machine_tonnage,tonnage_group',
            'tonnage_min'   => 'required|numeric|lt:tonnage_max',
            'tonnage_max'   => 'required|numeric|gt:tonnage_min',
            'is_active'     => 'required|boolean',
            'reason'        => 'required',
            'updated_by'    => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try {
                QueryController::reasonAction("update");
                $this->machineTonnage = DB::table('precise.machine_tonnage')
                    ->where('tonnage_group', $request->tonnage_group)
                    ->update([
                        'tonnage_min'       => $request->tonnage_min,
                        'tonnage_max'       => $request->tonnage_max,
                        'is_active'         => $request->is_active,
                        'updated_by'        => $request->updated_by
                    ]);
                if($this->machineTonnage == 0) {
                    DB::rollback();
                    return response()->json(['status' => 'error', 'message' => 'Failed to update Machine tonnage, Contact your administrator']);
                } else {
                    DB::commit();
                    return response()->json(['status' => 'ok', 'message' => 'Machine tonnage has been updated']);
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
            if ($type == "group") {
                $this->dummy = DB::table('precise.machine_tonnage')
                    ->where('tonnage_group', $value)
                    ->count();
            }

            return response()->json(['status' => 'ok', 'message' => $this->dummy]);
        }
    }
}

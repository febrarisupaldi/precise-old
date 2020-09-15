<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;

class MoldStatusController extends Controller
{
    private $moldStatus;
    public function index(){
        $this->moldStatus = DB::table('precise.mold_status')
            ->select(
                'status_code',
                'status_description',
                'is_active',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();

        return response()->json(["data"=>$this->moldStatus]);
    }

    public function show($id){
        $this->moldStatus = DB::table('precise.mold_status')
            ->where('status_code', $id)
            ->select(
                'status_code',
                'status_description',
                'is_active'
            )
            ->first();

        return response()->json($this->moldStatus);
    }

    public function create(Request $request){
        
        $validator = Validator::make($request->all(), [
            'status_code'   => 'required|unique:mold_status,status_code',
            'created_by'    => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->moldStatus = DB::table('precise.mold_status')
                ->insert([
                    'status_code'       => $request->status_code,
                    'status_description'=> $request->desc,
                    'created_by'        => $request->created_by
                ]);
            if ($this->moldStatus == 0) {
                return response()->json(['status' => 'error', 'message' => 'Failed to create mold status, Contact your administrator']);
            } else {
                return response()->json(['status' => 'ok', 'message' => 'Mold status has been created']);
            }
        }
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'status_code'   => 'required',
            'is_active'     => 'boolean',
            'updated_by'    => 'required',
            'reason'        => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try {
                QueryController::reasonAction("update");
                $this->moldStatus = DB::table('precise.mold_status')
                    ->where('status_code', $request->status_code)
                    ->update([
                        'status_code'       => $request->status_code,
                        'status_description'=> $request->desc,
                        'is_active'         => $request->is_active,
                        'updated_by'        => $request->updated_by
                    ]);
                
                if ($this->moldStatus == 0) {
                    DB::rollback();
                    return response()->json(['status' => 'error', 'message' => 'Failed to update mold status, Contact your administrator']);
                } else {
                    DB::commit();
                    return response()->json(['status' => 'ok', 'message' => 'Mold status has been updated']);
                }
            }catch(\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    public function check(Request $request){
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type'  => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            if ($type == "code") {
                $this->moldStatus = DB::table('precise.mold_status')
                    ->where('status_code', $value)
                    ->count();
            }

            return response()->json(['status' => 'ok', 'message' => $this->moldStatus]);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Master\HelperController;

class RejectGroupController extends Controller
{
    private $rejectGroup;
    public function index()
    {
        $this->rejectGroup = DB::table('precise.reject_group as a')
            ->select(
                'a.reject_group_id',
                'a.reject_group_code',
                'a.reject_group_name',
                'a.reject_group_description',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by' 
            )
            ->get();

        return response()->json(["data"=> $this->rejectGroup], 200);
    }

    public function show($id)
    {
        $this->rejectGroup = DB::table('precise.reject_group as a')
            ->where('a.reject_group_id', $id)
            ->select(
                'a.reject_group_id',
                'a.reject_group_code',
                'a.reject_group_name',
                'a.reject_group_description',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by' 
            )
            ->first();

        return response()->json($this->rejectGroup, 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'reject_group_code' => 'required|unique:reject_group,reject_group_code',
            'reject_group_name' => 'required',
            'created_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->rejectGroup = DB::table('precise.reject_group')
                ->insert([
                    'reject_group_code'         => $request->reject_group_code,
                    'reject_group_name'         => $request->reject_group_name,
                    'reject_group_description'  => $request->reject_group_description,
                    'created_by'                => $request->created_by
                ]);
            
            if ($this->rejectGroup == 0) {
                return response()->json(['status' => 'error', 'message' => 'Failed to insert reject group, Contact your administrator']);
            } else {
                return response()->json(['status' => 'ok', 'message' =>'Reject group has been inserted']);
            }
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'reject_group_id'   => 'required|exists:reject_group,reject_group_id',
            'reject_group_code' => 'required',
            'reject_group_name' => 'required',
            'reason'            => 'required',
            'updated_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            try {
                DB::beginTransaction();
                $helper = new HelperController();
                $helper->reason("update");

                $this->rejectGroup = DB::table('precise.reject_group')
                    ->where('reject_group_id', $request->reject_group_id)
                    ->update([
                        'reject_group_code'         => $request->reject_group_code,
                        'reject_group_name'         => $request->reject_group_name,
                        'reject_group_description'  => $request->reject_group_description,
                        'updated_by'                => $request->updated_by
                    ]);
                
                if ($this->rejectGroup == 0) {
                    return response()->json(['status' => 'error', 'message' => 'Failed to update reject group, Contact your administrator']);
                } else {
                    return response()->json(['status' => 'ok', 'message' =>'Reject group has been updated']);
                }
            }catch(\Exception $e){
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

            $this->reject = DB::table('precise.reject_group')
                ->where('reject_group_id', $id)
                ->delete();

            if ($this->reject == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'Failed to delete reject group, Contact your administrator']);
            } else {
                DB::commit();
                return response()->json(['status' => 'ok', 'message' =>'Reject group has been deleted']);
            }
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function check(Request $request)
    {
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
                $this->rejectGroup = DB::table('precise.reject_group')
                    ->where('reject_group_code', $value)
                    ->count();
            }

            return response()->json(['status' => 'ok', 'message' => $this->rejectGroup]);
        }
    }
}

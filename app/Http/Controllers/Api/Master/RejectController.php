<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Master\HelperController;

class RejectController extends Controller
{
    private $reject;
    public function index(Request $request)
    {
        $wc = $request->get('workcenter');
        $validator = Validator::make($request->all(), [
            'workcenter' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $workcenter = explode("-",$wc);
            $this->reject = DB::table('precise.reject as a')
                ->whereIn('a.workcenter_id', $workcenter)
                ->select(
                    'a.reject_id',
                    'a.reject_code',
                    'a.reject_name',
                    'a.reject_description', 
                    'r.reject_group_code',
                    'r.reject_group_name',
                    'a.created_on',
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by' 
                )
                ->leftJoin('precise.reject_group as r','a.reject_group_id','=','r.reject_group_id')
                ->leftJoin('precise.workcenter as w','a.workcenter_id','=','w.workcenter_id')
                ->get();

            return response()->json(["data"=> $this->reject], 200);
        }
    }

    public function show($id)
    {
        $this->reject = DB::table('precise.reject as a')
            ->where('a.reject_id', $id)
            ->select(
                'a.reject_id',
                'a.reject_code',
                'a.reject_name',
                'a.reject_description',
                'a.reject_group_id', 
                'r.reject_group_code',
                'r.reject_group_name',
                'a.workcenter_id',
                'w.workcenter_code',
                'w.workcenter_name',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by' 
            )
            ->leftJoin('precise.reject_group as r','a.reject_group_id','=','r.reject_group_id')
            ->leftJoin('precise.workcenter as w','a.workcenter_id','=','w.workcenter_id')
            ->first();
                
        return response()->json($this->reject, 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'reject_code'     => 'required|unique:reject,reject_code',
            'reject_name'     => 'required',
            'reject_group_id' => 'required|exists:reject_group,reject_group_id',
            'workcenter_id'   => 'required|exists:workcenter,workcenter_id',
            'created_by'      => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->reject = DB::table('precise.reject')
                ->insert([
                    'reject_code'         => $request->reject_code,
                    'reject_name'         => $request->reject_name,
                    'reject_description'  => $request->reject_description,
                    'reject_group_id'     => $request->reject_group_id,
                    'workcenter_id'       => $request->workcenter_id,
                    'created_by'          => $request->created_by
                ]);

            if ($this->reject == 0) {
                return response()->json(['status' => 'error', 'message' => 'Failed to insert reject, Contact your administrator']);
            } else {
                return response()->json(['status' => 'ok', 'message' =>'Reject has been inserted']);
            }
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'reject_id'         => 'required|exists:reject,reject_id',
            'reject_code'       => 'required',
            'reject_name'       => 'required',
            'reject_group_id'   => 'required|exists:reject_group,reject_group_id',
            'workcenter_id'     => 'required|exists:workcenter,workcenter_id',
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
                $this->reject = DB::table('precise.reject')
                    ->where('reject_id', $request->reject_id)
                    ->update([
                        'reject_code'         => $request->reject_code,
                        'reject_name'         => $request->reject_name,
                        'reject_description'  => $request->reject_description,
                        'reject_group_id'     => $request->reject_group_id,
                        'workcenter_id'       => $request->workcenter_id,
                        'updated_by'          => $request->updated_by
                    ]);

                if ($this->reject == 0) {
                    DB::rollback();
                    return response()->json(['status' => 'error', 'message' => 'Failed to update reject, Contact your administrator']);
                } else {
                    DB::commit();
                    return response()->json(['status' => 'ok', 'message' =>'Reject has been updated']);
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

            $this->reject = DB::table('precise.reject')
                ->where('reject_id', $id)
                ->delete();

            if ($this->reject == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'Failed to delete reject, Contact your administrator']);
            } else {
                DB::commit();
                return response()->json(['status' => 'ok', 'message' =>'Reject has been deleted']);
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
                $this->downtime = DB::table('precise.reject')
                    ->where('reject_code', $value)
                    ->count();
            }

            return response()->json(['status' => 'ok', 'message' => $this->reject]);
        }
    }
}


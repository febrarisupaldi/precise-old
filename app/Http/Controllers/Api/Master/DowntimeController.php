<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Master\HelperController;

class DowntimeController extends Controller
{
    private $downtime;

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
            $this->downtime = DB::table('precise.downtime as a')
                ->whereIn('w.workcenter_id', $workcenter)
                ->select(
                    'a.downtime_id',
                    'a.downtime_code',
                    'a.downtime_name',
                    'a.downtime_description', 
                    'g.downtime_group_code',
                    'g.downtime_group_name',
                    'a.std_duration',
                    DB::raw("
                        case a.is_planned 
                            when 0 then 'Tidak aktif'
                            when 1 then 'Aktif' 
                        end as 'is_planned',
                        case a.is_need_approval 
                            when 0 then 'Tidak aktif'
                            when 1 then 'Aktif' 
                        end as 'is_need_approval'
                    "),
                    'a.to_be_added1',
                    'a.to_be_added2',
                    'a.created_on',
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by' 
                )
                ->leftJoin('precise.downtime_group as g','a.downtime_group_id','=','g.downtime_group_id')
                ->leftJoin('precise.workcenter as w','a.workcenter_id','=','w.workcenter_id')
                ->get();
            
            return response()->json(["data"=> $this->downtime], 200);
        }
    }

    public function show($id)
    {
        $this->downtime = DB::table('precise.downtime as a')
            ->where('a.downtime_id', $id)
            ->select(
                'a.downtime_id',
                'a.downtime_code',
                'a.downtime_name',
                'a.downtime_description', 
                'a.downtime_group_id',
                'g.downtime_group_code',
                'g.downtime_group_name',
                'a.workcenter_id',
                'w.workcenter_code',
                'w.workcenter_name',
                'a.std_duration',
                'a.is_planned',
                'a.is_need_approval',
                'a.to_be_added1',
                'a.to_be_added2',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by' 
            )
            ->leftJoin('precise.downtime_group as g','a.downtime_group_id','=','g.downtime_group_id')
            ->leftJoin('precise.workcenter as w','a.workcenter_id','=','w.workcenter_id')
            ->first();

        return response()->json($this->downtime, 200);
    }


    public function create(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'downtime_code'     => 'required|unique:downtime,downtime_code',
            'downtime_name'     => 'required',
            'downtime_group_id' => 'required|exists:downtime_group,downtime_group_id',
            'workcenter_id'     => 'required|exists:workcenter,workcenter_id',
            'std_duration'      => 'nullable|date_format:H:i:s',
            'is_planned'        => 'required|boolean',
            'is_need_approval'  => 'required|boolean',
            'created_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->downtime = DB::table('precise.downtime')
                ->insert([
                    'downtime_code'         => $request->downtime_code,
                    'downtime_name'         => $request->downtime_name,
                    'downtime_description'  => $request->downtime_description,
                    'downtime_group_id'     => $request->downtime_group_id,
                    'workcenter_id'         => $request->workcenter_id,
                    'std_duration'          => $request->std_duration,
                    'is_planned'            => $request->is_planned,
                    'is_need_approval'      => $request->is_need_approval,
                    'to_be_added1'          => $request->to_be_added1,
                    'to_be_added2'          => $request->to_be_added2,
                    'created_by'            => $request->created_by
                ]);

            if ($this->downtime == 0) {
                return response()->json(['status' => 'error', 'message' => 'Failed to insert downtime, Contact your administrator']);
            } else {
                return response()->json(['status' => 'ok', 'message' =>'Downtime has been inserted']);
            }
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'downtime_id'       => 'required|exists:downtime,downtime_id',
            'downtime_code'     => 'required',
            'downtime_name'     => 'required',
            'downtime_group_id' => 'required|exists:downtime_group,downtime_group_id',
            'workcenter_id'     => 'required|exists:workcenter,workcenter_id',
            'std_duration'      => 'nullable|date_format:H:i:s',
            'is_planned'        => 'required|boolean',
            'is_need_approval'  => 'required|boolean',
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
                $this->downtime = DB::table('precise.downtime')
                    ->where('downtime_id', $request->downtime_id)
                    ->update([
                        'downtime_code'         => $request->downtime_code,
                        'downtime_name'         => $request->downtime_name,
                        'downtime_description'  => $request->downtime_description,
                        'downtime_group_id'     => $request->downtime_group_id,
                        'workcenter_id'         => $request->workcenter_id,
                        'std_duration'          => $request->std_duration,
                        'is_planned'            => $request->is_planned,
                        'is_need_approval'      => $request->is_need_approval,
                        'to_be_added1'          => $request->to_be_added1,
                        'to_be_added2'          => $request->to_be_added2,
                        'updated_by'            => $request->updated_by
                    ]);


                if ($this->downtime == 0) {
                    DB::rollback();
                    return response()->json(['status' => 'error', 'message' => 'Failed to update downtime, Contact your administrator']);
                } else {
                    DB::commit();
                    return response()->json(['status' => 'ok', 'message' =>'Downtime has been updated']);
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

            $this->downtime = DB::table('precise.downtime')
                ->where('downtime_id', $id)
                ->delete();

            if ($this->downtime == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'Failed to delete downtime, Contact your administrator']);
            } else {
                DB::commit();
                return response()->json(['status' => 'ok', 'message' =>'Downtime has been deleted']);
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
                $this->downtime = DB::table('precise.downtime')
                    ->where('downtime_code', $value)
                    ->count();
            }

            return response()->json(['status' => 'ok', 'message' => $this->downtime]);
        }
    }
}

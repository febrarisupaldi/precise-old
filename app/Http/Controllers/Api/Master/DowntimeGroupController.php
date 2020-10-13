<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Master\HelperController;

class DowntimeGroupController extends Controller
{
    private $downtimeGroup;
    public function index()
    {
        $this->downtimeGroup = DB::table('precise.downtime_group as a')
            ->select(
                'a.downtime_group_id',
                'a.downtime_group_code',
                'a.downtime_group_name',
                'a.downtime_group_description',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by' 
            )
            ->get();

        
        return response()->json(["data"=>$this->downtimeGroup],200);
    }

    public function show($id)
    {
        $this->downtimeGroup = DB::table('precise.downtime_group as a')
            ->where('downtime_group_id',$id)
            ->select(
                'a.downtime_group_id',
                'a.downtime_group_code',
                'a.downtime_group_name',
                'a.downtime_group_description',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by' 
            )
            ->get();

        
        return response()->json($this->downtimeGroup,200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'downtime_group_code'   => 'required|unique:downtime_group,downtime_group_code',
            'downtime_group_name'   => 'required',
            'created_by'            => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->downtimeGroup = DB::table('precise.downtime_group')
                ->insert([
                    'downtime_group_code'       => $request->downtime_group_code,
                    'downtime_group_name'       => $request->downtime_group_name,
                    'downtime_group_description'=> $request->desc,
                    'created_by'                => $request->created_by
                ]);

            if ($this->downtimeGroup == 0) {
                return response()->json(['status' => 'error', 'message' => 'Failed to insert downtime group, Contact your administrator']);
            } else {
                return response()->json(['status' => 'ok', 'message' =>'Downtime group has been inserted']);
            }
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'downtime_group_id'     => 'required|exists:downtime_group,downtime_group_id',
            'downtime_group_code'   => 'required',
            'downtime_group_name'   => 'required',
            'reason'                => 'required',
            'updated_by'            => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            try {
                DB::beginTransaction();
                $helper = new HelperController();
                $helper->reason("update");
                $this->downtimeGroup = DB::table('precise.downtime_group')
                    ->where('downtime_group_id', $request->downtime_group_id)
                    ->update([
                        'downtime_group_code'       => $request->downtime_group_code,
                        'downtime_group_name'       => $request->downtime_group_name,
                        'downtime_group_description'=> $request->desc,
                        'updated_by'                => $request->updated_by
                    ]);

                if ($this->downtimeGroup == 0) {
                    DB::rollback();
                    return response()->json(['status' => 'error', 'message' => 'Failed to update downtime group, Contact your administrator']);
                } else {
                    DB::commit();
                    return response()->json(['status' => 'ok', 'message' =>'Downtime group has been updated']);
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

            $this->downtimeGroup = DB::table('precise.downtime_group')
                ->where('downtime_group_id', $id)
                ->delete();

            if ($this->downtimeGroup == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'Failed to delete downtime group, Contact your administrator']);
            } else {
                DB::commit();
                return response()->json(['status' => 'ok', 'message' =>'Downtime group has been deleted']);
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
                $this->downtimeGroup = DB::table('precise.downtime_group')
                    ->where('downtime_group_code', $value)
                    ->count();
            }

            return response()->json(['status' => 'ok', 'message' => $this->downtimeGroup]);
        }
    }
}


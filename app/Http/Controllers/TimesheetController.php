<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

// Controller that handles timesheet CRUD, approval/rejection, and stats.
// Key behaviors:
// - Employees can manage their own timesheets.
// - Admins can view all timesheets and approve/reject entries.
class TimesheetController extends Controller
{
   
    /**
     * Create a new timesheet entry
     * Validates input, checks duplicate per user/project/date and sets status to Pending.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project' => 'required|string|max:255',
            'hours_worked' => 'required|numeric|min:1|max:12',
            'date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        
        $user = $request->user();

        $duplicate = Timesheet::where('user_id', $user->id)
            ->where('project', $request->project)
            ->where('date', $request->date)
            ->first();

        if ($duplicate) {
            return response()->json(['error' => 'This project is already logged for today.'], 409);
        }

      
        $timesheet = Timesheet::create([
            'user_id' => $user->id,
            'project' => $request->project,
            'hours_worked' => $request->hours_worked,
            'date' => $request->date,
            'notes' => $request->notes,
            'status' => 'Pending',
        ]);

        return response()->json([
            'message' => 'Timesheet submitted successfully.',
            'data' => $timesheet
        ], 201);
    }

    /**
     * List timesheets
     * Supports filtering by status, project, and date range. Employees are scoped to their own records.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Timesheet::with('user');

        
        if ($user->role === 'employee') {
            $query->where('user_id', $user->id);
        }

      
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('project')) {
            $query->where('project', 'like', "%{$request->project}%");
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        }

        return response()->json($query->get());
    }

   
    /**
     * Approve a timesheet (admin only)
     * Sets status to Approved and records approver and timestamp.
     */
    public function approve($id, Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only admin can approve.'], 403);
        }

        $timesheet = Timesheet::findOrFail($id);
        $timesheet->update([
            'status' => 'Approved',
            'approved_by' => $user->id,
            'approved_at' => Carbon::now(),
        ]);

        return response()->json(['message' => 'Timesheet approved successfully.']);
    }

    
    /**
     * Reject a timesheet (admin only)
     * Sets status to Rejected and records approver and timestamp.
     */
    public function reject($id, Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only admin can reject.'], 403);
        }

        $timesheet = Timesheet::findOrFail($id);
        $timesheet->update([
            'status' => 'Rejected',
            'approved_by' => $user->id,
            'approved_at' => Carbon::now(),
        ]);

        return response()->json(['message' => 'Timesheet rejected.']);
    }

   
    /**
     * Return statistics about timesheets
     * Employees receive personal stats; admins receive global stats.
     */
    public function stats(Request $request)
    {
        $user = $request->user();

       
        if ($user->role === 'employee') {
            $total = Timesheet::where('user_id', $user->id)->count();
            $approved = Timesheet::where('user_id', $user->id)->where('status', 'Approved')->count();
            $pending = Timesheet::where('user_id', $user->id)->where('status', 'Pending')->count();
            $rejected = Timesheet::where('user_id', $user->id)->where('status', 'Rejected')->count();
            $total_hours = Timesheet::where('user_id', $user->id)->sum('hours_worked');
            $average_hours = Timesheet::where('user_id', $user->id)->avg('hours_worked');
        } else {
           
            $total = Timesheet::count();
            $approved = Timesheet::where('status', 'Approved')->count();
            $pending = Timesheet::where('status', 'Pending')->count();
            $rejected = Timesheet::where('status', 'Rejected')->count();
            $total_hours = Timesheet::sum('hours_worked');
            $average_hours = Timesheet::avg('hours_worked');
        }

        return response()->json([
            'total' => $total,
            'approved' => $approved,
            'pending' => $pending,
            'rejected' => $rejected,
            'total_hours' => $total_hours,
            'average_hours' => $average_hours,
        ]);
    }

    

    /**
     * Update a timesheet
     * Only owner or admin can update. Update resets status to Pending and clears approval fields.
     */
    public function update(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'project' => 'required|string|max:255',
        'hours_worked' => 'required|numeric|min:1|max:12',
        'date' => 'required|date|before_or_equal:today',
        'notes' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $timesheet = Timesheet::findOrFail($id);
    
    // Check if user owns this timesheet or is admin
    if ($request->user()->role !== 'admin' && $timesheet->user_id !== $request->user()->id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    // Check for duplicate (excluding current timesheet)
    $duplicate = Timesheet::where('user_id', $request->user()->id)
        ->where('project', $request->project)
        ->where('date', $request->date)
        ->where('id', '!=', $id)
        ->first();

    if ($duplicate) {
        return response()->json(['error' => 'This project is already logged for today.'], 409);
    }

    $timesheet->update([
        'project' => $request->project,
        'hours_worked' => $request->hours_worked,
        'date' => $request->date,
        'notes' => $request->notes,
        'status' => 'Pending', // Reset status when editing
        'approved_by' => null,
        'approved_at' => null,
    ]);

    return response()->json([
        'message' => 'Timesheet updated successfully.',
        'data' => $timesheet
    ]);
}

    /**
     * Delete a timesheet
     * Only owner or admin can delete.
     */
    public function destroy($id, Request $request)
{
    $timesheet = Timesheet::findOrFail($id);

    // Check if user owns this timesheet or is admin
    if ($request->user()->role !== 'admin' && $timesheet->user_id !== $request->user()->id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $timesheet->delete();

    return response()->json(['message' => 'Timesheet deleted successfully.']);
}
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Repository\ITaskRepository;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    protected $task;

    public function __construct(ITaskRepository $task)
    {
        $this->task = $task;
    }

    public function createTask(Request $request) {
        $validateTask = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'title' => 'required|string',
            'description' => 'required|string',
            'status' => 'required|string',
            'priority' => 'required|string|in:high,low,medium',
            'location' => 'nullable|string',
            'religion' => 'nullable|string',
            'gender' => 'nullable|string',
            'no_of_participants' => 'nullable|string',
            'task_duration' => 'nullable|string',
            'payment_per_task' => 'nullable|string',
            'type_of_comment' => 'nullable|string',
            'social_media_url' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'task_amount' => 'nullable|integer',
            'task_type' => 'required|integer',
            'task_count_total' => 'nullable|integer',
            'task_count_remaining' => 'nullable|integer',
            'platforms' => 'nullable|string',
            'category' => 'required|string|in:social_media,onlinvideo_markettinge,micro_influence,promotion,telegram',
            'file_path' => 'nullable|array',
            'file_path.*' => 'file|mimes:jpeg,png,jpg|max:10240',
        
            'video_path' => 'nullable|array',
            'video_path.*' => 'file|mimes:mp4,mov,avi,gif|max:10240',
        ]);


        if ($validateTask->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validateTask->errors(),
            ], 422); 
        }

        $task = $this->task->create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Task created successfully',
            'data' => $task,
        ], 201);

    }

    public function updateTask(Request $request, $id) {
        $validateTask = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'status' => 'required|string',
            'priority' => 'required|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'task_amount' => 'nullable|integer',
            'task_type' => 'required|integer',
            'task_count_total' => 'nullable|integer',
            'task_count_remaining' => 'nullable|integer',
            'platforms' => 'nullable|string',
        ]);

        if ($validateTask->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validateTask->errors(),
            ], 422);

        }

        $validatedData = $validateTask->validated();
        $task = $this->task->update($id, $validatedData);

        return response()->json([
            'status' => true,
            'message' => 'Task updated successfully',
            'data' => $task,
        ], 200);

    }

    public function showAll()
    {
        $tasks = $this->task->showAll();

        if (!$tasks) {
            return response()->json([
                'status' => false,
                'message' => 'No Available Tasks found at the moment',
            ], 404);
        }

        foreach($tasks as $task) {
            // Format created_at
            $task->created_at = $task->created_at->diffForHumans();
            
            // Calculate completion percentage
            $total_task = $task->task_count_total;
            $task_completed = $total_task - $task->task_count_remaining;
            
            $completionPercentage = ($total_task > 0) 
                ? round(($task_completed / $total_task) * 100, 2)
                : 0;
            
            // Add the percentage to the task object
            $task->completion_percentage = $completionPercentage;

            $task->completed = ($task->completed == 1) ? 'Completed' : 'Available';

            //check if task is new or not
            $createdAt = $task->created_at;
            $now = now();
            $hoursDifference = $createdAt->diffInHours($now);
            
            $newStatus = ($hoursDifference < 12) ? 'New Task' : '';
                
            $task->posted_status = $newStatus;
        }

        return response()->json([
            'status' => true,
            'message' => 'Task retrieved successfully',
            'data' => $tasks,
        ], 200);
    }

    public function show($id)
    {
        $task = $this->task->show($id);

        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'Task not found',
            ], 404);
        }

            $createdAt = $task->created_at;
            $now = now();
            $hoursDifference = $createdAt->diffInHours($now);
            
            $newStatus = ($hoursDifference < 12) ? 'new' : '';
                
            $total_task = $task->task_count_total;
            $task_completed = $total_task - $task->task_count_remaining;
            
            $completionPercentage = ($total_task > 0) 
                ? round(($task_completed / $total_task) * 100, 2)
                : 0;
            
            // Add the percentage to the task object
            $task->completion_percentage = $completionPercentage;
            $task->completed = ($task->completed == 1) ? 'Completed' : 'Available';

            $task->posted_status = $newStatus;

        return response()->json([
            'status' => true,
            'message' => 'Task retrieved successfully',
            'data' => $task,
        ], 200);
    }

    public function submitTask(Request $request, $id) {
        $validate = Validator::make($request->all(), [
            'screenshot' => 'required|mimes:jpg,png,jpeg|max:2048',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validate->errors(),
            ], 422);
        }
        $validated = $validate->validated();
        $task = $this->task->submitTask($request, $id);        
        return response()->json([
            'status' => true,
            'message' => 'Task submitted successfully, kindly wait for approval',
            'data' => $task,
        ]);
    }

    public function approveTask(Request $request, $id) {
        $validate = Validator::make($request->all(), [
            'status' => 'required|string',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validate->errors(),
            ], 422);
        }
        $status = $validate->validated();
        $task = $this->task->approveTask($id);
            
        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'Task not found or already approved',
            ], 404);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Task approved successfully',
            'data' => $task,
        ], 200);
    
    }

    public function approveCompletedTask(Request $request, $id) {
        $task = $this->task->approveCompletedTask($id);
            
        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'Task not found or already approved',
            ], 404);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Task approved successfully',
            'data' => $task,
        ], 200);
    
    }

    public function deleteTask($id) {
        $task = $this->task->delete($id);
        return response()->json([
            'status' => true,
            'message' => 'Task deleted successfully',
            'data' => $task,
        ], 200);
    }

    public function pendingTask() {
        $task = $this->task->pendingTask();
        if($task > 0) {
            return response()->json([
                'status' => true,
                'message' => 'Task retrieved successfully',
                'data' => $task,
            ]);
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'No Pending Tasks found at the moment',
            ]);
        }
        
    }

    public function completedTask() {
        $task = $this->task->completedTask();
        if($task > 0) {
            return response()->json([
                'status' => true,
                'message' => 'Task retrieved successfully',
                'data' => $task,
            ]);
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'No Completed Tasks found at the moment',
            ]);
        }
        
    }

    public function rejectTask() {
        $task = $this->task->rejectedTask();
       if($task > 0) {
            return response()->json([
                'status' => true,
                'message' => 'Task retrieved successfully',
                'data' => $task,
            ]);
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'No Rejected Tasks found at the moment',
            ]);
        }
    }

    public function taskHistory() {
        $tasks = $this->task->taskHistory();
        $task = count($tasks);
        if($task > 0) {
            return response()->json([
                'status' => true,
                'message' => 'Task retrieved successfully',
                'data' => $tasks,
            ]);
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'No Task History found at the moment',
            ]);
        }
    }
}

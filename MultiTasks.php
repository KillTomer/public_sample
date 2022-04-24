<?php

namespace App\classes;

use App\Models\MultiTask;
use Illuminate\Database\Eloquent\Builder;

class MultiTasks
{

    /**
     * Create and start an unique task (task + detail).
     * @param string $task 
     * @param string $detail 
     * @return bool Return false if task is already in process or completed
     */
    public static function startTask($task, $detail)
    {
        // Don't create a task if the same one exists
        if (
           self::taskIsNotFree($task, $detail)
            || $task === null || $detail === null
        ) {
            return false;
        }
        
        $job = (Multitask::where('task', $task)->where('detail', $detail)->first()) ?? new MultiTask();
        $job->task = $task;
        $job->detail = $detail;
        $job->is_running = 1;
        $job->url = request()->url();
        try {
            // Prevent to display "Integrity constraint violation: 1062 Duplicate entry "
            return $job->save();
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Delete a single task or all tasks
     * @param string $task 
     * @param string|null $detail 
     * @return int Number of deleted items
     */
    public static function deleteTask($task, $detail = null)
    {
        if ($detail) {
            $job = MultiTask::where('task', $task)->where('detail', $detail);
        } else {
            $job = MultiTask::where('task', $task);
        }
        return $job->delete();
    }

    /**
     * Set a task to be completed and cancel is_running
     * @param string $task 
     * @param string $detail 
     * @return bool 
     */
    public static function completeTask($task, $detail)
    {
        $job = MultiTask::where('task', $task)->where('detail', $detail)->first();
        // dd($job);
        $job->is_running = false;
        $job->completed = true;
        return $job->save();
    }

    public static function getLatiestTaskDetail($task)
    {
        return MultiTask::toBase()->select('detail')->where('task', $task)->orderByDesc('id')->first()->detail ?? null;
    }

    public static function getCompletedOrRunning($task)
    {
        return MultiTask::where('task', $task)
            ->where(function (Builder $query) {
                return $query->where('is_running', 1)
                    ->orWhere('completed', 1);
            })
            ->get();
    }

    /**
     * Return true if task is not in process & it is not completed
     * @param string $task 
     * @param string $detail 
     * @return bool 
     */
    public static function isTaskFree($task, $detail)
    {
        return !self::taskIsNotFree($task, $detail);
    }

    /**
     * Return true if task is in process or it is completed
     * @param string $task 
     * @param string $detail 
     * @return bool 
     */
    public static function taskIsNotFree($task, $detail)
    {
        return MultiTask::where('task', $task)
            ->where('detail', $detail)
            ->where(function (Builder $query) {
                return $query->where('is_running', 1)
                    ->orWhere('completed', 1);
            })
            ->exists();
    }
}

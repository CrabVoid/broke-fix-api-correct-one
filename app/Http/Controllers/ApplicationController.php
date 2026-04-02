<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{

    public function index() {
        return Application::all();
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'internship_id' => 'required|exists:internships,id',
                'motivation_letter' => 'nullable|string|max:5000',
            ]);
        } catch (ValidationException $e) {
            ActivityLogger::log('application_create_failed', null, [
                'validation_errors' => $e->errors(),
                'input' => [
                    'user_id' => $request->input('user_id'),
                    'internship_id' => $request->input('internship_id'),
                ],
            ]);

            throw $e;
        }

        return DB::transaction(function () use ($validated) {
            $result = Application::createForUser(
                $validated['user_id'],
                $validated['internship_id'],
                $validated['motivation_letter'] ?? null
            );

            if (!$result['success']) {
                ActivityLogger::log('application_create_failed', null, [
                    'reason' => $result['message'],
                    'user_id' => $validated['user_id'],
                    'internship_id' => $validated['internship_id'],
                ]);

                return response()->json(['message' => $result['text']], $result['status']);
            }

            ActivityLogger::created($result['application'], [
                'user_id' => $validated['user_id'],
                'internship_id' => $validated['internship_id'],
            ]);

            return response()->json([
                'message' => 'Application created successfully',
                'data' => $result['application'],
            ], 201);
        });
    }

    public function storeWithProcedure(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'internship_id' => 'required|exists:internships,id',
                'motivation_letter' => 'nullable|string|max:5000',
            ]);
        } catch (ValidationException $e) {
            ActivityLogger::log('application_create_failed', null, [
                'validation_errors' => $e->errors(),
                'input' => [
                    'user_id' => $request->input('user_id'),
                    'internship_id' => $request->input('internship_id'),
                ],
                'method' => 'stored_procedure',
            ]);

            throw $e;
        }

        $result = DB::select(
            'CALL create_application(?, ?, ?, @success, @message, @app_id)',
            [
                $validated['user_id'],
                $validated['internship_id'],
                $validated['motivation_letter'] ?? null,
            ]
        );

        $statusResult = DB::select('SELECT @success as success, @message as message, @app_id as application_id');

        $success = (bool) $statusResult[0]->success;
        $applicationId = $statusResult[0]->application_id;

        if (!$success) {
            ActivityLogger::log('application_create_failed', null, [
                'reason' => $statusResult[0]->message,
                'user_id' => $validated['user_id'],
                'internship_id' => $validated['internship_id'],
                'method' => 'stored_procedure',
            ]);

            return response()->json([
                'message' => $statusResult[0]->message,
            ], 422);
        }

        $application = Application::find($applicationId);

        ActivityLogger::created($application, [
            'user_id' => $validated['user_id'],
            'internship_id' => $validated['internship_id'],
            'method' => 'stored_procedure',
        ]);

        return response()->json([
            'message' => 'Application created successfully',
            'data' => $application,
        ], 201);
    }
}

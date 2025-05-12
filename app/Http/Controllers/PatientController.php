<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Patient;

class PatientController extends Controller
{
    /**
     * @OA\Get(
     *     path="/patients",
     *     tags={"Patients"},
     *     summary="Get list of patients",
     *     @OA\Parameter(
     *         name="accessKey",
     *         in="header",
     *         required=true,
     *         description="Access key for authentication",
     *         @OA\Schema(type="string", example="")
     *     ),
     *     @OA\Parameter(
     *         name="Content-Type",
     *         in="header",
     *         required=true,
     *         description="Request content type",
     *         @OA\Schema(type="string", example="application/json")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of patients",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="medium_acquisition", type="string"),
     *             @OA\Property(property="created_at", type="string"),
     *             @OA\Property(property="updated_at", type="string"),
     *         ))
     *     )
     * )
     */
    public function index()
    {
        try {
            $patients = Patient::with('user')->get()->map(function ($patient) {
                return [
                    'id' => $patient->id,
                    'name' => $patient->user->name,
                    'medium_acquisition' => $patient->medium_acquisition,
                    'created_at' => $patient->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $patient->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json($patients);
        } catch (\Exception $e) {
            Log::error('Patient index error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to fetch patients. Please try again later.'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/patients",
     *     tags={"Patients"},
     *     summary="Create a new patient",
     *     @OA\Parameter(
     *         name="accessKey",
     *         in="header",
     *         required=true,
     *         description="Access key for authentication",
     *         @OA\Schema(type="string", example="")
     *     ),
     *     @OA\Parameter(
     *         name="Content-Type",
     *         in="header",
     *         required=true,
     *         description="Content Type",
     *         @OA\Schema(type="string", example="application/json")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "id_type", "id_no", "gender", "dob", "address", "medium_acquisition"},
     *             @OA\Property(property="name", type="string", example="Nico Robin"),
     *             @OA\Property(property="id_type", type="string", example="KTP"),
     *             @OA\Property(property="id_no", type="string", example="1234567890"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}),
     *             @OA\Property(property="dob", type="string", format="date", example="2000-01-01"),
     *             @OA\Property(property="address", type="string", example="Ohara"),
     *             @OA\Property(property="medium_acquisition", type="string", example="Online")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Patient created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|regex:/^[a-zA-Z.\s]+$/',
            'id_type' => 'required|string',
            'id_no' => 'required|string|unique:users,id_no',
            'gender' => 'required|in:male,female',
            'dob' => 'required|date|date_format:Y-m-d',
            'address' => 'required|string',
            'medium_acquisition' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $name = $request->name;
            $emailBase = Str::slug($name, '.');
            $counter = 1;

            do {
                $email = $emailBase . $counter . '@example.com';
                $exists = User::where('email', $email)->exists();
                if ($exists) {
                    $counter++;
                }
            } while ($exists);

            $user = User::create([
                'name' => $name,
                'id_type' => $request->id_type,
                'id_no' => $request->id_no,
                'gender' => $request->gender,
                'dob' => $request->dob,
                'address' => $request->address,
                'email' => $email,
                'password' => bcrypt('Physiomobile2025@!'),
            ]);

            $patient = Patient::create([
                'user_id' => $user->id,
                'medium_acquisition' => $request->medium_acquisition,
            ]);

            $patient->load('user');

            $response = [
                'id' => $patient->id,
                'user_id' => $patient->user_id,
                'medium_acquisition' => $patient->medium_acquisition,
                'created_at' => $patient->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $patient->updated_at->format('Y-m-d H:i:s'),
                'user' => [
                    'id' => $patient->user->id,
                    'name' => $patient->user->name,
                    'id_type' => $patient->user->id_type,
                    'id_no' => $patient->user->id_no,
                    'gender' => $patient->user->gender,
                    'dob' => $patient->user->dob,
                    'address' => $patient->user->address,
                    'email' => $patient->user->email,
                    'created_at' => $patient->user->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $patient->user->updated_at->format('Y-m-d H:i:s'),
                ]
            ];

            return response()->json($response, 201);

        } catch (\Exception $e) {
            Log::error('Patient store error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to create patient. Please try again later.'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/patients/{id}",
     *     tags={"Patients"},
     *     summary="Get a specific patient by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Patient ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="accessKey",
     *         in="header",
     *         required=true,
     *         description="Access key for authentication",
     *         @OA\Schema(type="string", example="")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Patient data"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Patient not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $patient = Patient::with('user')->find($id);
            if (!$patient) {
                return response()->json(['message' => 'Patient not found'], 404);
            }

            $response = [
                'id' => $patient->id,
                'user_id' => $patient->user_id,
                'medium_acquisition' => $patient->medium_acquisition,
                'created_at' => $patient->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $patient->updated_at->format('Y-m-d H:i:s'),
                'user' => [
                    'id' => $patient->user->id,
                    'name' => $patient->user->name,
                    'id_type' => $patient->user->id_type,
                    'id_no' => $patient->user->id_no,
                    'gender' => $patient->user->gender,
                    'dob' => $patient->user->dob,
                    'address' => $patient->user->address,
                    'email' => $patient->user->email,
                    'created_at' => $patient->user->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $patient->user->updated_at->format('Y-m-d H:i:s'),
                ]
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Patient show error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to fetch patient data. Please try again later.'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/patients/{id}",
     *     tags={"Patients"},
     *     summary="Update an existing patient",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Patient ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="accessKey",
     *         in="header",
     *         required=true,
     *         description="Access key for authentication",
     *         @OA\Schema(type="string", example="")
     *     ),
     *     @OA\Parameter(
     *         name="Content-Type",
     *         in="header",
     *         required=true,
     *         description="Content Type",
     *         @OA\Schema(type="string", example="application/json")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Nico Robin"),
     *             @OA\Property(property="id_type", type="string", example="SIM"),
     *             @OA\Property(property="id_no", type="string", example="9876543210"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}),
     *             @OA\Property(property="dob", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="address", type="string", example="Ohara"),
     *             @OA\Property(property="medium_acquisition", type="string", example="Referral")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Patient updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Patient not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $patient = Patient::with('user')->find($id);
            if (!$patient) {
                return response()->json(['message' => 'Patient not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|regex:/^[a-zA-Z.\s]+$/',
                'id_type' => 'sometimes|required|string',
                'id_no' => [
                    'sometimes',
                    'required',
                    'string',
                    Rule::unique('users', 'id_no')->ignore($patient->user_id),
                ],
                'gender' => 'sometimes|required|in:male,female',
                'dob' => 'sometimes|required|date|date_format:Y-m-d',
                'address' => 'sometimes|required|string',
                'medium_acquisition' => 'sometimes|required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Update Patient
            if ($request->filled('medium_acquisition')) {
                $patient->medium_acquisition = $request->medium_acquisition;
                $patient->save();
            }

            // Update User
            $userData = $request->only('name', 'id_type', 'id_no', 'gender', 'dob', 'address');
            if (!empty($userData)) {
                $patient->user->update($userData);
            }

            $patient->load('user');

            $response = [
                'id' => $patient->id,
                'user_id' => $patient->user_id,
                'medium_acquisition' => $patient->medium_acquisition,
                'created_at' => $patient->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $patient->updated_at->format('Y-m-d H:i:s'),
                'user' => [
                    'id' => $patient->user->id,
                    'name' => $patient->user->name,
                    'id_type' => $patient->user->id_type,
                    'id_no' => $patient->user->id_no,
                    'gender' => $patient->user->gender,
                    'dob' => $patient->user->dob,
                    'address' => $patient->user->address,
                    'email' => $patient->user->email,
                    'created_at' => $patient->user->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $patient->user->updated_at->format('Y-m-d H:i:s'),
                ]
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Patient update error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to update patient. Please try again later.'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/patients/{id}",
     *     tags={"Patients"},
     *     summary="Delete a patient by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Patient ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="accessKey",
     *         in="header",
     *         required=true,
     *         description="Access key for authentication",
     *         @OA\Schema(type="string", example="")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Patient deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Patient not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $patient = Patient::find($id);
            if (!$patient) {
                return response()->json(['message' => 'Patient not found'], 404);
            }

            // Hapus user terlebih dahulu, lalu patient
            $patient->user->delete();
            $patient->delete();

            return response()->json(['message' => 'Patient deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Patient destroy error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to delete patient. Please try again later.'
            ], 500);
        }
    }
}
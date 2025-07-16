<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FaceRecognitionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class FaceRecognitionController extends Controller
{
    protected $faceRecognitionService;

    public function __construct(FaceRecognitionService $faceRecognitionService)
    {
        $this->faceRecognitionService = $faceRecognitionService;
    }

    public function detectFace(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|string'
            ]);

            $imageData = $request->input('image');
            $userId = Auth::id();

            // Verifikasi wajah menggunakan histogram dan GAN
            $isMatch = $this->faceRecognitionService->verifyFace($imageData, $userId);

            return response()->json([
                'face_detected' => $isMatch,
                'message' => $isMatch ? 'Wajah terdeteksi' : 'Wajah tidak terdeteksi'
            ]);
        } catch (\Exception $e) {
            Log::error('Face detection error: ' . $e->getMessage());
            return response()->json([
                'face_detected' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function captureFace(Request $request)
    {
        try {
            Log::info('Menerima request capture face');

            $request->validate([
                'image' => 'required|string'
            ]);

            $imageData = $request->input('image');
            $userId = Auth::id();

            Log::info('User ID: ' . $userId);

            // Buat direktori jika belum ada
            $datasetPath = storage_path('app/public/face_dataset');
            if (!file_exists($datasetPath)) {
                mkdir($datasetPath, 0755, true);
            }

            // Simpan gambar sementara
            $tempImagePath = storage_path('app/temp_face.jpg');
            $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $imageData);
            $imageData = base64_decode($imageData);

            if ($imageData === false) {
                Log::error('Invalid image data');
                throw new \Exception('Invalid image data');
            }

            file_put_contents($tempImagePath, $imageData);
            Log::info('Gambar sementara disimpan di: ' . $tempImagePath);

            // Panggil script Python untuk menyimpan dataset
            $pythonScript = base_path('app/Services/FaceRecognitionService.py');
            $command = "python {$pythonScript} capture_dataset {$userId} {$tempImagePath} 2>&1";
            Log::info('Menjalankan command: ' . $command);

            exec($command, $output, $returnCode);
            Log::info('Command output: ' . implode("\n", $output));
            Log::info('Return code: ' . $returnCode);

            // Hapus file sementara
            if (file_exists($tempImagePath)) {
                unlink($tempImagePath);
            }

            if ($returnCode !== 0) {
                Log::error('Face capture failed', ['output' => $output]);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan data wajah: ' . implode("\n", $output)
                ]);
            }

            // Update face_data di database
            User::where('id', Auth::id())->update([
                'face_data' => json_encode([
                    'has_face_data' => true,
                    'last_updated' => now()
                ])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data wajah berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            Log::error('Face capture error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clearFaceData()
    {
        try {
            $userId = Auth::id();
            Log::info('Menghapus data wajah untuk user: ' . $userId);

            // Hapus file dataset
            $datasetPath = storage_path('app/public/face_dataset');
            $pattern = "{$datasetPath}/user_{$userId}_*.jpg";
            $files = glob($pattern);
            Log::info('Menemukan ' . count($files) . ' file dataset');

            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                    Log::info('Menghapus file: ' . $file);
                }
            }

            // Hapus file model
            $modelPath = storage_path('app/public/face_models');
            $modelFile = "{$modelPath}/user_{$userId}_model.json";
            if (file_exists($modelFile)) {
                unlink($modelFile);
                Log::info('Menghapus file model: ' . $modelFile);
            }

            // Update face_data di database
            User::where('id', Auth::id())->update([
                'face_data' => null
            ]);
            Log::info('Data wajah di database dihapus');

            return response()->json([
                'success' => true,
                'message' => 'Data wajah berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            Log::error('Clear face data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}

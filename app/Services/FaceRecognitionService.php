<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FaceRecognitionService
{
    protected $pythonScript;
    protected $datasetPath;
    protected $modelPath;

    public function __construct()
    {
        $this->pythonScript = base_path('app/Services/FaceRecognitionService.py');
        $this->datasetPath = storage_path('app/public/face_dataset');
        $this->modelPath = storage_path('app/public/face_models');

        // Buat direktori jika belum ada
        if (!file_exists($this->datasetPath)) {
            mkdir($this->datasetPath, 0755, true);
        }
        if (!file_exists($this->modelPath)) {
            mkdir($this->modelPath, 0755, true);
        }
    }

    public function captureDataset($userId)
    {
        try {
            $command = "python {$this->pythonScript} capture_dataset {$userId}";
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                Log::error('Face dataset capture failed', ['output' => $output]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Face dataset capture error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function verifyFace($userId, $imageData = null)
    {
        try {
            // Jika tidak ada imageData, ambil dari webcam
            if (!$imageData) {
                $imageData = $this->captureFromWebcam();
            }

            // Simpan gambar sementara
            $tempImagePath = storage_path('app/temp/verify_' . time() . '.jpg');
            file_put_contents($tempImagePath, $imageData);

            // Jalankan script Python untuk verifikasi
            $command = sprintf(
                'python %s/app/Services/FaceRecognitionService.py verify_face %d %s',
                base_path(),
                $userId,
                $tempImagePath
            );

            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            // Hapus file temporary
            unlink($tempImagePath);

            if ($returnVar !== 0) {
                Log::error('Face verification failed', [
                    'user_id' => $userId,
                    'output' => $output
                ]);
                return [
                    'success' => false,
                    'message' => 'Gagal melakukan verifikasi wajah'
                ];
            }

            $result = json_decode($output[0] ?? '{}', true);
            return [
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? 'Verifikasi gagal'
            ];

        } catch (\Exception $e) {
            Log::error('Face verification error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ];
        }
    }

    protected function captureFromWebcam()
    {
        // Implementasi capture dari webcam menggunakan JavaScript
        // Data akan dikirim dari frontend
        return null;
    }

    public function getDatasetCount($userId)
    {
        $pattern = $this->datasetPath . "/user_{$userId}_*.jpg";
        return count(glob($pattern));
    }

    public function clearDataset($userId)
    {
        try {
            // Hapus semua gambar dataset
            $pattern = $this->datasetPath . "/user_{$userId}_*.jpg";
            array_map('unlink', glob($pattern));

            // Hapus model file
            $modelFile = $this->modelPath . "/user_{$userId}_model.json";
            if (file_exists($modelFile)) {
                unlink($modelFile);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Clear dataset error', ['error' => $e->getMessage()]);
            return false;
        }
    }
}

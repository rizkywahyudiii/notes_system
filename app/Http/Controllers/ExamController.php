<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Answer;
use App\Services\FaceRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExamController extends Controller
{
    protected $faceRecognitionService;

    public function __construct(FaceRecognitionService $faceRecognitionService)
    {
        $this->faceRecognitionService = $faceRecognitionService;
    }

    public function index()
    {
        $exams = Exam::where('is_active', true)
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->get();

        return view('exams.index', compact('exams'));
    }

    public function show(Exam $exam)
    {
        // Cek apakah user sudah memiliki sesi aktif
        $activeSession = ExamSession::where('user_id', Auth::id())
            ->where('exam_id', $exam->id)
            ->where('status', 'ongoing')
            ->first();

        if ($activeSession) {
            return redirect()->route('exams.take', $activeSession);
        }

        return view('exams.show', compact('exam'));
    }

    public function start(Exam $exam)
    {
        // Verifikasi wajah sebelum memulai ujian
        $verificationResult = $this->faceRecognitionService->verifyFace(Auth::id());

        if (!$verificationResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Verifikasi wajah gagal: ' . $verificationResult['message']
            ]);
        }

        // Buat sesi ujian baru
        $session = ExamSession::create([
            'user_id' => Auth::id(),
            'exam_id' => $exam->id,
            'start_time' => now(),
            'status' => 'ongoing',
            'face_verification_logs' => [
                [
                    'timestamp' => now(),
                    'status' => 'success',
                    'message' => 'Verifikasi wajah berhasil'
                ]
            ]
        ]);

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'redirect_url' => route('exams.take', $session)
        ]);
    }

    public function take(ExamSession $session)
    {
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        if ($session->status !== 'ongoing') {
            return redirect()->route('exams.index')
                ->with('error', 'Sesi ujian sudah berakhir');
        }

        $exam = $session->exam;
        $questions = $exam->questions;
        $answers = $session->answers->pluck('answer_text', 'question_id');

        return view('exams.take', compact('session', 'exam', 'questions', 'answers'));
    }

    public function submitAnswer(Request $request, ExamSession $session)
    {
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer_text' => 'required|string'
        ]);

        // Verifikasi wajah saat menjawab
        $verificationResult = $this->faceRecognitionService->verifyFace(Auth::id());

        if (!$verificationResult['success']) {
            // Log aktivitas mencurigakan
            $suspiciousActivities = $session->suspicious_activities ?? [];
            $suspiciousActivities[] = [
                'timestamp' => now(),
                'type' => 'face_verification_failed',
                'message' => $verificationResult['message']
            ];

            $session->update([
                'suspicious_activities' => $suspiciousActivities
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verifikasi wajah gagal: ' . $verificationResult['message']
            ]);
        }

        // Simpan jawaban
        Answer::updateOrCreate(
            [
                'exam_session_id' => $session->id,
                'question_id' => $request->question_id
            ],
            [
                'answer_text' => $request->answer_text
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Jawaban berhasil disimpan'
        ]);
    }

    public function finish(ExamSession $session)
    {
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $session->update([
            'end_time' => now(),
            'status' => 'completed'
        ]);

        return redirect()->route('exams.index')
            ->with('success', 'Ujian berhasil diselesaikan');
    }

    public function verifyFace(Request $request, ExamSession $session)
    {
        if ($session->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'image' => 'required|string'
        ]);

        // Verifikasi wajah
        $verificationResult = $this->faceRecognitionService->verifyFace(
            Auth::id(),
            base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->image))
        );

        // Log hasil verifikasi
        $faceLogs = $session->face_verification_logs ?? [];
        $faceLogs[] = [
            'timestamp' => now(),
            'status' => $verificationResult['success'] ? 'success' : 'failed',
            'message' => $verificationResult['message']
        ];

        $session->update([
            'face_verification_logs' => $faceLogs
        ]);

        return response()->json($verificationResult);
    }
}

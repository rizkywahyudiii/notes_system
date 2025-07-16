<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\FaceRecognitionService;

class NoteController extends Controller
{
    protected $faceRecognitionService;

    public function __construct(FaceRecognitionService $faceRecognitionService)
    {
        $this->faceRecognitionService = $faceRecognitionService;
    }

    public function index()
    {
        $notes = Note::where('user_id', Auth::id())->latest()->get();
        return view('dashboard', compact('notes'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
            ]);

            $data = [
                'user_id' => Auth::id(),
                'title' => $validated['title'],
                'content' => $validated['content'],
                'is_locked' => false,
                'lock_type' => null,
            ];

            // Handle attachment jika ada
            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('attachments', 'public');
                $data['attachment_path'] = $path;
            }

            $note = Note::create($data);

            return redirect()->route('dashboard')->with('success', 'Catatan berhasil dibuat!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal membuat catatan: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Note $note)
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        return view('notes.show', compact('note'));
    }

    public function update(Request $request, Note $note)
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
            ]);

            $data = [
                'title' => $validated['title'],
                'content' => $validated['content'],
            ];

            if ($request->hasFile('attachment')) {
                if ($note->attachment_path) {
                    Storage::disk('public')->delete($note->attachment_path);
                }
                $path = $request->file('attachment')->store('attachments', 'public');
                $data['attachment_path'] = $path;
            }

            $note->update($data);

            return redirect()->route('notes.show', $note)->with('success', 'Catatan berhasil diperbarui!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal memperbarui catatan: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(Note $note)
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        if ($note->attachment_path) {
            Storage::disk('public')->delete($note->attachment_path);
        }

        $note->delete();

        return redirect()->route('dashboard')->with('success', 'Catatan berhasil dihapus!');
    }

    public function lock(Request $request, Note $note)
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $validated = $request->validate([
                'lock_type' => 'required|in:pin,face',
                'pin' => 'required_if:lock_type,pin|nullable|string|min:4|max:6',
            ]);

            $data = [
                'is_locked' => true,
                'lock_type' => $validated['lock_type'],
            ];

            if ($validated['lock_type'] === 'pin') {
                $data['pin'] = bcrypt($validated['pin']);
            }

            $note->update($data);

            return redirect()->route('notes.show', $note)->with('success', 'Catatan berhasil dikunci!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal mengunci catatan: ' . $e->getMessage()])->withInput();
        }
    }

    public function unlock(Request $request, Note $note)
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            if ($note->lock_type === 'pin') {
                $validated = $request->validate([
                    'pin' => 'required|string',
                ]);

                if (!password_verify($validated['pin'], $note->pin)) {
                    return back()->withErrors(['error' => 'PIN yang Anda masukkan salah.']);
                }
            } else {
                $validated = $request->validate([
                    'face_data' => 'required|string',
                ]);

                if (!$this->faceRecognitionService->verifyFace($validated['face_data'], Auth::id())) {
                    return back()->withErrors(['error' => 'Verifikasi wajah gagal.']);
                }
            }

            $note->update([
                'is_locked' => false,
                'lock_type' => null,
                'pin' => null,
            ]);

            return redirect()->route('notes.show', $note)->with('success', 'Catatan berhasil dibuka!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal membuka kunci catatan: ' . $e->getMessage()])->withInput();
        }
    }
}

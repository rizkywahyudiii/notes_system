@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>{{ $exam->title }}</h4>
                    <div class="text-muted">
                        Waktu tersisa: <span id="timer"></span>
                    </div>
                </div>
                <div class="card-body">
                    <form id="examForm">
                        @foreach($questions as $index => $question)
                        <div class="question-container mb-4" id="question-{{ $question->id }}">
                            <h5>Pertanyaan {{ $index + 1 }}</h5>
                            <p>{{ $question->question_text }}</p>

                            @if($question->question_type === 'multiple_choice')
                                @foreach($question->options as $option)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio"
                                           name="answer[{{ $question->id }}]"
                                           value="{{ $option }}"
                                           {{ isset($answers[$question->id]) && $answers[$question->id] === $option ? 'checked' : '' }}>
                                    <label class="form-check-label">
                                        {{ $option }}
                                    </label>
                                </div>
                                @endforeach
                            @else
                                <textarea class="form-control"
                                          name="answer[{{ $question->id }}]"
                                          rows="3">{{ $answers[$question->id] ?? '' }}</textarea>
                            @endif
                        </div>
                        @endforeach

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary">Selesai</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Verifikasi Wajah</h5>
                </div>
                <div class="card-body">
                    <video id="webcam" width="100%" autoplay></video>
                    <canvas id="canvas" style="display: none;"></canvas>
                    <div id="verificationStatus" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let webcamStream = null;
let verificationInterval = null;

// Inisialisasi webcam
async function initWebcam() {
    try {
        const video = document.getElementById('webcam');
        webcamStream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: 640,
                height: 480,
                facingMode: 'user'
            }
        });
        video.srcObject = webcamStream;
    } catch (err) {
        console.error('Error accessing webcam:', err);
        alert('Tidak dapat mengakses kamera. Pastikan kamera sudah diizinkan.');
    }
}

// Capture dan verifikasi wajah
async function captureAndVerify() {
    const video = document.getElementById('webcam');
    const canvas = document.getElementById('canvas');
    const context = canvas.getContext('2d');

    // Set canvas size
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    // Capture frame
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageData = canvas.toDataURL('image/jpeg');

    try {
        const response = await fetch(`/api/exams/{{ $session->id }}/verify`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ image: imageData })
        });

        const result = await response.json();
        const statusDiv = document.getElementById('verificationStatus');

        if (result.success) {
            statusDiv.innerHTML = '<div class="alert alert-success">Verifikasi berhasil</div>';
        } else {
            statusDiv.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    } catch (err) {
        console.error('Error verifying face:', err);
    }
}

// Handle form submission
document.getElementById('examForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const answers = {};

    for (let [key, value] of formData.entries()) {
        if (key.startsWith('answer[')) {
            const questionId = key.match(/\[(\d+)\]/)[1];
            answers[questionId] = value;
        }
    }

    try {
        const response = await fetch(`/api/exams/{{ $session->id }}/submit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ answers })
        });

        const result = await response.json();
        if (result.success) {
            window.location.href = '/exams';
        } else {
            alert(result.message);
        }
    } catch (err) {
        console.error('Error submitting answers:', err);
        alert('Terjadi kesalahan saat mengirim jawaban');
    }
});

// Timer
function updateTimer() {
    const startTime = new Date('{{ $session->start_time }}').getTime();
    const duration = {{ $exam->duration_minutes }} * 60 * 1000;
    const endTime = startTime + duration;

    function update() {
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance < 0) {
            clearInterval(timerInterval);
            document.getElementById('timer').innerHTML = 'Waktu habis!';
            document.getElementById('examForm').submit();
            return;
        }

        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        document.getElementById('timer').innerHTML =
            minutes.toString().padStart(2, '0') + ':' +
            seconds.toString().padStart(2, '0');
    }

    update();
    const timerInterval = setInterval(update, 1000);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initWebcam();
    updateTimer();

    // Verifikasi wajah setiap 30 detik
    verificationInterval = setInterval(captureAndVerify, 30000);
});

// Cleanup
window.addEventListener('beforeunload', function() {
    if (webcamStream) {
        webcamStream.getTracks().forEach(track => track.stop());
    }
    if (verificationInterval) {
        clearInterval(verificationInterval);
    }
});
</script>
@endpush
@endsection

@extends('layouts.dashboard')

@section('header')
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-semibold text-gray-900">Detail Catatan</h1>
        @if(!$note->is_locked)
            <div class="flex space-x-2">
                <button onclick="openEditModal()" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600">
                    <i class="fas fa-edit mr-2"></i>Edit
                </button>
                <form action="{{ route('notes.destroy', $note) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600" onclick="return confirm('Apakah Anda yakin ingin menghapus catatan ini?')">
                        <i class="fas fa-trash mr-2"></i>Hapus
                    </button>
                </form>
            </div>
        @endif
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-2">{{ $note->title }}</h2>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">{{ $note->created_at->format('d M Y H:i') }}</span>
                @if($note->is_locked)
                    <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $note->lock_type === 'pin' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                        <i class="fas {{ $note->lock_type === 'pin' ? 'fa-lock' : 'fa-camera' }} mr-1"></i>
                        {{ $note->lock_type === 'pin' ? 'Terkunci (PIN)' : 'Terkunci (Face)' }}
                    </span>
                @endif
            </div>
        </div>

        @if($note->is_locked)
            <div class="text-center py-8">
                <i class="fas fa-lock text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-500 mb-4">Catatan ini terkunci</p>
                <button onclick="openUnlockModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    <i class="fas fa-key mr-2"></i>Buka Kunci
                </button>
            </div>
        @else
            <div class="prose max-w-none">
                {!! nl2br(e($note->content)) !!}
            </div>

            @if($note->attachment_path)
                <div class="mt-6">
                    <h3 class="text-lg font-medium mb-2">Lampiran</h3>
                    <a href="{{ Storage::url($note->attachment_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800">
                        <i class="fas fa-paperclip mr-2"></i>Lihat Lampiran
                    </a>
                </div>
            @endif

            <div class="mt-6">
                <button onclick="openLockModal()" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-lock mr-2"></i>Kunci Catatan
                </button>
            </div>
        @endif
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-3/4 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Edit Catatan</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form action="{{ route('notes.update', $note) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Judul</label>
                        <input type="text" name="title" id="title" value="{{ $note->title }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="content" class="block text-sm font-medium text-gray-700">Isi Catatan</label>
                        <textarea name="content" id="content" rows="6" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $note->content }}</textarea>
                    </div>

                    <div>
                        <label for="attachment" class="block text-sm font-medium text-gray-700">Lampiran (Opsional)</label>
                        <input type="file" name="attachment" id="attachment"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        @if($note->attachment_path)
                            <p class="mt-2 text-sm text-gray-500">Lampiran saat ini: {{ basename($note->attachment_path) }}</p>
                        @endif
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Kunci -->
    <div id="lockModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-1/2 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Kunci Catatan</h3>
                <button onclick="closeLockModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form action="{{ route('notes.lock', $note) }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Metode Kunci</label>
                        <select name="lock_type" id="lock_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="pin">PIN</option>
                            <option value="face">Face Recognition</option>
                        </select>
                    </div>

                    <div id="pinInput">
                        <label for="pin" class="block text-sm font-medium text-gray-700">PIN (4-6 digit)</label>
                        <input type="password" name="pin" id="pin" pattern="[0-9]{4,6}" maxlength="6"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeLockModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Kunci Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Buka Kunci -->
    <div id="unlockModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-1/3 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Buka Kunci Catatan</h3>
                <button onclick="closeUnlockModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form action="{{ route('notes.unlock', $note) }}" method="POST" id="unlockForm">
                @csrf
                @if($note->lock_type === 'pin')
                    <div class="space-y-4">
                        <div>
                            <label for="pin" class="block text-sm font-medium text-gray-700">Masukkan PIN</label>
                            <input type="password" name="pin" id="pin" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                @else
                    <div class="text-center">
                        <div class="relative w-64 h-64 mx-auto mb-4">
                            <video id="video" class="w-full h-full rounded-lg object-cover" autoplay></video>
                            <canvas id="canvas" class="hidden"></canvas>
                            <div id="faceOverlay" class="absolute inset-0 border-2 border-green-500 rounded-lg hidden"></div>
                        </div>
                        <input type="hidden" name="face_data" id="face_data">
                        <p id="faceStatus" class="text-sm text-gray-500 mb-4">Mendeteksi wajah...</p>
                    </div>
                @endif

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeUnlockModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Batal
                    </button>
                    <button type="submit" id="unlockButton"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Buka Kunci
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fungsi untuk modal edit
        function openEditModal() {
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Fungsi untuk modal kunci
        function openLockModal() {
            document.getElementById('lockModal').classList.remove('hidden');
        }

        function closeLockModal() {
            document.getElementById('lockModal').classList.add('hidden');
        }

        // Fungsi untuk modal buka kunci
        function openUnlockModal() {
            document.getElementById('unlockModal').classList.remove('hidden');
            if (document.getElementById('video')) {
                startVideo();
            }
        }

        function closeUnlockModal() {
            document.getElementById('unlockModal').classList.add('hidden');
            if (document.getElementById('video')) {
                stopVideo();
            }
        }

        // Fungsi untuk kamera dan face recognition
        let faceDetectionInterval;
        let isFaceDetected = false;

        function startVideo() {
            const video = document.getElementById('video');
            const faceOverlay = document.getElementById('faceOverlay');
            const faceStatus = document.getElementById('faceStatus');
            const unlockButton = document.getElementById('unlockButton');
            const unlockForm = document.getElementById('unlockForm');

            navigator.mediaDevices.getUserMedia({ video: true })
                .then(stream => {
                    video.srcObject = stream;

                    // Mulai deteksi wajah otomatis
                    faceDetectionInterval = setInterval(() => {
                        const canvas = document.getElementById('canvas');
                        const context = canvas.getContext('2d');

                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        context.drawImage(video, 0, 0, canvas.width, canvas.height);

                        // Kirim gambar ke server untuk deteksi wajah
                        const imageData = canvas.toDataURL('image/jpeg');

                        // Simulasi deteksi wajah (ganti dengan API call ke server)
                        fetch('/api/detect-face', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ image: imageData })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.face_detected) {
                                faceOverlay.classList.remove('hidden');
                                faceOverlay.classList.remove('border-red-500');
                                faceOverlay.classList.add('border-green-500');
                                faceStatus.textContent = 'Wajah terdeteksi!';
                                faceStatus.classList.remove('text-red-500');
                                faceStatus.classList.add('text-green-500');

                                // Simpan data wajah dan submit form
                                document.getElementById('face_data').value = imageData;
                                if (!isFaceDetected) {
                                    isFaceDetected = true;
                                    unlockForm.submit();
                                }
                            } else {
                                faceOverlay.classList.remove('hidden');
                                faceOverlay.classList.remove('border-green-500');
                                faceOverlay.classList.add('border-red-500');
                                faceStatus.textContent = 'Wajah tidak terdeteksi';
                                faceStatus.classList.remove('text-green-500');
                                faceStatus.classList.add('text-red-500');
                                isFaceDetected = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            faceStatus.textContent = 'Error: ' + error.message;
                            faceStatus.classList.add('text-red-500');
                        });
                    }, 1000); // Cek setiap 1 detik
                })
                .catch(err => {
                    console.error("Error accessing camera:", err);
                    faceStatus.textContent = 'Error: Tidak dapat mengakses kamera';
                    faceStatus.classList.add('text-red-500');
                });
        }

        function stopVideo() {
            const video = document.getElementById('video');
            const stream = video.srcObject;
            if (stream) {
                const tracks = stream.getTracks();
                tracks.forEach(track => track.stop());
                video.srcObject = null;
            }
            if (faceDetectionInterval) {
                clearInterval(faceDetectionInterval);
            }
            isFaceDetected = false;
        }

        // Toggle PIN input berdasarkan metode kunci
        document.getElementById('lock_type').addEventListener('change', function() {
            const pinInput = document.getElementById('pinInput');
            pinInput.style.display = this.value === 'pin' ? 'block' : 'none';
        });
    </script>
@endsection

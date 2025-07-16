@extends('layouts.dashboard')

@section('header')
    Profil
@endsection

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="max-w-3xl mx-auto">
            <!-- Informasi Profil -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Informasi Profil</h3>
                </div>
                <div class="border-t border-gray-200">
                    <dl>
                        <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Nama Lengkap</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ auth()->user()->name }}</dd>
                        </div>
                        <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Username</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ auth()->user()->username }}</dd>
                        </div>
                        <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ auth()->user()->email }}</dd>
                        </div>
                        <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Jenis Kelamin</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ auth()->user()->gender }}</dd>
                        </div>
                        <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Tanggal Lahir</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ auth()->user()->birth_date->format('d F Y') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Pengaturan Keamanan -->
            <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Pengaturan Keamanan</h3>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                    <!-- PIN -->
                    <div class="mb-8">
                        <h4 class="text-md font-medium text-gray-900 mb-4">Pengaturan PIN</h4>
                        <form action="{{ route('update.pin') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label for="pin" class="block text-sm font-medium text-gray-700">PIN Baru (4-6 digit)</label>
                                <input type="password" name="pin" id="pin" pattern="[0-9]{4,6}" maxlength="6" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Simpan PIN
                            </button>
                        </form>
                    </div>

                    <!-- Face Recognition -->
                    <div>
                        <h4 class="text-md font-medium text-gray-900 mb-4">Pengaturan Face Recognition</h4>
                        <div class="flex items-center space-x-4">
                            <button type="button" onclick="openFaceModal()" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <i class="fas fa-camera mr-2"></i> Daftarkan Wajah
                            </button>
                            <button type="button" onclick="clearFaceData()" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <i class="fas fa-trash mr-2"></i> Hapus Data Wajah
                            </button>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Face recognition digunakan untuk login dan membuka kunci catatan yang dilindungi.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tombol Hapus Akun -->
            <div class="mt-8">
                <form action="{{ route('profile.destroy') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun? Tindakan ini tidak dapat dibatalkan.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <i class="fas fa-trash mr-2"></i> Hapus Akun
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Face Recognition -->
<div id="faceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Daftarkan Wajah</h3>
            <div class="mt-2 px-7 py-3">
                <div class="relative">
                    <video id="video" width="100%" height="auto" class="rounded-lg" autoplay playsinline></video>
                    <canvas id="canvas" class="hidden"></canvas>
                    <div id="faceOverlay" class="absolute inset-0 border-2 border-green-500 rounded-lg hidden"></div>
                </div>
                <p id="faceStatus" class="mt-2 text-sm text-gray-500">Memulai kamera...</p>
                <div class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div id="progressBar" class="bg-green-600 h-2.5 rounded-full" style="width: 0%"></div>
                    </div>
                    <p id="progressText" class="mt-1 text-sm text-gray-500">0/50 gambar</p>
                </div>
                <!-- Preview Area -->
                <div class="mt-4 grid grid-cols-5 gap-2" id="previewArea">
                    <!-- Preview images will be added here -->
                </div>
            </div>
            <div class="items-center px-4 py-3">
                <button id="startCaptureBtn" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-300">
                    Mulai Pengambilan
                </button>
                <button onclick="closeFaceModal()" class="mt-2 px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let stream = null;
let isCapturing = false;
let captureCount = 0;
const totalCaptures = 50;

function openFaceModal() {
    document.getElementById('faceModal').classList.remove('hidden');
    startCamera();
}

function closeFaceModal() {
    document.getElementById('faceModal').classList.add('hidden');
    stopCamera();
    resetCapture();
}

async function startCamera() {
    try {
        const constraints = {
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: "user"
            }
        };

        stream = await navigator.mediaDevices.getUserMedia(constraints);
        const video = document.getElementById('video');
        video.srcObject = stream;

        // Tunggu video siap
        await new Promise((resolve) => {
            video.onloadedmetadata = () => {
                resolve();
            };
        });

        video.play();
        document.getElementById('faceStatus').textContent = 'Kamera siap';
    } catch (err) {
        console.error('Error accessing camera:', err);
        document.getElementById('faceStatus').textContent = 'Error: Tidak dapat mengakses kamera';
        document.getElementById('faceStatus').classList.add('text-red-500');
    }
}

function stopCamera() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
}

function resetCapture() {
    isCapturing = false;
    captureCount = 0;
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressText').textContent = '0/50 gambar';
    document.getElementById('startCaptureBtn').textContent = 'Mulai Pengambilan';
    document.getElementById('faceStatus').textContent = 'Memulai kamera...';
    document.getElementById('previewArea').innerHTML = '';
}

function addPreviewImage(imageData) {
    const previewArea = document.getElementById('previewArea');
    const img = document.createElement('img');
    img.src = imageData;
    img.className = 'w-full h-16 object-cover rounded';
    previewArea.appendChild(img);

    // Batasi jumlah preview menjadi 5
    if (previewArea.children.length > 5) {
        previewArea.removeChild(previewArea.firstChild);
    }
}

function startCapture() {
    if (!isCapturing) {
        isCapturing = true;
        document.getElementById('startCaptureBtn').textContent = 'Mengambil Gambar...';
        captureFaces();
    }
}

async function captureFaces() {
    if (!isCapturing || captureCount >= totalCaptures) {
        if (captureCount >= totalCaptures) {
            document.getElementById('faceStatus').textContent = 'Pengambilan selesai!';
            document.getElementById('faceStatus').classList.add('text-green-500');
            isCapturing = false;
            document.getElementById('startCaptureBtn').textContent = 'Selesai';
        }
        return;
    }

    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    // Pastikan video sudah siap
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        // Tambahkan brightness dan contrast
        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;

        // Adjust brightness and contrast
        for (let i = 0; i < data.length; i += 4) {
            // Brightness
            data[i] = Math.min(255, data[i] * 1.2);     // Red
            data[i + 1] = Math.min(255, data[i + 1] * 1.2); // Green
            data[i + 2] = Math.min(255, data[i + 2] * 1.2); // Blue
        }

        context.putImageData(imageData, 0, 0);

        const imageDataUrl = canvas.toDataURL('image/jpeg', 0.8);

        // Tambahkan preview
        addPreviewImage(imageDataUrl);

        try {
            console.log('Mengirim request ke server...');
            const response = await fetch('{{ route("api.capture-face") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ image: imageDataUrl })
            });

            console.log('Response status:', response.status);
            const responseText = await response.text();
            console.log('Response text:', responseText);

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('Error parsing JSON:', e);
                throw new Error('Invalid JSON response from server');
            }

            if (data.success) {
                captureCount++;
                const progress = (captureCount / totalCaptures) * 100;
                document.getElementById('progressBar').style.width = `${progress}%`;
                document.getElementById('progressText').textContent = `${captureCount}/${totalCaptures} gambar`;

                if (captureCount < totalCaptures) {
                    setTimeout(captureFaces, 100);
                } else {
                    document.getElementById('faceStatus').textContent = 'Pengambilan selesai!';
                    document.getElementById('faceStatus').classList.add('text-green-500');
                    isCapturing = false;
                    document.getElementById('startCaptureBtn').textContent = 'Selesai';
                }
            } else {
                throw new Error(data.message || 'Gagal menyimpan data wajah');
            }
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('faceStatus').textContent = 'Error: ' + error.message;
            document.getElementById('faceStatus').classList.add('text-red-500');
            isCapturing = false;
        }
    } else {
        // Jika video belum siap, coba lagi setelah 100ms
        setTimeout(captureFaces, 100);
    }
}

function clearFaceData() {
    if (confirm('Apakah Anda yakin ingin menghapus data wajah? Anda perlu mendaftarkan ulang untuk menggunakan face recognition.')) {
        fetch('{{ route("api.clear-face-data") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Data wajah berhasil dihapus');
            } else {
                alert('Gagal menghapus data wajah: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus data wajah');
        });
    }
}

document.getElementById('startCaptureBtn').addEventListener('click', startCapture);
</script>
@endpush
@endsection

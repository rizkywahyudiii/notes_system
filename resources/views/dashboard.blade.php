@extends('layouts.dashboard')

@section('header')
    Dashboard
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Catatan Saya</h1>
        <button onclick="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
            <i class="fas fa-plus mr-2"></i>Tambah Catatan
        </button>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($notes as $note)
            <a href="{{ route('notes.show', $note) }}" class="block">
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-200">
                    <div class="flex justify-between items-start mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">{{ $note->title }}</h2>
                        @if($note->is_locked)
                            <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $note->lock_type === 'pin' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                                <i class="fas {{ $note->lock_type === 'pin' ? 'fa-lock' : 'fa-camera' }} mr-1"></i>
                                {{ $note->lock_type === 'pin' ? 'PIN' : 'Face' }}
                            </span>
                        @endif
                    </div>
                    <p class="text-gray-600 mb-4 line-clamp-3">
                        @if($note->is_locked)
                            <span class="text-gray-400 italic">Catatan terkunci</span>
                        @else
                            {{ $note->content }}
                        @endif
                    </p>
                    <div class="flex justify-between items-center text-sm text-gray-500">
                        <span>{{ $note->created_at->format('d M Y H:i') }}</span>
                        @if($note->attachment_path)
                            <span class="text-indigo-600">
                                <i class="fas fa-paperclip mr-1"></i>Lampiran
                            </span>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-12">
                <i class="fas fa-sticky-note text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-500">Belum ada catatan. Mulai buat catatan baru!</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Modal Tambah Catatan -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-3/4 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Tambah Catatan Baru</h3>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('notes.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Judul</label>
                    <input type="text" name="title" id="title" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700">Isi Catatan</label>
                    <textarea name="content" id="content" rows="6" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <div>
                    <label for="attachment" class="block text-sm font-medium text-gray-700">Lampiran (Opsional)</label>
                    <input type="file" name="attachment" id="attachment"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeAddModal()"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Batal
                </button>
                <button type="submit"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }
</script>
@endsection

@extends('layouts.dashboard')
@section('header', $headerTitle ?? 'Текст нууцлах')

@section('content')
<div class="space-y-6">
    @if(session('error'))
        <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h1 class="text-lg font-semibold text-slate-800 mb-4">Текст нууцлах (DOCX)</h1>

        <form method="POST" action="{{ $actionUrl }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">DOCX файл оруулах</label>
                <input type="file" name="file" accept=".docx"
                       class="block w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                @error('file')
                    <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Нууцлах үг/өгүүлбэрүүд (1 мөрөнд 1)</label>
                <textarea name="phrases" rows="6"
                          placeholder="Жишээ:\nБат-Эрдэнэ\nСүхбаатар дүүрэг\nАБ99112233\n..."
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500">{{ old('phrases') }}</textarea>
                @error('phrases')
                    <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                @enderror
                <div class="text-xs text-slate-500 mt-1">Тухайн текст нь файл дотор таарвал бүхэлд нь масклагдана.</div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="auto_phone" value="1" class="rounded border-slate-300" @checked(old('auto_phone'))>
                    Утасны дугаар нууцлах
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="auto_register" value="1" class="rounded border-slate-300" @checked(old('auto_register'))>
                    Регистрийн дугаар нууцлах
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="auto_plate" value="1" class="rounded border-slate-300" @checked(old('auto_plate'))>
                    Машины дугаар нууцлах
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="auto_initial_name" value="1" class="rounded border-slate-300" @checked(old('auto_initial_name'))>
                    Нэр нууцлах 
                    
                </label>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-slate-700">Нууцлах тэмдэг:</label>
                    <input type="text" name="mask_char" value="{{ old('mask_char','*') }}" maxlength="1"
                           class="w-16 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                    @error('mask_char')
                        <div class="text-xs text-red-600">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" name="action" value="preview"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    Нууцлах
                </button>
                <button type="submit" name="action" value="download"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 transition-colors">
                   Шууд Татах
                </button>
            </div>

            <div class="text-xs text-slate-500">
                Анхаарах: Word дотор нэг өгүүлбэр олон хэсэг (`run`) болж тасарсан бол зарим тохиолдолд яг тааруулж масклахгүй үлдэх боломжтой. Ийм үед үгийг богино хэсгүүдээр (эсвэл хэд хэдэн мөрөөр) оруулж масклаж болно.
            </div>
        </form>
    </div>
</div>
@endsection


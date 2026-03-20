@php
    $initial = $initial ?? collect();
    $nameKey = $nameKey ?? 'names';
    $label = $label ?? 'Нэр';
    $buttonLabel = $buttonLabel ?? 'Нэмэх';
@endphp

<div
    class="flex flex-wrap items-end gap-3"
    x-data="{
        items: @js($initial->all()),
        newName: '',
        addFromInput() {
            const name = (this.newName || '').trim();
            if (!name) return;
            if (this.items.some(d => (d && d.name) === name)) { this.newName = ''; return; }
            this.items.push({ name, registry: '' });
            this.newName = '';
        },
        removeItem(index) { this.items.splice(index, 1); },
    }"
>
    <div class="flex-1 min-w-[200px]">
        <label class="text-sm font-semibold text-gray-700">{{ $label }}</label>
        <div class="mt-1 rounded-md border border-gray-300 bg-white">
            <div class="flex items-center gap-2 px-2 py-1.5 min-h-[2.5rem] flex-wrap">
                <template x-for="(d, i) in items" :key="i">
                    <span class="inline-flex items-center gap-1.5 bg-gray-100 border border-gray-300 rounded-lg px-2.5 py-1 text-sm">
                        <span x-text="d.name"></span>
                        <button type="button" @click="removeItem(i)" class="text-gray-500 hover:text-red-600 leading-none">&times;</button>
                    </span>
                </template>
                <input type="text"
                       x-model="newName"
                       @keydown.enter.prevent="addFromInput()"
                       placeholder="Нэр оруулаад Enter дарна уу"
                       class="flex-1 min-w-[12rem] border-0 py-1 focus:ring-0 focus:outline-none">
            </div>
        </div>
    </div>
    {{-- intentionally no "+ add" button: add via Enter --}}
    <template x-for="(d, i) in items" :key="'h-'+i">
        <input type="hidden" :name="'{{ $nameKey }}['+i+']'" :value="d.name">
    </template>
</div>

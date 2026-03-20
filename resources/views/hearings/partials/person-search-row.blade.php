@php
    $initial = $initial ?? collect();
    $nameKey = $nameKey ?? 'names';
    $label = $label ?? 'Нэр';
    $buttonLabel = $buttonLabel ?? 'Нэмэх';
    $modalTitle = $modalTitle ?? 'Оруулах';
    $searchUrl = $searchUrl ?? route('admin.defendant-search');
@endphp
<div
    class="flex flex-wrap items-end gap-3"
    x-data="{
        items: @js($initial->all()),
        openModal: false,
        activeTab: 'person',
        registry: '',
        organizationName: '',
        loading: false,
        results: [],
        message: '',
        searchUrl: @js($searchUrl),
        addItem(item) {
            if (!item || !item.name) return;
            if (this.items.some(d => d.name === item.name && d.registry === (item.registry || ''))) return;
            this.items.push({ name: item.name, registry: item.registry || '' });
        },
        addManualOrganization() {
            const name = (this.organizationName || '').trim();
            if (!name) {
                this.message = 'Байгууллагын нэр оруулна уу.';
                return;
            }
            if (this.items.some(d => (d.name || '').trim().toLowerCase() === name.toLowerCase())) {
                this.message = 'Энэ нэртэй байгууллага аль хэдийн нэмэгдсэн байна.';
                return;
            }
            this.items.push({ name, registry: '' });
            this.organizationName = '';
            this.message = '';
            this.openModal = false;
        },
        removeItem(index) { this.items.splice(index, 1); },
        async search() {
            const reg = (this.registry || '').trim();
            if (!reg) { this.message = 'Регистрийн дугаар оруулна уу.'; this.results = []; return; }
            this.loading = true; this.message = ''; this.results = [];
            try {
                const r = await fetch(this.searchUrl + '?registry=' + encodeURIComponent(reg));
                const data = await r.json();
                this.results = data.results || [];
                if (this.results.length === 0) this.message = data.message || 'Олдсонгүй.';
            } catch (e) { this.message = 'Хайлт амжилтгүй.'; }
            this.loading = false;
        }
    }"
>
    <div class="flex-1 min-w-[200px]">
        <label class="text-sm font-semibold text-gray-700">{{ $label }}</label>
        <div class="mt-1 min-h-[2.5rem] rounded-md border border-gray-300 bg-gray-50/50 p-2 flex flex-wrap items-center gap-2">
            <template x-for="(d, i) in items" :key="i">
                <span class="inline-flex items-center gap-1.5 bg-white border border-gray-300 rounded-lg px-2.5 py-1 text-sm shadow-sm">
                    <span x-text="d.registry ? d.name + ' (' + d.registry + ')' : d.name"></span>
                    <button type="button" @click="removeItem(i)" class="text-gray-500 hover:text-red-600 leading-none">&times;</button>
                </span>
            </template>
        </div>
    </div>
    <div class="shrink-0">
        <button type="button"
                @click="openModal = true; activeTab = 'person'; registry = ''; organizationName = ''; results = []; message = '';"
                class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            <span>+</span>
            <span>{{ $buttonLabel }}</span>
        </button>
    </div>
    <template x-for="(d, i) in items" :key="'h-'+i">
        <input type="hidden" :name="'{{ $nameKey }}['+i+']'" :value="d.name">
    </template>

    <div x-show="openModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         @keydown.escape.window="openModal = false"
    >
        <div @click.outside="openModal = false"
             class="bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[85vh] flex flex-col overflow-hidden"
             role="dialog"
             aria-label="{{ $modalTitle }}">
            <div class="px-5 py-4 border-b bg-slate-50 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">{{ $modalTitle }}</h3>
                <button type="button" @click="openModal = false" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-5 space-y-4 overflow-y-auto">
                <div class="inline-flex rounded-lg border border-slate-300 bg-white p-1">
                    <button type="button"
                            @click="activeTab = 'person'"
                            :class="activeTab === 'person' ? 'bg-blue-700 text-white' : 'text-slate-600 hover:bg-slate-100'"
                            class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors">
                        Иргэн
                    </button>
                    <button type="button"
                            @click="activeTab = 'organization'"
                            :class="activeTab === 'organization' ? 'bg-blue-700 text-white' : 'text-slate-600 hover:bg-slate-100'"
                            class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors">
                        Байгууллага
                    </button>
                </div>

                <div x-show="activeTab === 'person'" x-cloak class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                    <label class="block text-sm font-semibold text-slate-700">Иргэн (регистрийн дугаараар хайх)</label>
                    <div class="flex gap-2">
                        <input type="text"
                               x-model="registry"
                               @keydown.enter.prevent="search()"
                               placeholder="Регистрийн дугаар"
                               class="flex-1 rounded-md border border-slate-300 px-3 py-2 bg-white">
                        <button type="button"
                                @click="search()"
                                :disabled="loading"
                                class="px-4 py-2 rounded-md bg-blue-700 text-white hover:bg-blue-800 disabled:opacity-50">
                            <span x-text="loading ? 'Уншиж...' : 'Хайх'"></span>
                        </button>
                    </div>
                    <p x-show="message" x-text="message" class="text-sm text-amber-700"></p>
                    <div class="border border-slate-200 rounded-lg overflow-auto max-h-56 bg-white">
                        <template x-for="(item, idx) in results" :key="idx">
                            <div class="flex items-center justify-between px-3 py-2 border-b border-slate-100 hover:bg-slate-50 last:border-0">
                                <div>
                                    <span class="font-medium text-slate-800" x-text="item.name"></span>
                                    <span x-show="item.registry" class="text-slate-500 text-sm ml-1" x-text="'(' + item.registry + ')'"></span>
                                </div>
                                <button type="button"
                                        @click="addItem(item); openModal = false"
                                        class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    Сонгох
                                </button>
                            </div>
                        </template>
                        <p x-show="!loading && results.length === 0 && !message" class="px-3 py-4 text-slate-500 text-sm">Регистрийн дугаар оруулаад Хайх дарна уу.</p>
                    </div>
                </div>

                <div x-show="activeTab === 'organization'" x-cloak class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                    <label class="block text-sm font-semibold text-slate-700">Байгууллага (нэрээр гараар оруулах)</label>
                    <div class="flex gap-2">
                        <input type="text"
                               x-model="organizationName"
                               @keydown.enter.prevent="addManualOrganization()"
                               placeholder="Байгууллагын нэр"
                               class="flex-1 rounded-md border border-slate-300 px-3 py-2 bg-white">
                        <button type="button"
                                @click="addManualOrganization()"
                                class="px-4 py-2 rounded-md border border-slate-300 bg-white hover:bg-slate-100 text-sm font-medium text-slate-700">
                            Нэмэх
                        </button>
                    </div>
                </div>
            </div>
            <div class="px-5 py-4 border-t bg-slate-50 flex justify-end">
                <button type="button" @click="openModal = false" class="px-4 py-2 rounded-md border border-slate-300 bg-white hover:bg-slate-100">Хаах</button>
            </div>
        </div>
    </div>
</div>

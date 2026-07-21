<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Nom complet</label>
        <input name="full_name" value="{{ old('full_name', $patient?->full_name) }}" required
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm @error('full_name') border-red-400 @enderror">
        @error('full_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Téléphone</label>
        <input name="phone" value="{{ old('phone', $patient?->phone) }}" required
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm @error('phone') border-red-400 @enderror">
        @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Langue</label>
        <select name="language" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            @foreach(['ar'=>'AR','fr'=>'FR','en'=>'EN'] as $k=>$lab)
                <option value="{{ $k }}" @selected(old('language', $patient?->language ?? 'fr')==$k)>{{ $lab }}</option>
            @endforeach
        </select>
        @error('language')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
        <textarea name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('notes', $patient?->notes) }}</textarea>
        @error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

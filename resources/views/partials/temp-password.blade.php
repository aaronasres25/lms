@if (session('temp_password'))
    <div class="mb-4 rounded-2xl border-2 border-dashed border-emerald-300 bg-emerald-50 p-4">
        <div class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Login created - password shown only once</div>
        <div class="mt-1 flex flex-wrap items-center gap-3">
            <code id="tempPw" class="rounded-lg bg-white px-3 py-2 text-lg font-bold tracking-wider text-emerald-900 shadow-sm">{{ session('temp_password') }}</code>
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('tempPw').textContent).then(() => this.textContent = 'Copied ✓')" class="btn btn-outline">Copy</button>
        </div>
        <p class="mt-2 text-xs text-emerald-800">Give it to the user securely. They will be asked to choose their own password at the first sign-in.</p>
    </div>
@endif

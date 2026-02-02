<div>
    @if($isImpersonating)
    <div class="bg-amber-500 text-white px-4 py-2 text-center text-sm font-medium">
        <div class="flex items-center justify-center gap-4">
            <span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline-block mr-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                You are impersonating <strong>{{ Auth::user()->name }}</strong>
                @if($impersonator)
                    (originally {{ $impersonator->name }})
                @endif
            </span>
            <a
                href="{{ route('stop-impersonating') }}"
                class="inline-flex items-center gap-1 px-3 py-1 bg-white text-amber-600 rounded-md hover:bg-amber-50 transition-colors font-semibold text-xs"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
                </svg>
                Stop Impersonating
            </a>
        </div>
    </div>
    @endif
</div>

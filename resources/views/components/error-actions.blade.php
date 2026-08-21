<div class="flex items-center mt-10 gap-x-2">
    <a href="{{ url()->previous() }}">
        <x-forms.button>Go back</x-forms.button>
    </a>
    <a href="{{ route('dashboard') }}" {{ wireNavigate() }}>
        <x-forms.button>Dashboard</x-forms.button>
    </a>
    <a target="_blank" class="text-xs" href="{{ config('branding.urls.contact') }}">Contact
        support
        <x-external-link />
    </a>
</div>

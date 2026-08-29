@extends('layouts.base')
@section('body')
    @parent
    @if (isSubscribed() || !isCloud())
        {{-- Promos off: Coolify-branded popups break the Railway skin. The
             realtime-connection warning still renders. --}}
        <livewire:layout-popups :show-promos="false" />
    @endif
    @auth
        <div class="rw-root" x-data>
            {{ $slot }}
        </div>
    @endauth
@endsection

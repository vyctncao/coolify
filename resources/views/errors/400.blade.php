@extends('layouts.base')
<div class="flex flex-col items-center justify-center h-full">
    <div>
        <p class="font-mono font-semibold text-7xl dark:text-warning">400</p>
        <h1 class="mt-4 font-bold tracking-tight dark:text-white">Bad Request</h1>
        @if ($exception->getMessage())
            <p class="text-base leading-7 text-red-500">{{ $exception->getMessage() }}</p>
        @else
            <p class="text-base leading-7 dark:text-neutral-400 text-black">The request could not be understood by the
                server due to
                malformed syntax.
            </p>
        @endif
        <x-error-actions />
    </div>
</div>

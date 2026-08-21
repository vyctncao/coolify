@extends('layouts.base')
<div class="flex flex-col items-center justify-center h-full">
    <div>
        <p class="font-mono font-semibold text-7xl dark:text-warning">404</p>
        <h1 class="mt-4 font-bold tracking-tight dark:text-white">How did you get here?</h1>
        <p class="text-base leading-7 dark:text-neutral-400 text-black">Sorry, we couldn't find the page you're looking
            for.
        </p>
        <x-error-actions />
    </div>
</div>

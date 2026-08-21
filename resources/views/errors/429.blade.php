@extends('layouts.base')
<div class="flex flex-col items-center justify-center h-full">
    <div>
        <p class="font-mono font-semibold text-7xl dark:text-warning">429</p>
        <h1 class="mt-4 font-bold tracking-tight dark:text-white">Woah, slow down there!</h1>
        <p class="text-base leading-7 dark:text-neutral-400 text-black">You're making too many requests. Please wait a
            few
            seconds before trying again.
        </p>
        <x-error-actions />
    </div>
</div>

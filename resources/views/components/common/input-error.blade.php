@props([
    'for',
    'bag' => null,
    'id' => null,
    'class' => 'mt-1 text-xs text-error-600',
])

@php
    $key = (string) $for;
    $errorId = $id ? (string) $id : ('error-' . preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $key));
@endphp

@error($key, $bag)
    <p id="{{ $errorId }}" class="{{ $class }}" role="alert">{{ $message }}</p>
@enderror


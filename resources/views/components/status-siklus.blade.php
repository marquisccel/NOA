@props(['siklus'])

<x-badge :variant="$siklus->varianStatus()" :dot="true" {{ $attributes }}>{{ $siklus->labelStatus() }}</x-badge>

@props([
    'name' => '',
    'required' => false,
])

<select
    name="{{ $name }}"
    {{ $required ? 'required' : '' }}
    {{ $attributes->merge(['class' => 'w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 bg-white']) }}
>
    {{ $slot }}
</select>

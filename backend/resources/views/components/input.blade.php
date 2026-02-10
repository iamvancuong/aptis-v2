@props([
    'type' => 'text',
    'name' => '',
    'value' => '',
    'label' => '',
    'placeholder' => '',
    'required' => false,
    'error' => null,
])

<div class="mb-4">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <input 
        id="{{ $name }}"
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'w-full px-4 py-2.5 text-sm border ' . ($error ? 'border-red-500' : 'border-gray-300') . ' rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200']) }}
    >

    @if($error)
        <p class="text-red-500 text-xs mt-1">{{ $error }}</p>
    @endif
</div>

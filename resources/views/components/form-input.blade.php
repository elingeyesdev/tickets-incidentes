@props([
    'name',
    'label',
    'type' => 'text',
    'required' => false,
    'placeholder' => '',
    'value' => '',
    'help' => null
])

<div class="form-group">
    <label for="{{ $name }}">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        class="form-control @error($name) is-invalid @enderror"
        placeholder="{{ $placeholder }}"
        value="{{ old($name, $value) }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes }}
    >

    @if ($help)
        <small class="form-text text-muted">{{ $help }}</small>
    @endif

    @error($name)
        <span class="invalid-feedback d-block">{{ $message }}</span>
    @enderror
</div>

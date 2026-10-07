@props([
    'image' => null,
    'alt' => '',
    'class' => '',
])

@if($image)
    <div
        {{ $attributes->merge(['class' => 'vision-image-hover ' . $class]) }}
        data-image-hover
        style="--vision-image: url('{{ $image }}');"
        tabindex="0"
        role="img"
        aria-label="{{ $alt }}"
    >
        @for($i = 0; $i < 10; $i++)
            <div
                class="vision-image-hover__layer rectangle"
                aria-hidden="true"
            ></div>
        @endfor
    </div>
@endif

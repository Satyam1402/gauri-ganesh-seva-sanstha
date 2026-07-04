@props(['as' => 'div'])

<{{ $as }} {{ $attributes->class(['mx-auto w-full max-w-[1360px] px-4 sm:px-6 lg:px-8']) }}>
    {{ $slot }}
</{{ $as }}>

@if (session('status'))
    <x-ui.alert variant="success" dismissible class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

@if (session('error'))
    <x-ui.alert variant="error" dismissible class="mb-6">{{ session('error') }}</x-ui.alert>
@endif

@if ($errors->any())
    <x-ui.alert variant="error" dismissible class="mb-6">
        <p class="font-semibold">Please fix the following:</p>
        <ul class="mt-1 list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif

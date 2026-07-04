@if (session('status'))
    <x-ui.container class="pt-6">
        <x-ui.alert variant="success" dismissible>{{ session('status') }}</x-ui.alert>
    </x-ui.container>
@endif

@if (session('error'))
    <x-ui.container class="pt-6">
        <x-ui.alert variant="error" dismissible>{{ session('error') }}</x-ui.alert>
    </x-ui.container>
@endif

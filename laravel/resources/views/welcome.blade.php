<x-welcome-layout>
    <x-slot name="header">
        <div class="shrink-0 flex items-center justify-center mx-auto">
            <a href="{{ route('welcome') }}">
                <x-application-logo class="block h-20 w-auto fill-current text-gray-800 dark:text-gray-200" />
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-lg">
                <div class="py-12 text-center text-gray-800 dark:text-gray-200">
                    <h1 class="text-xl font-bold mb-6">{{ __('messages.welcome') }}</h1>
                    <p class="text-xl mb-4">
                        {{ __('messages.work_together_identify_werewolves') }}
                    </p>
                    <p class="mb-4">
                        {{ __('messages.join_survive_help') }}
                    </p>
                    @auth
                        <a href="{{ route('dashboard') }}" class="mt-6 inline-block text-black font-bold py-2 px-4 rounded hover:bg-opacity-75" style="background-color: #e7d49e;">
                            {{ __('messages.start_playing') }}
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="mt-6 inline-block text-black font-bold py-2 px-4 rounded hover:bg-opacity-75" style="background-color: #e7d49e;">
                            {{ __('messages.join_now') }}
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</x-welcome-layout>

<x-welcome-layout>
    <x-slot name="header">
        <div class="flex items-center justify-center">
            <h2 class="ml-2 font-semibold text-xl text-gray-800 dark:text-gray-200">
                {{ __('Katze') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-lg">
                <div class="py-12 text-center text-gray-800 dark:text-gray-200">
                    <h1 class="text-3xl font-bold mb-6">{{ __('Welcome to Katze') }}</h1>
                    <p class="text-xl mb-4">
                        {{ __('A social deduction game where villagers must work together to identify the werewolves among them.') }}
                    </p>
                    <p class="mb-4">
                        {{ __('Join a game, survive the night, and help your team win!') }}
                    </p>
                    @auth
                        <a href="{{ route('dashboard') }}" class="mt-6 inline-block bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            {{ __('Start Playing') }}
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="mt-6 inline-block bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            {{ __('Join Now') }}
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</x-welcome-layout>

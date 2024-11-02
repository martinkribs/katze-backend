<x-guest-layout>
    <x-slot name="header">
        <div class="flex items-center justify-center">
            <span class="text-3xl">🐱</span>
            <h2 class="ml-2 font-semibold text-xl text-gray-200">
                {{ __('Werwölfe von Düsterwald') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-gray-800 overflow-hidden shadow-lg sm:rounded-lg">
                <div class="p-8 text-gray-100 text-center">
                    <h1 class="text-3xl font-bold mb-6">Welcome to Katze</h1>
                    <p class="text-xl mb-4">
                        A social deduction game where villagers must work together to identify the werewolves among them.
                    </p>
                    <p class="text-gray-400">
                        Join a game, survive the night, and help your team win!
                    </p>
                    @auth
                        <a href="{{ route('dashboard') }}" class="mt-6 inline-block bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            Start Playing
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="mt-6 inline-block bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            Join Now
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

</x-guest-layout>
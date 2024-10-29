<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('verification.title') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 0;
        }
        .store-buttons {
            display: none;
            margin-top: 20px;
        }
        .store-button {
            display: block;
            margin: 10px 0;
        }
        #desktop-instructions {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ __('verification.title') }}</h1>
        
        <div id="loading">
            {{ __('verification.verifying') }}
        </div>

        <div id="desktop-instructions">
            <h2>{{ __('verification.desktop_title') }}</h2>
            <p>{{ __('verification.desktop_instructions') }}</p>
            <ol>
                @foreach(__('verification.desktop_steps') as $step)
                    <li>{{ $step }}</li>
                @endforeach
            </ol>
        </div>

        <div id="mobile-content" style="display: none;">
            <p>{{ __('verification.verified') }}</p>
            <a href="katzeapp://verify-email?token={{ $token }}" class="button" id="open-app">
                {{ __('verification.open_in_app') }}
            </a>
            
            <div class="store-buttons">
                <p>{{ __('verification.download_prompt') }}</p>
                <a href="https://play.google.com/store/apps/details?id=com.katzeapp" class="store-button" id="play-store">
                    {{ __('verification.get_android') }}
                </a>
                <a href="https://apps.apple.com/app/katzeapp" class="store-button" id="app-store">
                    {{ __('verification.get_ios') }}
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
            const loading = document.getElementById('loading');
            const desktopInstructions = document.getElementById('desktop-instructions');
            const mobileContent = document.getElementById('mobile-content');
            const storeButtons = document.querySelector('.store-buttons');
            
            loading.style.display = 'none';

            if (isMobile) {
                mobileContent.style.display = 'block';
                
                // Try to open the app
                const openApp = document.getElementById('open-app');
                openApp.click();

                // Show store buttons after a delay if app doesn't open
                setTimeout(() => {
                    storeButtons.style.display = 'block';
                }, 2000);
            } else {
                desktopInstructions.style.display = 'block';
            }
        });
    </script>
</body>
</html>

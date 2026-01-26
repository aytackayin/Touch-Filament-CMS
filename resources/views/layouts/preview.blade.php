<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview</title>

    <!-- Fonts mirrored from blog layout -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap"
        rel="stylesheet">

    <!-- Tailwind & Scripts from frontend -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com?plugins=typography,forms"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        display: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        indigo: {
                            50: '#f5f7ff',
                            600: '#4f46e5',
                        }
                    }
                }
            }
        }

        // Sync dark mode with parent Filament window
        window.addEventListener('message', (event) => {
            if (event.data === 'enable-dark') {
                document.documentElement.classList.add('dark');
            } else if (event.data === 'disable-dark') {
                document.documentElement.classList.remove('dark');
            }
        });
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: transparent !important;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Outfit', sans-serif;
        }

        /* Hide scrollbars for cleaner iframe integration if needed */
        ::-webkit-scrollbar {
            width: 0px;
            background: transparent;
        }
    </style>
</head>

<body class="antialiased text-slate-900 dark:text-slate-100">
    <main>
        {{ $slot }}
    </main>
</body>

</html>
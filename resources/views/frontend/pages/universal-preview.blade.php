<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Mirror frontend fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Tailwind Typography using CDN for instant match -->
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        display: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }

        window.addEventListener('message', (event) => {
            if (event.data === 'enable-dark') { document.documentElement.classList.add('dark'); }
            else if (event.data === 'disable-dark') { document.documentElement.classList.remove('dark'); }
        });
    </script>

    <style>
        body {
            background: transparent !important;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            transition: background-color 0.3s ease;
        }

        html.dark body {
            background-color: #0f172a !important;
        }

        html:not(.dark) body {
            background-color: #ffffff !important;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #475569;
        }
    </style>
</head>

<body class="antialiased">
    <div class="prose prose-lg dark:prose-invert max-w-none font-sans" style="padding: 1rem;">
        {!! $content !!}
    </div>

    <script>
        function reportHeight() {
            window.parent.postMessage({
                type: 'resize',
                height: document.body.scrollHeight
            }, '*');
        }
        window.addEventListener('load', reportHeight);
        window.addEventListener('resize', reportHeight);
        new ResizeObserver(reportHeight).observe(document.body);
    </script>
</body>

</html>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Core Workbench</title>
        <style>
            :root {
                color-scheme: light;
                --bg: #f6f0e8;
                --ink: #1f2937;
                --muted: #6b7280;
                --card: rgba(255, 255, 255, 0.78);
                --border: rgba(31, 41, 55, 0.12);
                --accent: #d97706;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: grid;
                place-items: center;
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                color: var(--ink);
                background:
                    radial-gradient(circle at top left, rgba(217, 119, 6, 0.18), transparent 30%),
                    radial-gradient(circle at bottom right, rgba(31, 41, 55, 0.08), transparent 28%),
                    linear-gradient(135deg, #fffaf2 0%, var(--bg) 100%);
            }

            main {
                width: min(720px, calc(100vw - 2rem));
                padding: 3rem;
                border: 1px solid var(--border);
                border-radius: 28px;
                background: var(--card);
                box-shadow: 0 24px 80px rgba(31, 41, 55, 0.12);
                backdrop-filter: blur(18px);
            }

            .eyebrow {
                margin: 0 0 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.22em;
                font-size: 0.78rem;
                color: var(--accent);
                font-weight: 700;
            }

            h1 {
                margin: 0;
                font-size: clamp(2.5rem, 7vw, 4.8rem);
                line-height: 0.96;
                letter-spacing: -0.06em;
            }

            p {
                margin: 1.25rem 0 0;
                max-width: 36rem;
                font-size: 1.08rem;
                line-height: 1.7;
                color: var(--muted);
            }
        </style>
    </head>
    <body>
        <main>
            <p class="eyebrow">Devanox Core</p>
            <h1>Core package workbench</h1>
            <p>The package is ready for browser testing.</p>
        </main>
    </body>
</html>

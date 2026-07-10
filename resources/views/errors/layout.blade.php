<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — রূপশী POS</title>
    @include('partials.base-fonts')
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Hind Siliguri', sans-serif;
            background: #0a0f1e;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow: hidden;
        }

        /* Subtle background pattern */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 50% at 20% 40%, rgba(79,70,229,.08) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 80% 70%, rgba(16,185,129,.06) 0%, transparent 60%);
            pointer-events: none;
        }

        .error-wrap {
            position: relative;
            text-align: center;
            max-width: 520px;
            width: 100%;
            animation: fadeUp .4s ease;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Big code number */
        .error-code {
            font-size: clamp(5rem, 20vw, 8rem);
            font-weight: 700;
            line-height: 1;
            margin-bottom: 8px;
            background: linear-gradient(135deg, @yield('code-color-from'), @yield('code-color-to'));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -.02em;
            user-select: none;
        }

        /* Icon */
        .error-icon {
            font-size: 3.2rem;
            margin-bottom: 18px;
            display: block;
            color: @yield('icon-color');
            filter: drop-shadow(0 0 18px @yield('icon-glow'));
        }

        /* Title */
        .error-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 10px;
        }

        /* Description */
        .error-desc {
            font-size: .95rem;
            color: #94a3b8;
            line-height: 1.7;
            margin-bottom: 28px;
        }

        /* Action card */
        .error-card {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            text-align: left;
        }
        .error-card-title {
            font-size: .78rem;
            font-weight: 700;
            color: #64748b;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .error-steps {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .error-steps li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: .88rem;
            color: #cbd5e1;
            line-height: 1.5;
        }
        .error-steps li i {
            margin-top: 2px;
            flex-shrink: 0;
            width: 16px;
            color: @yield('icon-color');
        }

        /* Buttons */
        .error-btns {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-primary-err {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 22px;
            background: linear-gradient(135deg, @yield('btn-from'), @yield('btn-to'));
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: .9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: opacity .2s, transform .1s;
        }
        .btn-primary-err:hover { opacity: .88; transform: translateY(-1px); }
        .btn-ghost-err {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 22px;
            background: transparent;
            color: #94a3b8;
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 10px;
            font-family: inherit;
            font-size: .9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s, color .2s;
        }
        .btn-ghost-err:hover { background: rgba(255,255,255,.06); color: #e2e8f0; }

        /* Brand footer */
        .error-brand {
            margin-top: 32px;
            font-size: .78rem;
            color: #334155;
        }

        @media (max-width: 480px) {
            .error-code { font-size: 5rem; }
            .error-title { font-size: 1.2rem; }
            .error-btns { flex-direction: column; }
            .btn-primary-err, .btn-ghost-err { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="error-wrap">
        <div class="error-code">@yield('code')</div>
        <i class="@yield('icon') error-icon"></i>
        <div class="error-title">@yield('title-text')</div>
        <div class="error-desc">@yield('description')</div>

        <div class="error-card">
            <div class="error-card-title">এখন কী করবেন?</div>
            <ul class="error-steps">
                @yield('steps')
            </ul>
        </div>

        <div class="error-btns">
            @yield('buttons')
        </div>

        <div class="error-brand">রূপশী POS — সফটওয়্যার সহায়তায় সমস্যা হলে অ্যাডমিনকে জানান</div>
    </div>
</body>
</html>

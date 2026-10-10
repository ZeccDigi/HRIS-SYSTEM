<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Redirecting') }}</title>
    <script>
        window.parent.location.replace(@js($url));
    </script>
</head>
<body style="margin:0;background:#fff;">
</body>
</html>

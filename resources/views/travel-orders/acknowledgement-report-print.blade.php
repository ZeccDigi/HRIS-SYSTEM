<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Authority Report (TAR)</title>
    @include('travel-orders.partials.acknowledgement-report-styles')
</head>
<body>
    @include('travel-orders.partials.acknowledgement-report-document')
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>

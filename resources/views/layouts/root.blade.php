<!doctype html>
<html lang="en-us">
<head>
    <meta charset="utf-8">
    <title>{{ $title or "" }} - HAWA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link href="{{ asset('img/favicon.png') }}" rel="shortcut icon" type="image/vnd.microsoft.icon" />
    <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('semantic/semantic.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
    <style>
        @stack('block-styles')
    </style>
</head>
<body>

@yield('content')

<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="{{ asset('semantic/semantic.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
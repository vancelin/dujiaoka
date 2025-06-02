<!DOCTYPE html>
<html lang="{{ str_replace('_','-',strtolower(app()->getLocale())) }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($page_title) ? $page_title : '' }} | {{ dujiaoka_config_get('title') }}</title>
    <meta name="keywords" content="{{ $gd_keywords ?? dujiaoka_config_get('seo_keywords', '') }}">
    <meta name="description" content="{{ $gd_description ?? dujiaoka_config_get('seo_description', '') }}">
    <meta property="og:type" content="article">
    <meta property="og:image" content="{{ $picture ?? dujiaoka_config_get('img_logo') }}">
    <meta property="og:title" content="{{ isset($page_title) ? $page_title : '' }}">
    <meta property="og:description" content="{{ $gd_description ?? dujiaoka_config_get('seo_description', '') }}">    
    @if(isset($updated_at))
        <meta property="og:release_date" content="{{ $updated_at }}">
    @endif
    @if(\request()->getScheme() == "https")
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    @endif
    <link rel="stylesheet" href="/assets/unicorn/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/unicorn/css/base.css">
    <link rel="stylesheet" href="/assets/unicorn/css/common.css">
    <link rel="stylesheet" href="/assets/unicorn/css/index.css">
    <link rel="stylesheet" href="/assets/unicorn/css/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
@include('unicorn.layouts._nav')
@yield('content')
@include('unicorn.layouts._footer')
@include('unicorn.layouts._script')
@section('js')
@show
</body>
</html>

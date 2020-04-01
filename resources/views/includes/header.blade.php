<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="shortcut icon" href="{!! asset('favicon.ico') !!}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title')</title>
@yield('styles')
{!! Html::style('css/bootstrap.min.css') !!}
{!! Html::style('css/bootstrap-grid.min.css') !!}
{!! Html::style('css/font-awesome.min.css') !!}
{!! Html::style('css/fonts.googleapis.com.css') !!}
{!! Html::style('css/ace-rtl.min.css') !!}
{!! Html::style('css/ace.min.css') !!}
{!! Html::style('css/ace-skins.min.css') !!}
{!! Html::style('css/main.css') !!}
{!! Html::script('js/ace-extra.min.js') !!}
@yield('styles-after')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">

@yield('extra-scripts')




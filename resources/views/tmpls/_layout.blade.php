<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @includeFirst(['tmpls._head', 'frameworks._head'])
</head>
<body>
<div id="app" class="container v-cloak" v-loading.fullscreen.lock="fullscreen_loading">
    <header class="row">
        @includeFirst(['tmpls._header', 'frameworks._header'])
    </header>
    <div id="main" class="row">
        @yield('content')
    </div>
    <footer class="footer row">
        @includeFirst(['tmpls._footer', 'frameworks._footer'])
    </footer>
</div>
@section('script')
    <script type="text/javascript" src="{{asset('js/app.js')}}"></script>
@show
</body>
</html>

@extends('test._layout')

@section('content')
    @parent
    <p>index page</p>
    @include('test._page')
@endsection

@section('script')
    <script>
        var js_data = @json($js_data);
        var mixin = {
            data: {
                'data': js_data.data,
                'page': js_data.page,
                'search': {},
            },
            methods: {
                handleSizeChange(val) {
                    console.log(`每页 ${val} 条`);
                },
                handleCurrentChange(val) {
                    console.log(`当前页: ${val}`);
                }
            }
        }
    </script>
    @parent
@endsection
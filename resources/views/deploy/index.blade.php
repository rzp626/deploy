<script data-exec-on-popstate>
    $(function () {
        $('.output-box').removeClass('hide');
        $('.output-box .output-body').html(data.data);
    });
</script>

<style>
    .output-body {
        white-space: pre-wrap;
        background: #000000;
        color: #00fa4a;
        padding: 10px;
        border-radius: 0;
    }

</style>

<div class="box box-default output-box">
    <div class="box-header with-border">
        <i class="fa fa-terminal"></i>

        <h3 class="box-title">执行输出结果：</h3>
    </div>
    <!-- /.box-header -->
    <div class="box-body">
        <pre class="output-body">{{ $data }}</pre>
        <span><a class="btn btn-sm btn-primary grid-refresh" href="{{ url('admin/dp/ts') }}">返回</a> <div class="btn-group" style="margin-right: 10px" data-toggle="buttons"></div></span>
    </div>
    <!-- /.box-body -->
</div>

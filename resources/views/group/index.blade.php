<form role="form" name="form">
    <div class="md-form" id="my-from" style="display: none;">
        <label for="form106">所选组的用途</label>
        {{--<i class="fa fa-tag prefix grey-text"></i>--}}
        <input type="text" id="form106" class="form-control">
    </div>
    <br>
    <div class="form-group" id="test">
        <label for="name">分配组</label>
        <select class="form-control" onfocus="MyGroup()" name="mygroup" id="myGroup">
            <option value="">选择组</option>
            {{--@foreach($groupInfo as $group)--}}
                {{--<option value="{{ $group['id'] }}">{{ $group['name'] }}</option>--}}
            {{--@endforeach--}}
        </select>
    </div>
    <div class="form-group">
        <label for="name">选择用户</label>
        <select class="form-control" name="myuser" onfocus="MyUser();" id="myUser">
            <option value="">选择用户</option>
            {{--@foreach($userInfo as $user)--}}
                {{--<option value="{{ $user['id'] }}">{{ $user['name'] }}</option>--}}
            {{--@endforeach--}}
        </select>
    </div>

    <!--Grid column-->
    <div class="col-md-12">
        <div class="text-center">
            <button type="button" class="btn btn-grey btn-rounded z-depth-1a" id="createConfig">点击分配</button>
        </div>
    </div>
    <!--Grid column-->
</form>


<script>
    var userCounts = 0;
    var groupCounts = 0;
    var groupNo;
    var userNo;
    var userInfo = '{{ $userInfo }}';
    if (typeof userInfo != 'undefined' && userInfo.length > 0) {
        userInfo = JSON.parse(userInfo.replace(/&quot;/g,'"'));
        console.log(userInfo);
        userCounts=userInfo.length;
    }

    var groupInfo = '{{ $groupInfo }}';
    if (typeof groupInfo != 'undefined' && groupInfo.length > 0) {
        groupInfo = JSON.parse(groupInfo.replace(/&quot;/g,'"'));
        console.log(groupInfo);
        groupCounts=groupInfo.length;
    }

    function MyUser(){
        var i;
        document.form.myuser.options[0] = new Option('请先选择用户', '');
        for (i=1;i <= userCounts; i++) {
            document.form.myuser.options[i] = new Option(userInfo[i-1].name,userInfo[i-1].id);
        }
    }

    function MyGroup(){
        document.form.mygroup.options[0] = new Option('请选择组', '');

        var i;
        for (i=1;i <= groupCounts; i++) {
            document.form.mygroup.options[i] = new Option(groupInfo[i-1].name, groupInfo[i-1].id);
        }
    }

    $("#myGroup").bind('change', function () {
        groupNo = $(this).val();
        console.log(groupNo);
        if (groupNo.length > 0 && groupNo > 0) {
            for (i=0;i < groupCounts; i++) {
                if (groupInfo[i].id == groupNo) {
                    $("#form106").val(groupInfo[i].action);
                    $("#my-from").show();
                }
            }
        } else {
            $("#my-from").hide();
            alert('先选择组');
            return false;
        }
        console.log($(this).val());

    });


    $("#myUser").bind('change', function () {
        userNo = $(this).val();
        console.log(userNo);
        if (typeof groupNo == 'undefined'
            || groupNo == 0
            || groupNo.length == 0) {
            alert('先选择组');
            return false;
        }
        if (userNo.length <= 0 || userNo == 0) {
            alert("请选择分配组用户");
            return false;
        } else {
            $.ajax({
                url: '',
                type: 'POST',
                data: {'userId': userNo, 'groupId': groupNo},
                dataType: 'json',
                success: function (data) {
                    console.log(data);
                },
                error: function (data) {
                    console.log(data);
                }
            });
        }
    });

    // 创建配置事件
$("#createConfig").on('click', function(){
    if (typeof groupNo == 'undefined'
        || groupNo == ''
        || groupNo == 0) {
        alert('先选择组');
        return false;
    }

    if (typeof userNo == 'undefined'
        || userNo == ''
        || userNo == 0) {
        alert('请选择分配组用户');
        return false;
    }

    $.ajax({
        url: '/createGroupUser',
        type: 'POST',
        dataType: 'json',
        data: {'groupId': groupNo, 'userId': userNo, '_token': "{{csrf_token()}}"},
        success: function (data) {
            console.log(data);
            if (typeof data.code != 'undefined') {
                if (data.code == '4000') {
                    alert(data.msg);
                    return false;
                } else if (data.code == '5000') {
                    alert(data.msg);
                    return false;
                } else if (data.code == '2000') {
                    alert(data.msg);
                    location.reload();
                    return true;
                }
            }
        },
        error: function (data) {
            console.log(data);
            alert('创建配置文件失败');
            return false;
        },
    });
});
</script>
<!-- Section: form gradient -->
<section class="form-gradient mb-5">

  <!--Form with header-->
  <div class="card">
    <div class="card-body mx-4">
      <div class="md-form">
        <i class="fa fa-tag prefix grey-text"></i>
        <input type="text" id="form106" class="form-control">
        <label for="form106">存储路径</label>
      </div>

      <div class="md-form">
        <i class="fa fa-pencil prefix grey-text"></i>
        <textarea type="text" id="form107" class="md-textarea form-control" rows="15" placeholder="填写配置内容"></textarea>
        <label for="form107">配置文件内容</label>
      </div>


      <!--Grid row-->
      <div class="row d-flex align-items-center mb-3 mt-4">

        <!--Grid column-->
        <div class="col-md-12">
          <div class="text-center">
            <button type="button" class="btn btn-grey btn-rounded z-depth-1a" id="createConfig">点击创建</button>
          </div>
        </div>
        <!--Grid column-->

      </div>
      <!--Grid row-->
    </div>

  </div>
  <!--/Form with header-->

</section>
<!-- Section: form gradient -->

<script>
  var content;
  var dir;
  // 验证存储路径是否存在
  $("#form106").blur(function(){
      dir = $(this).val();
      if (typeof dir == 'undefined' || dir == '') {
          $(this).focus();
          $("#form106").css("background-color","#FFF");
          $(this).attr('placeholder', '存储路径必填');
          return false;
      }

      $.ajax({
          url: '/validDir',
          type: 'POST',
          dataType: 'json',
          data: {'dir': dir, '_token': "{{csrf_token()}}"},
          success: function (data) {
              console.log(data);
              if (typeof data.code != 'undefined') {
                  if (data.code == '4000') { // 参数有误
                      $("#form106").focus();
                      $("#form106").css("background-color","#D6D6FF");
                      $("#form106").val(data.msg);
                      return false;
                  } else if (data.code == '2000') { // 路径有效
                      $("#form106").css("background-color","#FFF");
                      $("#form107").focus();
                      return true;
                  } else if (data.code == '2001') { // 路径有效
                      alert(data.msg);
                      $("#form106").css("background-color","#FFF");
                      $("#form107").focus();
                      return true;
                  } else if (data.code == '4004') { // 路径无效
                      $("#form106").focus();
                      $("#form106").css("background-color","#D6D6FF");
                      $("#form106").val(data.msg);
                      return false;
                  } else {
                      $("#form106").focus();
                      $("#form106").css("background-color","#D6D6FF");
                      $("#form106").val('请重输入存储路径');
                      return false;
                  }
              }
          },
          error: function (data) {
              console.log(data);
              $("#form106").focus();
              $("#form106").css("background-color","#D6D6FF");
              $("#form106").val('请重输入存储路径');
          }
      });
  });

$("#form107").on('click', function () {
    if (typeof dir == 'undefined' || dir == '') {
        $("#form106").focus();
        $("#form106").css("background-color","#D6D6FF");
        $("#form106").val('请先输入存储路径');
        return true;
    }
});

    // 创建配置事件
$("#createConfig").on('click', function(){
    if (typeof dir == 'undefined' || dir == '') {
        $("#form106").focus();
        alert('配置路径不能为空');
        $("#form107").focus();
        return false;
    }

    content = $("#form107").val();
    if (typeof content == 'undefined' || content == '') {
        alert('配置文件内容不能为空');
        $("#form107").focus();
        return false;
    }

    $.ajax({
        url: '/createFile',
        type: 'POST',
        dataType: 'json',
        data: {'dir': dir, 'content': content, '_token': "{{csrf_token()}}"},
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
<html>
  <head>
    <title>UG部署系统留言板</title>
    <style type="text/css">
      .dark-matter {
      margin-left: auto;
      margin-right: auto;
      max-width: 500px;
      background: #555;
      padding: 20px 30px 20px 30px;
      font: 12px "Helvetica Neue", Helvetica, Arial, sans-serif;
      color: #D3D3D3;
      text-shadow: 1px 1px 1px #444;
      border: none;
      border-radius: 5px;
      -webkit-border-radius: 5px;
      -moz-border-radius: 5px;
      }
      .dark-matter h1 {
      padding: 0px 0px 10px 40px;
      display: block;
      border-bottom: 1px solid #444;
      margin: -10px -30px 30px -30px;
      }
      .dark-matter h1>span {
      display: block;
      font-size: 11px;
      }
      .dark-matter label {
      display: block;
      margin: 0px 0px 5px;
      }
      .dark-matter label>span {
      float: left;
      width: 20%;
      text-align: right;
      padding-right: 10px;
      margin-top: 10px;
      font-weight: bold;
      }
      .dark-matter input[type="text"], .dark-matter input[type="email"], .dark-matter textarea, .dark-matter select {
      border: none;
      color: #525252;
      height: 25px;
      line-height:15px;
      margin-bottom: 16px;
      margin-right: 6px;
      margin-top: 2px;
      outline: 0 none;
      padding: 5px 0px 5px 5px;
      width: 70%;
      border-radius: 2px;
      -webkit-border-radius: 2px;
      -moz-border-radius: 2px;
      -moz-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
      background: #DFDFDF;
      }
      .dark-matter select {
      background: #DFDFDF url('down-arrow.png') no-repeat right;
      background: #DFDFDF url('down-arrow.png') no-repeat right;
      appearance:none;
      -webkit-appearance:none;
      -moz-appearance: none;
      text-indent: 0.01px;
      text-overflow: '';
      width: 70%;
      height: 35px;
      color: #525252;
      line-height: 25px;
      }
      .dark-matter textarea{
      height:100px;
      padding: 5px 0px 0px 5px;
      width: 70%;
      }
      .dark-matter .button {
      background: #FFCC02;
      border: none;
      padding: 10px 25px 10px 25px;
      color: #585858;
      border-radius: 4px;
      -moz-border-radius: 4px;
      -webkit-border-radius: 4px;
      text-shadow: 1px 1px 1px #FFE477;
      font-weight: bold;
      box-shadow: 1px 1px 1px #3D3D3D;
      -webkit-box-shadow:1px 1px 1px #3D3D3D;
      -moz-box-shadow:1px 1px 1px #3D3D3D;
      }
      .dark-matter .button:hover {
      color: #333;
      background-color: #EBEBEB;
      }
    </style>
  </head>
  <body>
  <form id="myForm" class="dark-matter" method="post" action="/addMessage">
    {{ csrf_field() }}
    <h1>留言板
    <span></span>
    </h1>
    <label>
    <span>姓名 :</span>
    <input id="uname" type="text" name="uname" placeholder="您的姓名" />
    </label>
    <label>
    <span>邮箱地址 :</span>
    <input id="email" type="email" name="email" placeholder="邮箱地址" />
    </label>
    <label>
    <span>留言 :</span>
    <textarea id="content" name="content" placeholder="给我们的建议与意见"></textarea>
    </label>
    <label>
    <span>&nbsp;</span>
    <input type="submit" id="mySendBtn" class="button" value="Send" />
    </label>
  </form>
  <script>
      $("#myForm #mySendBtn").on('click', function(){
          var uname = $("#uname").val();
          if (typeof uname == 'undefined' || uname == '') {
              $("#uname").focus();
              $("#uname").css("background-color","#FFF");
              $("#uname").attr('placeholder', '姓名必填');
              return false;
          }

          var email = $("#email").val();
          if (typeof email == 'undefined' || email == '') {
              $("#email").focus();
              $("#email").css("background-color","#FFF");
              $("#email").attr('placeholder', '邮箱必填');
              return false;
          }

          var content = $("#content").val();
          if (typeof content == 'undefined' || content == '') {
              $("#content").focus();
              $("#content").css("background-color","#FFF");
              $("#content").attr('placeholder', '您的建议与意见呢？？？');
              return false;
          }

          return true;
          $.ajax({
              url: '/addMessage',
              type: 'POST',
              dataType: 'json',
              data: {'name': uname, 'email': email, 'content': content, '_token': "{{csrf_token()}}"},
              success: function (data) {
                  console.log(data);
                  return true;
                  if (typeof data.code != 'undefined') {
                      if (data.code == '4000') { // 参数有误
                          alert(data.msg);
                          return false;
                      } else if (data.code == '2000') { // 路径有效
                          alert(data.msg);
                          // window.location.reload();
                          //window.location.href="/admin/dp/list_message";
                          return true;
                      } else if (data.code == '4004') { // 路径无效
                          alert(data.msg);
                          return false;
                      } else {
                          alert('留言失败');
                          return false;
                      }
                  }
              },
              error: function (data) {
                  console.log(data);
                  return false;
              }
          });
      });
  </script>
  </body>
</html>
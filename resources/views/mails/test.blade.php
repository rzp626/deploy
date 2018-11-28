<form role="form">
  @foreach($email as $key => $value)
    <div class="form-group">
      <label for="name">用户{{ $value }}的邮箱：</label>
      <input type="text" class="form-control" id="name" placeholder="请输入名称" value="{{ $value }}">
    </div>
  @endforeach
  <button type="submit" class="btn btn-default">修改</button>
</form>

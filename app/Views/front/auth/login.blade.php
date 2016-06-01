@extends('front._master')

@section('css')

@endsection

@section('js')

@endsection

@section('js-init')

@endsection

@section('content')
    <div class="col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">Register</div>
            <div class="panel-body">
                <form class="form-horizontal" role="form" method="POST" action="/{{ $currentLanguageCode }}/auth/login">
                    {!! csrf_field() !!}
                    <div class="form-group">
                        <label class="col-md-4 control-label">E-Mail Address</label>

                        <div class="col-md-6">
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">Password</label>

                        <div class="col-md-6">
                            <input type="password" class="form-control" name="password">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-6 col-md-offset-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-btn fa-user"></i>Login
                            </button>
                            <a href="/{{ $currentLanguageCode.'/auth/register' }}" title="Register" class="btn btn-primary pull-right">Register</a>
                            <div class="clearfix"></div>
                            <br>
                            <a href="/{{ $currentLanguageCode.'/password/email' }}" title="Forget password" class="btn btn-primary pull-right">Forget password</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
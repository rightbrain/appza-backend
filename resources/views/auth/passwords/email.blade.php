
<!doctype html>
<html lang="en">
<head>
    <!-- meta item -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('messages.LazyCoder') }}">
    <meta name="author" content="{{ __('messages.LazyCoder') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('Fav.svg') }}" type="image/x-icon"/>

    <link href="{{ asset('assets/backend/css/bootstrap.min.css') }}" rel="stylesheet">
    <meta name="theme-color" content="#7952b3">

    <title>Reset Password</title>

    <style>
        html,body {height: 100%;}
        .global-container{height:100%;display: flex;align-items: center;justify-content: center;background-color: #f5f5f5;}
        form{padding-top: 10px;font-size: 14px;margin-top: 30px;}
        .card-title{ font-weight:300; }
        .btn{font-size: 14px;margin-top:20px;}
        .login-form{width:330px;margin:20px;}
        .sign-up{text-align:center;padding:20px 0 0;}
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/fontawesome.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/all.min.css" />
    <link href="{{ asset('assets/backend/css/login.css') }}" rel="stylesheet">
</head>
<body class="app">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Reset Password') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Send Password Reset Link') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>



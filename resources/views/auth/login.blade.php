{{-- File: resources/views/auth/login.blade.php --}}
@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="login-container" x-data="loginForm()">
<!-- Logo and Title -->
<div class="logo-section">
    <div class="logo">
<img src="{{ asset('assets/img/mtco-logo.png') }}" alt="Company Logo" style="max-height: 130px; width: auto; display: block; margin: 0 auto;">
    </div>
    <h1 class="system-title">PBC Checklist</h1>
</div>

    <!-- Login Form -->
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Laravel Validation Errors -->
        @if ($errors->any())
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Session Status -->
        @if (session('status'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <!-- Alpine.js Alert Messages -->
        <div x-show="alert.show" class="alert" :class="alert.type === 'error' ? 'alert-error' : 'alert-success'">
            <i :class="alert.type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle'"></i>
            <span x-text="alert.message"></span>
        </div>

        <!-- Email Field -->
        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-icon">
                <i class="fas fa-envelope"></i>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-input @error('email') border-red-500 @enderror"
                    placeholder="Enter your email"
                    value="{{ old('email') }}"
                    x-model="form.email"
                    required
                    autocomplete="email"
                    autofocus
                >
            </div>
            @error('email')
                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password Field -->
        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <div class="input-icon">
                <i class="fas fa-lock"></i>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input @error('password') border-red-500 @enderror"
                    placeholder="Enter your password"
                    x-model="form.password"
                    required
                    autocomplete="current-password"
                >
            </div>
            @error('password')
                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="remember-forgot">
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember" x-model="form.remember" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember">Remember me</label>
            </div>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="forgot-password">Forgot password?</a>
            @endif
        </div>

        <!-- Login Button -->
        <button type="submit" class="login-btn">
            <i class="fas fa-sign-in-alt" style="margin-right: 0.5rem;"></i>
            Sign In
        </button>
    </form>

    <!-- Demo Credentials -->
    @if(config('app.env') !== 'production')
    <div class="demo-credentials">
        <h4><i class="fas fa-users" style="margin-right: 0.5rem;"></i>Demo Accounts</h4>
        <div class="demo-accounts">
            <div class="demo-account" @click="fillCredentials('admin@pbcaudit.com', 'password')">
                <div class="demo-account-info">
                    <div class="demo-role">System Administrator</div>
                    <div class="demo-email">admin@pbcaudit.com</div>
                </div>
                <span class="demo-badge badge-admin">Admin</span>
            </div>

            <div class="demo-account" @click="fillCredentials('john.smith@auditfirm.com', 'password')">
                <div class="demo-account-info">
                    <div class="demo-role">Engagement Partner</div>
                    <div class="demo-email">john.smith@auditfirm.com</div>
                </div>
                <span class="demo-badge badge-partner">Partner</span>
            </div>

            <div class="demo-account" @click="fillCredentials('sarah.johnson@auditfirm.com', 'password')">
                <div class="demo-account-info">
                    <div class="demo-role">Manager</div>
                    <div class="demo-email">sarah.johnson@auditfirm.com</div>
                </div>
                <span class="demo-badge badge-manager">Manager</span>
            </div>

            <div class="demo-account" @click="fillCredentials('mike.wilson@auditfirm.com', 'password')">
                <div class="demo-account-info">
                    <div class="demo-role">Associate</div>
                    <div class="demo-email">mike.wilson@auditfirm.com</div>
                </div>
                <span class="demo-badge badge-associate">Associate</span>
            </div>

            <div class="demo-account" @click="fillCredentials('lisa.chen@abccorp.com', 'password')">
                <div class="demo-account-info">
                    <div class="demo-role">Client User</div>
                    <div class="demo-email">lisa.chen@abccorp.com</div>
                </div>
                <span class="demo-badge badge-guest">Client</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Footer Links -->
    <div class="footer-links">
        <a href="#">Privacy Policy</a>
        <a href="#">Terms of Service</a>
        <a href="#">Support</a>
    </div>

    <div class="version-info">
        {{ config('app.name') }} v1.0 | Laravel {{ app()->version() }} | Built with ❤️ for Audit Excellence
    </div>
</div>

@push('scripts')
<script>
    function loginForm() {
        return {
            form: {
                email: '{{ old('email') }}',
                password: '',
                remember: {{ old('remember') ? 'true' : 'false' }}
            },
            alert: {
                show: false,
                type: 'error',
                message: ''
            },

            fillCredentials(email, password) {
                this.form.email = email;
                this.form.password = password;
                this.hideAlert();

                // Update the actual form inputs
                document.getElementById('email').value = email;
                document.getElementById('password').value = password;

                // Auto-focus password field for convenience
                setTimeout(() => {
                    document.getElementById('password').focus();
                }, 100);
            },

            showAlert(type, message) {
                this.alert = {
                    show: true,
                    type: type,
                    message: message
                };

                // Auto-hide success messages
                if (type === 'success') {
                    setTimeout(() => {
                        this.hideAlert();
                    }, 5000);
                }
            },

            hideAlert() {
                this.alert.show = false;
            }
        }
    }

    // Auto-hide Laravel validation errors after 10 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }, 10000);
        });
    });
</script>
@endpush
@endsection

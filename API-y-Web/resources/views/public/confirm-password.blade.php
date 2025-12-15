@extends('adminlte::master')

@section('adminlte_css')
    {{-- Lockscreen styles --}}
@stop

@section('classes_body')
    lockscreen
@stop

@php
    $passResetUrl = View::getSection('password_reset_url') ?? config('adminlte.password_reset_url', 'password/reset');
    $dashboardUrl = View::getSection('dashboard_url') ?? config('adminlte.dashboard_url', 'home');

    if (config('adminlte.use_route_url', false)) {
        $passResetUrl = $passResetUrl ? route($passResetUrl) : '';
        $dashboardUrl = $dashboardUrl ? route($dashboardUrl) : '';
    } else {
        $passResetUrl = $passResetUrl ? url($passResetUrl) : '';
        $dashboardUrl = $dashboardUrl ? url($dashboardUrl) : '';
    }
@endphp

@section('body')
    <div class="lockscreen-wrapper" x-data="confirmPasswordForm()" x-init="init()">

        {{-- Lockscreen logo --}}
        <div class="lockscreen-logo">
            <a href="{{ $dashboardUrl }}">
                <img src="{{ asset(config('adminlte.logo_img')) }}" height="50">
                {!! config('adminlte.logo', '<b>Admin</b>LTE') !!}
            </a>
        </div>

        {{-- Lockscreen user name --}}
        <div class="lockscreen-name">
            <span x-text="userName"></span>
        </div>

        {{-- Lockscreen item --}}
        <div class="lockscreen-item">
            {{-- User avatar --}}
            @if(config('adminlte.usermenu_image'))
                <div class="lockscreen-image">
                    <img :src="userAvatar" :alt="userName">
                </div>
            @endif

            {{-- Lockscreen credentials form --}}
            <form
                @submit.prevent="submit()"
                class="lockscreen-credentials"
                :class="{ 'ml-0': !config('adminlte.usermenu_image') }"
                novalidate
            >
                @csrf

                <!-- Error Alert -->
                <div x-show="error" class="alert alert-danger alert-dismissible fade show" role="alert">
                    <button type="button" class="close" @click="error = false" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span x-text="errorMessage"></span>
                </div>

                <!-- Success Alert -->
                <div x-show="success" class="alert alert-success alert-dismissible fade show" role="alert">
                    <button type="button" class="close" @click="success = false" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <i class="fas fa-check-circle mr-2"></i>
                    <span x-text="successMessage"></span>
                </div>

                <div class="input-group">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-control"
                        :class="{ 'is-invalid': errors.password }"
                        x-model="formData.password"
                        @blur="validatePassword"
                        placeholder="{{ __('adminlte::adminlte.password') }}"
                        :disabled="loading"
                        required
                        autofocus
                    >

                    <div class="input-group-append">
                        <button
                            type="submit"
                            class="btn"
                            :disabled="loading"
                        >
                            <span x-show="!loading">
                                <i class="fas fa-arrow-right text-muted"></i>
                            </span>
                            <span x-show="loading">
                                <span class="spinner-border spinner-border-sm text-muted"></span>
                            </span>
                        </button>
                    </div>
                </div>

                <span class="invalid-feedback d-block" x-show="errors.password" x-text="errors.password"></span>
            </form>
        </div>

        {{-- Help block --}}
        <div class="help-block text-center">
            {{ __('adminlte::adminlte.confirm_password_message') }}
        </div>

        {{-- Additional links --}}
        <div class="text-center">
            <a href="{{ $passResetUrl }}">
                {{ __('adminlte::adminlte.i_forgot_my_password') }}
            </a>
        </div>

    </div>
@stop

@section('adminlte_js')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        function confirmPasswordForm() {
            return {
                formData: {
                    password: '',
                },
                errors: {
                    password: '',
                },
                loading: false,
                success: false,
                error: false,
                successMessage: '',
                errorMessage: '',
                userName: '',
                userAvatar: '',

                async init() {
                    // Cargar datos del usuario desde el localStorage o API
                    try {
                        const token = localStorage.getItem('access_token');
                        if (!token) {
                            window.location.href = '/login';
                            return;
                        }

                        const response = await fetch('/api/auth/status', {
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Accept': 'application/json',
                            },
                        });

                        if (response.ok) {
                            const data = await response.json();
                            this.userName = data.data.user.profile?.firstName + ' ' + data.data.user.profile?.lastName ||
                                           data.data.user.email;
                            this.userAvatar = data.data.user.profile?.avatarUrl || '';
                        }
                    } catch (err) {
                        console.error('Error loading user:', err);
                    }
                },

                validatePassword() {
                    this.errors.password = '';
                    if (!this.formData.password) {
                        this.errors.password = 'La contraseña es requerida';
                        return false;
                    }
                    if (this.formData.password.length < 8) {
                        this.errors.password = 'La contraseña debe tener al menos 8 caracteres';
                        return false;
                    }
                    return true;
                },

                async submit() {
                    const passwordValid = this.validatePassword();

                    if (!passwordValid) {
                        return;
                    }

                    this.loading = true;
                    this.error = false;
                    this.success = false;

                    try {
                        const token = localStorage.getItem('access_token');
                        const response = await fetch('/auth/confirm-password', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-Token': document.querySelector('input[name="_token"]').value,
                                'Authorization': `Bearer ${token}`,
                            },
                            body: JSON.stringify({
                                password: this.formData.password,
                            }),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Contraseña incorrecta');
                        }

                        this.successMessage = 'Contraseña confirmada. Redirigiendo...';
                        this.success = true;

                        setTimeout(() => {
                            window.location.href = '/dashboard';
                        }, 1500);

                    } catch (err) {
                        console.error('Confirm password error:', err);
                        this.errorMessage = err.message || 'Error desconocido';
                        this.error = true;
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
@stop

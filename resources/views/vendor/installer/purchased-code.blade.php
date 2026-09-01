@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.requirements.templateTitle') }}
@endsection

@section('title')
    <i class="fa fa-shield fa-fw" aria-hidden="true"></i>
    {{ trans('installer_messages.purchased_code.title') }}
@endsection
@if(session('message'))
    <div class="alert alert-success" style="width: 100%; word-wrap: break-word;">
        {{ session('message') }}
    </div>
@endif

@section('container')
    <form id="myForm" action="{{ route('LaravelInstaller::purchasedCodeStore') }}" method="post" style="width: 100%;">
        @csrf
        <div class="form-group {{ $errors->has('user_email') ? ' has-error ' : '' }}">
            <label for="user_email" >Email <br /> <span style=" font-weight:bold; color:blue"> * This email address will own the license code and be used for domain changes. </span></label>
            <input type="text" name="user_email" id="user_email" style="width: 100%;" value="{{ old('user_email', $project->user_email) }}" placeholder="Enter User Email" />
            @if ($errors->has('user_email'))
                <span class="error-block">
                <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                {{ $errors->first('user_email') }}
            </span>
            @endif
        </div>
        <div class="form-group {{ $errors->has('backend_url') ? ' has-error ' : '' }}">
            <label for="backend_url">{{ trans('installer_messages.environment.wizard.form.base_domain_label') }}</label>
            <input type="url" name="backend_url" style="width: 100%;"  id="backend_url" value="{{ old('backend_url', $project->project_url) }}" placeholder="Enter Backend Url" />
            @if ($errors->has('backend_url'))
                <span class="error-block">
                <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                {{ $errors->first('backend_url') }}
            </span>
            @endif
        </div>
        <div class="form-group {{ $errors->has('purchased_code') || !empty(session('purchased_code')) ? ' has-error ' : '' }}">
            <label for="purchased_code">{{ trans('installer_messages.purchased_code.purchased_code') }} ( Envato License Code )</label>
            <input type="text" name="purchased_code" style="width: 100%;" id="purchased_code" value="{{ old('purchased_code', $project->project_code) }}" placeholder="Enter Purchased Code ( Envato License Code )" />
            @if ($errors->has('purchased_code') || !empty(session('purchased_code')))
                <span class="error-block">
                <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                @if($errors->has('purchased_code'))
                    {{ $errors->first('purchased_code') }}
                @endif
                @if(!empty(session('purchased_code')))
                    {{ session('purchased_code') }}
                @endif
            </span>
            @endif

        </div>

        @if ($errors->has('token') || !empty(session('token')))
        <div class="form-group {{ $errors->has('token') || !empty(session('token')) ? ' has-error ' : '' }}">
            <label for="token"> Comfirmation Token Code ( Please check your email ) </label>
            <input type="text" name="token" id="token" style="width: 100%;" value="" placeholder="Enter Confirmation Token" />            
                <span class="error-block">
                <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                @if($errors->has('token'))
                    {{ $errors->first('token') }}
                @endif
                @if(!empty(session('token')))
                    {{ session('token') }}
                @endif
            </span>            
        </div>
        @endif

        <div class="buttons">
            <button type="submit" class="button-btn" data-loading-text="Loading..." onclick="submitForm(this)">
                @if(count($errors) > 0)
                    {{ trans('installer_messages.try_again') }}
                @else
                    {{ trans('installer_messages.purchased_code.update_conf_btn') }}
                    <i class="fa fa-angle-right fa-fw" aria-hidden="true"></i>
                @endif
            </button>
        </div>
    </form>

@endsection

@section("scripts")
    <script>
        
        function submitForm(button) {

            var url = document.getElementById('backend_url').value;
            if (url.endsWith('/') || url.endsWith('\\')) {
                url = url.slice(0, -1);
            }
            document.getElementById('backend_url').value = url;

            var originalText = button.textContent;
            button.disabled = true;
            button.dataset.originalText = originalText;
            button.textContent = button.dataset.loadingText;
            button.form.submit();
        }

        
        document.addEventListener("DOMContentLoaded", function() {
            var buttons = document.querySelectorAll('.button-btn');
            buttons.forEach(function(button) {
                if (button.dataset.originalText) {
                    button.innerHTML = button.dataset.originalText;
                    button.disabled = false;
                }
            });

            // Add this to handle page load from cache
            window.onpageshow = function(event) {
                if (event.persisted) {
                    buttons.forEach(function(button) {
                        if (button.dataset.originalText) {
                            button.innerHTML = button.dataset.originalText;
                            button.disabled = false;
                        }
                    });
                }
            };
        });

    </script>
@endsection

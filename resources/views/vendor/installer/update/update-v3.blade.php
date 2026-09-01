@extends('vendor.installer.layouts.master-update-v3')

@section('title', 'Welcome to the update wizard')
@section('container')
    <p class="paragraph text-center">
        Begin the process of enhancing your app's performance and features. Let's get started by progressing through this
        update wizard.
    </p>

    {{-- Error Message --}}
    @if ($errors->any())
        <div class="alert alert-danger" style="margin: 0.6rem 0;">
            <ul style="margin: 0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Success Message  --}}
    @if (session('zipUploadSuccess'))
        <div style="background: green; color: white; padding: 0.5rem 1rem; border-radius: 0.25rem; margin; 0.6rem 0">
            {{ session('zipUploadSuccess.message') }}
        </div>
    @endif

    @if (session('installSuccess'))
        <div style="background: green; color: white; padding: 0.5rem 1rem; border-radius: 0.25rem; margin; 0.6rem 0">
            {{ session('installSuccess.message') }}
        </div>
    @endif

    {{-- Zip Upload Form --}}
    @if (!session('zipUploadSuccess'))
        <div id='zip-name-container' style="display: none">
            <span style="font-weight: bold;">Selected File:</span> <span id="zip-name"></span>
        </div>

        <form method="POST" action="{{ route('NextLaravelUpdater::updateV3.uploadZip') }}" enctype="multipart/form-data">
            @csrf
            <input id='zip-input' name='zip_file' style="position: absolute; visibility: hidden;" type="file"
                accept=".zip" />
            <div class="buttons">
                <button id='zip-select' type="button" class="button-btn">Select Zip File</button>
                <button id='zip-upload' data-loading-text="Loading..." style="display: none" type="submit" class="button-btn" onclick="submitForm(this)">Upload</button>
            </div>
        </form>

        <script>
            const zipInput = document.getElementById('zip-input');
            const uploadButton = document.getElementById('zip-upload');
            const zipNameContainer = document.getElementById('zip-name-container');
            const zipNameSpan = document.getElementById('zip-name');

            document.getElementById('zip-select').onclick = () => {
                zipInput.click();
            };

            zipInput.onchange = () => {
                if (zipInput.files.length > 0) {
                    const file = zipInput.files[0];
                    uploadButton.style.display = 'inline';
                    zipNameContainer.style.display = 'inline';
                    zipNameSpan.textContent = file.name;
                } else {
                    uploadButton.style.display = 'none';
                    zipNameContainer.style.display = 'none';
                    zipNameSpan.textContent = '';
                }
            };
        </script>
    @endif

    @if (session('zipUploadSuccess'))
        <form method="POST" action="{{ route('NextLaravelUpdater::updateV3.installUpdate') }}">
            @csrf
            <div class="buttons">
                <button id='zip-upload' type="submit" data-loading-text="Loading..." class="button-btn" onclick="submitForm(this)">Install</button>
            </div>
        </form>
    @endif

    <script>
        function submitForm(button) {
            const uploadZipBtn = document.getElementById('zip-select');
            if(uploadZipBtn) {
                uploadZipBtn.disabled = true;
            }

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
@stop

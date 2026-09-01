<?php

namespace Modules\Installer\Controllers;

use App\Config\ps_constant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Core\Entities\Project;
use Modules\Installer\Helpers\InstalledFileManager;

class PurchasedCodeController
{
    private $installManager;

    public function __construct()
    {
        $this->installManager = new InstalledFileManager;
    }

    public function purchasedCode()
    {

        $project = Project::first();

        $project->user_email = $this->installManager->getOwner();

        return view('vendor.installer.purchased-code')->with('project', $project);
    }

    private function returnError($errors)
    {
        return redirect()
            ->route('LaravelInstaller::purchasedCode')
            ->withErrors($errors)
            ->withInput();
    }

    public function getCurrentDomainUrl(): string
    {
        // 1. Determine the Protocol (HTTP or HTTPS)
        // Check if the server is running on HTTPS. Note: HTTP_X_FORWARDED_PROTO is common when behind a load balancer/proxy.
        $protocol = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (! empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            ? 'https://' : 'http://';

        // 2. Get the Host (Domain name)
        // HTTP_HOST includes the port if it's non-standard (e.g., example.com:8080).
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];

        // 4. Combine them
        return $protocol.$host;
    }

    public function purchasedCodeStore(Request $request) // UpdateLicenseRequest $request)
    {

        try {

            // Check Params
            if (! $request->backend_url) {
                return $this->returnError(['backend_url' => 'Domain is invalid.']);
            }
            $backendUrl = rtrim($request->backend_url, '/\\');

            if (! $request->user_email) {
                return $this->returnError(['user_email' => 'Email is invalid.']);
            }

            if (! $request->purchased_code) {
                return $this->returnError(['purchased_code' => 'License is invalid.']);
            }

            $domain = $this->getCurrentDomainUrl();
            if (! Str::contains($backendUrl, $domain)) {
                return $this->returnError(['backend_url' => 'Domain is invalid. It should be : '.$domain]);
            }

            // 2. Project Create API ( Builder )
            $project = Project::first();
            $apiUrl = ps_constant::base_url.'/register-project-v3';
            $response = Http::post($apiUrl, [
                'license_code' => $request->purchased_code,
                'domain_url' => $backendUrl,
                'user_email' => $request->user_email,
                'token' => $request->token ?? '',
                'base_project_id' => $project->base_project_id ?? '',
            ]);

            // 3. Handle Success
            if ($response->successful()) {
                // Assuming success means a valid code was verified
                $response = json_decode($response, true);
                if ($response['status'] == 'success') {
                    $this->installManager->storeOwner($request->user_email);

                    $project = Project::first();
                    $project->id = $response['data']['id'];
                    $project->project_name = $response['data']['project_name'].'_A';
                    $project->ps_license_code = $response['data']['ps_license_code'];
                    $project->project_code = $request->purchased_code;
                    $project->project_url = $response['data']['project_url'];
                    $project->api_key = $response['data']['api_key'];
                    $project->save();

                    return redirect()->route('LaravelInstaller::userConfiguration');

                } elseif ($response['status'] == 'confirmation') {
                    $this->installManager->storeOwner($request->user_email);

                    return redirect()
                        ->route('LaravelInstaller::purchasedCode')
                        ->withErrors(['token' => $response['message']])
                        ->withInput();

                } else {
                    // Failed/ Error
                    return redirect()
                        ->route('LaravelInstaller::purchasedCode')
                        ->withErrors(['message' => 'Unknowed Error!'])
                        ->withInput();
                }

                return redirect()
                    ->route('LaravelInstaller::purchasedCode')
                    ->with('message', $response->body())
                    ->with('email', $request->user_email);

            }

            // 4. Handle API Failure (e.g., server responded, but validation failed)
            $errorMessage = 'Verification failed.';

            if ($response->status() === 401 || $response->status() === 422) {
                // Try to extract a more specific error from the API response body
                $apiError = $response->json('message');
                if ($apiError) {
                    $errorMessage = $apiError;
                }
            }

            // Redirect back with an error associated with the 'purchased_code' field
            return redirect()
                ->route('LaravelInstaller::purchasedCode')
                ->withErrors(['purchased_code' => $errorMessage])
                ->withInput();

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Redirect back with an error associated with the 'backend_url' field
            return redirect()
                ->route('LaravelInstaller::purchasedCode')
                ->withErrors(['message' => $errorMessage])
                ->withInput();
        }

    }
}

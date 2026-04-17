<?php

namespace App\Providers;

use App\Policies\AttachmentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Laravel auto-discovers AttachmentPolicy for Attachment and
        // AttachmentSharePolicy for AttachmentShare. The one ability that
        // takes a Project instead of an Attachment needs an explicit gate
        // so route middleware (can:upload-attachment,project) works.
        Gate::define('upload-attachment', [AttachmentPolicy::class, 'createIn']);
    }
}

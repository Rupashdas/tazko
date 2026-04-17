<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage disk
    |--------------------------------------------------------------------------
    |
    | Which filesystem disk new attachments are stored on. Defaults to the
    | private 'local' disk (storage/app/private). Switching to 's3' here —
    | with no other code changes — makes future uploads land in S3; existing
    | rows keep working because each row records its own 'disk' column.
    |
    */
    'disk' => env('ATTACHMENT_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Maximum file size (megabytes, per file)
    |--------------------------------------------------------------------------
    |
    | Upload validation rejects files larger than this. 50 MB covers typical
    | project-management attachments (screenshots, PDFs, short screen
    | recordings) while staying under common PHP upload defaults after
    | tuning. Remember that php.ini's upload_max_filesize and post_max_size
    | must be set at least this high on the server.
    |
    */
    'max_size_mb' => (int) env('ATTACHMENT_MAX_SIZE_MB', 50),

    /*
    |--------------------------------------------------------------------------
    | Orphan TTL (hours)
    |--------------------------------------------------------------------------
    |
    | How long an uploaded-but-never-committed attachment is kept before the
    | reaper command deletes it. 24 hours is a balance: long enough that a
    | slow-drafting user can still submit, short enough that abandoned
    | uploads don't accumulate meaningfully.
    |
    */
    'orphan_ttl_hours' => (int) env('ATTACHMENT_ORPHAN_TTL_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Denied extensions
    |--------------------------------------------------------------------------
    |
    | Denylist over allowlist: we can't predict every legitimate file type a
    | user needs (.sketch, .fig, .psd, .csv, .mov, etc.), but we CAN block
    | the small set of executable and script types that represent the real
    | security risk. Values are lowercase, no dot.
    |
    */
    'denied_extensions' => [
        'exe', 'bat', 'cmd', 'com', 'msi', 'scr', 'dll',
        'sh', 'bash', 'zsh',
        'php', 'phtml', 'phar', 'asp', 'aspx', 'jsp',
        'vbs', 'wsf', 'ps1', 'jar',
        'dmg', 'app', 'deb', 'rpm',
    ],

    /*
    |--------------------------------------------------------------------------
    | Denied MIME types
    |--------------------------------------------------------------------------
    |
    | Second-line defense against someone renaming malware.exe to invoice.pdf.
    | Laravel's UploadedFile::getMimeType() reads the file's magic bytes via
    | finfo, so this check can't be fooled by extension alone.
    |
    */
    'denied_mime_types' => [
        'application/x-msdownload',
        'application/x-msdos-program',
        'application/x-executable',
        'application/x-sh',
        'application/x-msi',
        'application/x-elf',
        'application/x-mach-binary',
    ],

];

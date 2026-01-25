@extends('adminlte::page')

@section('title', 'Create Storage Box')

@section('content_header')
    <h1>Create Storage Box</h1>
@stop

@section('content')
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong><i class="fas fa-exclamation-triangle"></i> Warning!</strong>
        Creating a new Storage Box will charge your Hetzner account. Please verify all details before proceeding.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <div class="card">
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.storage_servers.store') }}" id="create-storage-box-form">
            @csrf
            <div class="card-body">

                <!-- Basic Information Section -->
                <h5 class="mb-3"><i class="fas fa-info-circle"></i> Basic Information</h5>

                <div class="form-group">
                    <label for="name">Storage Box Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" placeholder="e.g., Backup Server 1" required>
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="location">Location <span class="text-danger">*</span></label>
                        <select name="location" id="location" class="form-control @error('location') is-invalid @enderror" required>
                            <option value="">-- Select Location --</option>
                            @foreach($locations as $location)
                                <option value="{{ $location['id'] }}" @selected(old('location') == $location['id'])>
                                    {{ $location['name'] }} - {{ $location['description'] }} ({{ $location['country'] }})
                                </option>
                            @endforeach
                        </select>
                        @error('location')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group col-md-6">
                        <label for="storage_box_type">Storage Box Type <span class="text-danger">*</span></label>
                        <select name="storage_box_type" id="storage_box_type" class="form-control @error('storage_box_type') is-invalid @enderror" required>
                            <option value="">-- Select Type --</option>
                            @foreach($storageBoxTypes as $type)
                                <option value="{{ $type['id'] }}" @selected(old('storage_box_type') == $type['id'])>
                                    {{ $type['name'] }} - {{ $type['description'] }} ({{ round($type['size'] / 1024 / 1024 / 1024, 0) }} GB)
                                </option>
                            @endforeach
                        </select>
                        @error('storage_box_type')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror"
                               value="{{ old('password') }}" placeholder="Must be 12-128 characters" required>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <small class="form-text text-muted">
                        Password must contain: lowercase, uppercase, numbers, and special characters. Length: 12-128 characters.
                    </small>
                </div>

                <hr>

                <!-- Access Settings Section -->
                <h5 class="mb-3"><i class="fas fa-lock"></i> Access Settings</h5>

                <div class="custom-control custom-checkbox">
                    <input type="checkbox" name="reachable_externally" class="custom-control-input" id="reachable_externally"
                           @checked(old('reachable_externally', true))>
                    <label class="custom-control-label" for="reachable_externally">
                        <strong>Reachable Externally</strong> - Allow external connections to storage box
                    </label>
                </div>

                <div class="custom-control custom-checkbox">
                    <input type="checkbox" name="ssh_enabled" class="custom-control-input" id="ssh_enabled"
                           @checked(old('ssh_enabled', true))>
                    <label class="custom-control-label" for="ssh_enabled">
                        <strong>SSH</strong> - Enable SSH/SFTP access
                    </label>
                </div>

                <div class="custom-control custom-checkbox">
                    <input type="checkbox" name="samba_enabled" class="custom-control-input" id="samba_enabled"
                           @checked(old('samba_enabled', true))>
                    <label class="custom-control-label" for="samba_enabled">
                        <strong>Samba/SMB</strong> - Enable network share access
                    </label>
                </div>

                <div class="custom-control custom-checkbox">
                    <input type="checkbox" name="webdav_enabled" class="custom-control-input" id="webdav_enabled"
                           @checked(old('webdav_enabled', false))>
                    <label class="custom-control-label" for="webdav_enabled">
                        <strong>WebDAV</strong> - Enable WebDAV access
                    </label>
                </div>

                <div class="custom-control custom-checkbox">
                    <input type="checkbox" name="zfs_enabled" class="custom-control-input" id="zfs_enabled"
                           @checked(old('zfs_enabled', false))>
                    <label class="custom-control-label" for="zfs_enabled">
                        <strong>ZFS</strong> - Enable ZFS snapshots
                    </label>
                </div>

                <hr>

                <!-- Advanced Options -->
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" id="show-advanced" class="custom-control-input">
                    <label class="custom-control-label" for="show-advanced">
                        <strong>Advanced Options</strong> (SSH Keys & Labels)
                    </label>
                </div>

                <div id="advanced-section" style="display: none; margin-top: 20px;">
                    <div class="form-group">
                        <label for="ssh_keys">SSH Public Keys (Optional)</label>
                        <textarea name="ssh_keys" id="ssh_keys" class="form-control" rows="4"
                                  placeholder="Enter SSH public key(s), one per line&#10;Example: ssh-rsa AAAA...&#10;ssh-ed25519 AAAA...">{{ old('ssh_keys') }}</textarea>
                        <small class="form-text text-muted">Add SSH public keys to enable key-based authentication</small>
                    </div>

                    <div class="form-group">
                        <label>Labels (Optional)</label>
                        <small class="form-text text-muted d-block mb-2">Add metadata labels as key-value pairs</small>
                        <div id="labels-container">
                            <div class="label-row mb-2">
                                <div class="input-group">
                                    <input type="text" name="labels[0][key]" class="form-control" placeholder="Key (e.g., environment)" value="">
                                    <input type="text" name="labels[0][value]" class="form-control" placeholder="Value (e.g., prod)" value="">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-danger remove-label" type="button">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-label">
                            <i class="fas fa-plus"></i> Add Label
                        </button>
                    </div>
                </div>

            </div>

            <div class="card-footer">
                <button type="button" class="btn btn-primary" id="submit-btn" data-toggle="modal" data-target="#confirm-modal">
                    <i class="fas fa-check"></i> Create Storage Box
                </button>
                <a href="{{ route('admin.storage_servers.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirm-modal" tabindex="-1" role="dialog" aria-labelledby="confirm-label" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirm-label">
                        <i class="fas fa-exclamation-circle"></i> Confirm Storage Box Creation
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>⚠️ This action will charge your Hetzner account!</strong></p>
                    <p>Please verify the following details:</p>
                    <ul id="confirm-list" style="margin-left: 20px;">
                        <li><strong>Name:</strong> <span id="confirm-name"></span></li>
                        <li><strong>Location:</strong> <span id="confirm-location"></span></li>
                        <li><strong>Type:</strong> <span id="confirm-type"></span></li>
                    </ul>
                    <p class="mt-3">
                        <strong>Type "I UNDERSTAND" below to confirm:</strong>
                    </p>
                    <input type="text" id="confirm-text" class="form-control" placeholder="Type: I UNDERSTAND">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm-btn" disabled>
                        <i class="fas fa-exclamation-triangle"></i> Yes, Create Storage Box
                    </button>
                </div>
            </div>
        </div>
    </div>

@stop

@section('js')
<script>
$(function() {
    // Toggle password visibility
    $('#toggle-password').click(function() {
        const passwordInput = $('#password');
        const icon = $(this).find('i');
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordInput.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Toggle advanced options
    $('#show-advanced').change(function() {
        $('#advanced-section').toggle();
    });

    // Add label
    let labelCount = 1;
    $('#add-label').click(function() {
        const html = `
            <div class="label-row mb-2">
                <div class="input-group">
                    <input type="text" name="labels[${labelCount}][key]" class="form-control" placeholder="Key" value="">
                    <input type="text" name="labels[${labelCount}][value]" class="form-control" placeholder="Value" value="">
                    <div class="input-group-append">
                        <button class="btn btn-outline-danger remove-label" type="button">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#labels-container').append(html);
        labelCount++;
    });

    // Remove label
    $(document).on('click', '.remove-label', function() {
        $(this).closest('.label-row').remove();
    });

    // Confirmation modal
    $('#submit-btn').click(function() {
        $('#confirm-name').text($('#name').val());
        $('#confirm-location').text($('#location option:selected').text());
        $('#confirm-type').text($('#storage_box_type option:selected').text());
        $('#confirm-text').val('').focus();
    });

    $('#confirm-text').on('input', function() {
        const confirmBtn = $('#confirm-btn');
        if ($(this).val() === 'I UNDERSTAND') {
            confirmBtn.prop('disabled', false);
        } else {
            confirmBtn.prop('disabled', true);
        }
    });

    $('#confirm-btn').click(function() {
        $('#create-storage-box-form').submit();
    });
});
</script>
@stop

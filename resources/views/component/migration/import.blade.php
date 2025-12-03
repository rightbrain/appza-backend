@extends('layouts.app')

@section('body')

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Import Component</h1>
            <a href="{{ route('component_migrate_logs') }}" class="btn btn-outline-secondary">
                <i class="fas fa-history me-2"></i>View Import Logs
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Upload Component JSON</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('component_migrate_import_file') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="file" class="form-label">Component JSON File</label>
                        <input type="file" class="form-control" id="file" name="file" required accept=".json">
                        <div class="form-text">
                            Select the JSON file exported from the development server.
                            Maximum file size: 10MB
                        </div>
                        <div class="invalid-feedback">
                            Please select a valid JSON file.
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="overwrite" name="overwrite" value="1">
                        <label class="form-check-label" for="overwrite">
                            Overwrite if component already exists
                        </label>
                        <div class="form-text">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            Enabling this will replace all existing component data including properties and relationships.
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary" id="importButton">
                            <i class="fas fa-upload me-2"></i>Import Component
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-2"></i>Reset Form
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Recent Imports</h3>
                <a href="{{ route('component_migrate_logs') }}" class="btn btn-sm btn-outline-primary">
                    View All Logs <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    @php
                        $recentLogs = \App\Models\ComponentImportLog::latest()->take(10)->get();
                    @endphp

                    @if($recentLogs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Component</th>
                                    <th>Status</th>
                                    <th>Source</th>
                                    <th>Message</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($recentLogs as $log)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($log->created_at)->format('M j, Y g:i:s A') }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $log->component_name }}</div>
                                        </td>
                                        <td>
                                            @if($log->success)
                                                <span class="badge bg-success">Success</span>
                                            @else
                                                <span class="badge bg-danger">Failed</span>
                                            @endif
                                        </td>
                                        <td>{{ $log->source }}</td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" title="{{ $log->message }}">
                                                {{ $log->message }}
                                            </div>
                                        </td>
                                        <td>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary view-message-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#messageModal"
                                                    data-message="{{ $log->message }}"
                                                    data-component="{{ $log->component_name }}"
                                                    data-status="{{ $log->success ? 'Success' : 'Failed' }}">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No import history found</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Import Log Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6>Component: <span id="modalComponentName" class="fw-bold"></span></h6>
                        <h6>Status: <span id="modalStatus"></span></h6>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Raw Message:</label>
                        <pre id="rawMessage" class="p-3 bg-light border rounded" style="white-space: pre-wrap; max-height: 200px; overflow-y: auto;"></pre>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Formatted JSON (if applicable):</label>
                        <div id="jsonViewer" class="p-3 bg-light border rounded" style="max-height: 300px; overflow-y: auto;">
                            <pre id="formattedJson" class="mb-0"></pre>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="copyMessageBtn">
                        <i class="fas fa-copy me-2"></i>Copy Message
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Handle modal show event
            const messageModal = document.getElementById('messageModal');
            messageModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const message = button.getAttribute('data-message');
                const componentName = button.getAttribute('data-component');
                const status = button.getAttribute('data-status');

                document.getElementById('modalComponentName').textContent = componentName;
                document.getElementById('modalStatus').textContent = status;
                document.getElementById('rawMessage').textContent = message;

                const jsonViewer = document.getElementById('jsonViewer');
                const formattedJson = document.getElementById('formattedJson');

                try {
                    const jsonData = JSON.parse(message);
                    formattedJson.textContent = JSON.stringify(jsonData, null, 2);
                    jsonViewer.style.display = 'block';
                } catch {
                    jsonViewer.style.display = 'none';
                }
            });

            document.getElementById('copyMessageBtn').addEventListener('click', function() {
                const message = document.getElementById('rawMessage').textContent;
                navigator.clipboard.writeText(message);
            });
        });
    </script>
@endsection


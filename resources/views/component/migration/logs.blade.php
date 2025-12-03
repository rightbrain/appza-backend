@extends('layouts.app')

@section('body')
    <div class="container-fluid">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Import Logs</h1>
            <a href="{{ route('component_migrate_form') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Import
            </a>
        </div>

        <!-- Filter Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Filter Logs</h5>
            </div>
            <div class="card-body">

                <form action="{{ route('component_migrate_logs') }}" method="GET">
                    <div class="row g-3">

                        <div class="col-md-3">
                            <label class="form-label">Component Name</label>
                            <input type="text" class="form-control" name="component_name"
                                   value="{{ request('component_name') }}" placeholder="Filter by name">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="success">
                                <option value="">All</option>
                                <option value="1" {{ request('success') === '1' ? 'selected' : '' }}>Success</option>
                                <option value="0" {{ request('success') === '0' ? 'selected' : '' }}>Failed</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Source</label>
                            <select class="form-select" name="source">
                                <option value="">All</option>
                                @foreach($sources as $source)
                                    <option value="{{ $source }}" {{ request('source') === $source ? 'selected' : '' }}>
                                        {{ $source }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                        </div>

                        <div class="col-md-1 d-flex align-items-end">
                            <button class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Filter</button>
                        </div>

                    </div>
                </form>

            </div>
        </div>

        <!-- Stats -->
        {{--<div class="row mb-4">
            <div class="col-md-3"><div class="card text-white bg-primary"><div class="card-body"><h5>Total Imports</h5><h2>{{ \App\Models\ComponentImportLog::count() }}</h2></div></div></div>
            <div class="col-md-3"><div class="card text-white bg-success"><div class="card-body"><h5>Successful</h5><h2>{{ \App\Models\ComponentImportLog::where('success', true)->count() }}</h2></div></div></div>
            <div class="col-md-3"><div class="card text-white bg-danger"><div class="card-body"><h5>Failed</h5><h2>{{ \App\Models\ComponentImportLog::where('success', false)->count() }}</h2></div></div></div>
            <div class="col-md-3"><div class="card text-white bg-info"><div class="card-body"><h5>Success Rate</h5><h2>{{ round(\App\Models\ComponentImportLog::where('success', true)->count() / max(1, \App\Models\ComponentImportLog::count()) * 100) }}%</h2></div></div></div>
        </div>--}}

        <!-- Logs Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Import History</h5>
                <span class="badge bg-secondary">{{ $logs->count() }} of {{ $logs->total() }} records</span>
            </div>

            <div class="card-body p-0 pb-5">

                <table class="table table-bordered text-center mb-0">
                    <thead class="thead-dark">
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
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($log->created_at)->format('M j, Y g:i:s A') }}</td>

                            <td><strong>{{ $log->component_name }}</strong></td>

                            <td>
                                @if($log->success)
                                    <span class="badge bg-success">Success</span>
                                @else
                                    <span class="badge bg-danger">Failed</span>
                                @endif
                            </td>

                            <td>{{ $log->source }}</td>

                            <td>
                                <div class="text-truncate" style="max-width: 300px;" title="{{ $log->message }}">
                                    {{ $log->message }}
                                </div>
                            </td>

                            <td>
                                <div class="btn-group">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary view-message-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#messageModal"
                                            data-message="{{ $log->message }}"
                                            data-component="{{ $log->component_name }}"
                                            data-status="{{ $log->success ? 'Success' : 'Failed' }}">
                                        <i class="fas fa-eye"></i> View
                                    </button>

                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary copy-message-btn"
                                            data-message="{{ $log->message }}">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-muted">No logs found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

                <!-- Pagination (Always show when total > 0) -->
                @if($logs->total() > 0)
                    <div class="mt-3 px-3 pb-3 d-flex justify-content-end">
                        {{ $logs->links('layouts.pagination') }}
                    </div>
                @endif


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

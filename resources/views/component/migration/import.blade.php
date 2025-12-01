@extends('layouts.app')

@section('body')
    <div class="container">
        <h1>Import Component</h1>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('component_migrate_import_file') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="file" class="form-label">Component JSON File</label>
                        <input type="file" class="form-control" id="file" name="file" required accept=".json">
                        <div class="form-text">Select the JSON file exported from the development server.</div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="overwrite" name="overwrite">
                        <label class="form-check-label" for="overwrite">
                            Overwrite if component already exists
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">Import Component</button>
                </form>
            </div>
        </div>

        <div class="mt-4">
            <h3>Recent Imports</h3>
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Component</th>
                    <th>Status</th>
                    <th>Source</th>
                </tr>
                </thead>
                <tbody>
                @foreach(\App\Models\ComponentImportLog::latest()->take(10)->get() as $log)
                    <tr>
                        <td>{{ $log->created_at }}</td>
                        <td>{{ $log->component_name }}</td>
                        <td>
                            @if($log->success)
                                <span class="badge bg-success">Success</span>
                            @else
                                <span class="badge bg-danger">Failed</span>
                            @endif
                        </td>
                        <td>{{ $log->source }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

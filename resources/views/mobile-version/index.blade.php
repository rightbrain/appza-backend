@extends('layouts.app')

@section('body')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card" style="margin-bottom: 50px !important;">

                    <div class="card-header">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
                        <h6>{{__('messages.MobileVersionList')}}</h6>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="{{route('mobile_version_add')}}" title="" class="module_button_header">
                                    <button type="button" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-plus-circle"></i> {{__('messages.createMobileVersion')}}
                                    </button>
                                </a>
                            </div>
                        </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @include('layouts.message')
                        <form method="post" role="form" id="search-form">
                            <table id="leave_settings" class="table table-bordered datatable table-responsive mainTable text-center">

                                <thead class="thead-dark">
                                <tr>
                                    <th>{{__('messages.SL')}}</th>
                                    <th>App Name</th>
                                    <th>Mobile Version</th>
                                    <th>Minimum plugin version</th>
                                    <th>Latest plugin version</th>
                                    <th>Type</th>
                                    <th scope="col text-center" class="sorting_disabled" rowspan="1" colspan="1" aria-label style="width: 24px;">
                                        <i class="fas fa-cog"></i>
                                    </th>
                                </tr>
                                </thead>

                                @if(sizeof($mobileVersions)>0)
                                    <tbody>
                                        @php
                                            $i=1;
                                            $currentPage = $mobileVersions->currentPage();
                                            $perPage = $mobileVersions->perPage();
                                            $serial = ($currentPage - 1) * $perPage + 1;
                                        @endphp
                                        @foreach($mobileVersions as $mobileVersion)
                                            <tr>
                                                <td>{{$serial++}}</td>
                                                <td>{{$mobileVersion->mobile_app_name}}</td>
                                                <td>{{$mobileVersion->mobile_version}}</td>
                                                <td>{{$mobileVersion->minimum_plugin_version}}</td>
                                                <td>{{$mobileVersion->latest_plugin_version}}</td>
                                                <td>
                                                    @if($mobileVersion->force_update)
                                                        <span class="badge bg-success">Force update</span>
                                                    @else
                                                        <span class="badge bg-warning">Not force update</span>
                                                    @endif

                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group" aria-label="Basic outlined example">
                                                        @if(auth()->user()->user_type === 'DEVELOPER' || auth()->user()->user_type === 'ADMIN' || auth()->user()->user_type === 'PLUGIN' || auth()->user()->user_type === 'ANDROID')
                                                            <a title="Edit" class="btn btn-outline-primary btn-sm" href="{{route('mobile_version_edit',$mobileVersion->id)}}"><i class="fas fa-edit"></i></a>
                                                        @endif
{{--                                                        <a title="Delete" onclick="return confirm('Are you sure?');" class="btn btn-outline-danger btn-sm" href="{{route('page_delete',$page->id)}}"><i class="fas fa-trash"></i></a>--}}
                                                    </div>
                                                </td>
                                            </tr>
                                            @php $i++; @endphp
                                        @endforeach
                                    </tbody>
                                @endif
                            </table>
                            @if(isset($mobileVersions) && count($mobileVersions)>0)
                                <div class=" justify-content-right">
                                    {{ $mobileVersions->links('layouts.pagination') }}
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection

@section('footer.scripts')
{{--    <script src="{{Module::asset('appfiy:js/employee.js')}}"></script>--}}
@endsection

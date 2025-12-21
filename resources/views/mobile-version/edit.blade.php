@extends('layouts.app')

@section('body')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">

                    <div class="card-header">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
                            <h6>Mobile version update</h6>
                            <div class="btn-toolbar mb-2 mb-md-0">
                                <div class="btn-group me-2">

                                    <div class="btn-group me-2">
                                        <a href="{{route('mobile_version_add')}}" title="" class="module_button_header">
                                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-plus-circle"></i> {{__('messages.createMobileVersion')}}
                                            </button>
                                        </a>
                                    </div>

                                    <a href="{{route('mobile_version_list')}}" title="" class="module_button_header">
                                        <button type="button" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-list"></i> {{__('messages.list')}}
                                        </button>
                                    </a>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @include('layouts.message')
                        <div class="row">
                            <div class="col-md-12">
                                {{ html()->modelForm($data, 'PATCH', route('mobile_version_update', $data->id))
                                    ->attribute('enctype', 'multipart/form-data')
                                    ->attribute('files', true)
                                    ->attribute('autocomplete', 'off')
                                    ->open() }}

                                <div class="row">

                                    <div class="form-group row mg-top">
                                        <div class="col-sm-2">
                                            <label for="mobile_app_id" class="form-label">Mobile App</label>
                                            <span class="textRed">*</span>
                                        </div>

                                        <div class="col-sm-10">
                                            {{ html()
                                                ->select('mobile_app_id', $mobileApps, old('mobile_app_id'))
                                                ->class('form-control form-select js-example-basic-single')
                                                ->attribute('aria-describedby', 'basic-addon2')
                                                ->placeholder("Choose mobile app")
                                                ->required()
                                            }}
                                            <span class="textRed">{!! $errors->first('mobile_app_id') !!}</span>
                                        </div>
                                    </div>

                                    <div class="form-group row mg-top">
                                        <div class="col-sm-2">
                                            <label for="mobile_version" class="form-label">Mobile Version</label>
                                            <span class="textRed">*</span>
                                        </div>

                                        <div class="col-sm-4">
                                            {{html()
                                                ->text('mobile_version')
                                                ->class('form-control')
                                                ->placeholder("Mobile Version")
                                                ->required()
                                            }}
                                            <span class="textRed">{!! $errors->first('mobile_version') !!}</span>
                                        </div>

                                        <hr style="margin-top: 20px">

                                        <div class="col-sm-2">
                                            <label for="minimum_plugin_version" class="form-label">Minimum plugin version</label>
                                            <span class="textRed">*</span>
                                        </div>

                                        <div class="col-sm-4">
                                            {{html()
                                                ->text('minimum_plugin_version')
                                                ->class('form-control')
                                                ->placeholder("Minimum plugin version")
                                                ->required()
                                            }}
                                            <span class="textRed">{!! $errors->first('minimum_plugin_version') !!}</span>
                                        </div>

                                        <div class="col-sm-2">
                                            <label for="latest_plugin_version" class="form-label">Latest plugin version</label>
                                            <span class="textRed">*</span>
                                        </div>

                                        <div class="col-sm-4">
                                            {{html()
                                                ->text('latest_plugin_version')
                                                ->class('form-control')
                                                ->placeholder("Latest plugin version")
                                                ->required()
                                            }}
                                            <span class="textRed">{!! $errors->first('latest_plugin_version') !!}</span>
                                        </div>
                                    </div>

                                    <hr style="margin-top: 20px">


                                    <div class="form-group row mg-top">
                                        <div class="col-sm-2">
                                            <label for="optional_message" class="form-label">Optional message</label>
                                        </div>

                                        <div class="col-sm-4">
                                            {{html()
                                                ->textarea('optional_message')
                                                ->class('form-control')
                                                ->placeholder("Optional message")
                                                ->attribute('rows',5)
                                            }}
                                            <span class="textRed">{!! $errors->first('optional_message') !!}</span>
                                        </div>
                                    </div>

                                    <hr style="margin-top: 20px">


                                    <div class="form-group row mg-top">
                                        <div class="col-sm-2">
                                            <label for="" class="form-label">Force update</label>
                                        </div>

                                        <div class="col-sm-4">
                                            <div class="from-group">
                                                <?php
                                                $pluginIsPremiumTrue = '';
                                                $pluginIsPremiumFalse = '';
                                                if (isset($data->force_update)) {
                                                    if ($data->force_update == 1) {
                                                        $pluginIsPremiumTrue = 'checked="checked"';
                                                    } else {
                                                        $pluginIsPremiumFalse = 'checked="checked"';
                                                    }
                                                } else {
                                                    $pluginIsPremiumFalse = 'checked="checked"';
                                                }
                                                ?>
                                                <div class="input-group mb-3">
                                                    <div class="form-check form-check-inline">
                                                        <input style="margin-top: 0px"
                                                               class="form-check-input isChecked"
                                                               type="radio" name="force_update" id="showNoDataViewTrue"
                                                               value="1" {{$pluginIsPremiumTrue}}>
                                                        <label class="form-check-label" for="showNoDataViewTrue">True</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input style="margin-top: 0px"
                                                               class="form-check-input isChecked"
                                                               type="radio" name="force_update" id="showNoDataViewFalse"
                                                               value="0" {{$pluginIsPremiumFalse}}>
                                                        <label class="form-check-label" for="showNoDataViewFalse">False</label>
                                                    </div>
                                                    <span class="textRed">{!! $errors->first('force_update') !!}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mg-top">
                                        <div class="col-md-2"></div>
                                        <div class="col-md-10" >
                                            <div class="from-group">
                                                <button type="submit" class="btn btn-primary " id="UserFormSubmit">Submit</button>
                                                <button type="reset" class="btn submit-button">Reset</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{ html()->form()->close() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('CustomStyle')
    <style>
        .customButton{
            color: #000;
            background-color: #fff;
            border-color: #6c757d;
        }
        .imageText{
            background: blue;
            color: #fff;
            padding: 5px 5px;
            display: block;
            margin-top: 2px;
        }
        .textRed{
            color: #ff0000;
        }

        .height29{
            height: 29px;
        }
        .textCenter{
            text-align: center;
        }
        .displayNone{
            display: none;
        }

    </style>
@endpush

@section('footer.scripts')

    <script type="text/javascript">
        imgInp.onchange = evt => {
            const [file] = imgInp.files
            if (file) {
                blah.src = URL.createObjectURL(file)
            }
        }
        $(document).delegate('.global-config-component','change',function(){
            let value = $(this).val();
            let globalConfigId = $('#global_config_id').val();
            let isChecked = 0
            if($(this).is(':checked')){isChecked = 1}
            let route = $('#global_config_assign_component').attr('data-href');
            $.ajax({
                url: route,
                method: "get",
                dataType: "json",
                data: {isChecked: isChecked,componentId:value,globalConfigId:globalConfigId},
                beforeSend: function( xhr ) {

                }
            }).done(function( response ) {
                console.log(response)
                if (response.status == 'deleted'){
                    $('#component_position_id_'+value).val(null)
                }
            }).fail(function( jqXHR, textStatus ) {

            });
            return false;
        });
        $(document).delegate('.component_position','change',function(){
            let value = $(this).val();
            let globalConfigId = $('#global_config_id').val();
            let componentId = $(this).attr('component_id');
            let route = $('#global_config_assign_component_position').attr('data-href');
            $.ajax({
                url: route,
                method: "get",
                dataType: "json",
                data: {componentId: componentId,value:value,globalConfigId:globalConfigId},
                beforeSend: function( xhr ) {

                }
            }).done(function( response ) {
                if (response.status == 'not-found'){
                    alert('Please assign component then added position.')
                    $('#component_position_id_'+componentId).val(null)
                }
            }).fail(function( jqXHR, textStatus ) {

            });
            return false;
        });

        $(document).delegate('.plugin_slug', 'change', function (event) {
            event.preventDefault(); // Prevent any default behavior
            let value = $(this).val();
            let id = $(this).attr('id');
            let route = $('.plugin_slug_update').attr('data-href');

            $.ajax({
                url: route,
                method: "post",
                dataType: "json",
                data: { id: id, value: value },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Attach CSRF token
                },
                beforeSend: function (xhr) {
                    // Optional: Add any loading indicator logic here
                }
            }).done(function (response) {
                if (response.status !== 'ok') {
                    // Reload the page when the response is successful
                    alert('Plugin slug not updated.')
                }
                location.reload();
                // console.log(response);
            }).fail(function (jqXHR, textStatus) {
                console.error('Request failed:', textStatus);
            });

            return false; // Prevent any additional default behavior
        });
    </script>

@endsection

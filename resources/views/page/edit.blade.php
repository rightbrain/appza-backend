@extends('layouts.app')

@section('body')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">

                    <div class="card-header">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
                            <h6>Page update</h6>
                            <div class="btn-toolbar mb-2 mb-md-0">
                                <div class="btn-group me-2">

                                    <div class="btn-group me-2">
                                        <a href="{{route('page_add')}}" title="" class="module_button_header">
                                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-plus-circle"></i> {{__('messages.createPage')}}
                                            </button>
                                        </a>
                                    </div>

                                    <a href="{{route('page_list')}}" title="" class="module_button_header">
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
                                {{ html()->modelForm($data, 'PATCH', route('page_update', $data->id))
                                    ->attribute('enctype', 'multipart/form-data')
                                    ->attribute('files', true)
                                    ->attribute('autocomplete', 'off')
                                    ->open() }}

                                <div class="row">
                                    <div class="form-group row mg-top">
                                        <div class="col-sm-2">
                                            <label for="" class="form-label">{{__('messages.Plugin')}}</label>
                                            <span class="textRed">*</span>
                                        </div>

                                        <div class="col-sm-10">
                                            <input type="hidden" name="plugin_slug" value="{{$data->plugin_slug}}">
                                            {{ html()
                                                ->select('plugin_slug', $pluginDropdown, $data->plugin_slug)
                                                ->class('form-control form-select js-example-basic-single')
                                                ->attribute('aria-describedby', 'basic-addon2')
                                                ->placeholder(__('messages.choosePlugin'))
                                                ->attribute('disabled',true)
                                            }}
                                            <span class="textRed">{!! $errors->first('plugin_slug') !!}</span>
                                        </div>
                                    </div>

                                    <div class="form-group row mg-top">
                                        <div class="col-sm-2">
                                            <label for="layout_type_id" class="form-label">{{__('messages.name')}}</label>
                                            <span class="textRed">*</span>
                                        </div>

                                        <div class="col-sm-4">
                                            {{html()
                                                ->text('name')
                                                ->class('form-control')
                                                ->placeholder(__('messages.name'))
                                            }}
                                            <span class="textRed">{!! $errors->first('name') !!}</span>
                                        </div>

                                        <div class="col-sm-2">
                                            <label for="transparent" class="form-label">{{__('messages.slug')}}</label>
                                            <span class="textRed">*</span>
                                        </div>

                                        <div class="col-sm-4">
                                            {{html()
                                                ->text('slug')
                                                ->class('form-control')
                                                ->placeholder(__('messages.slug'))
                                            }}
                                            <span class="textRed">{!! $errors->first('slug') !!}</span>
                                        </div>
                                    </div>

                                    <div class="form-group row mg-top">
                                        <div class="col-sm-2">
                                            <label for="layout_type_id" class="form-label">{{__('messages.BackgroundColor')}}</label>
                                        </div>

                                        <div class="col-sm-4">
                                            {{ html()
                                                ->input('color', 'background_color')
                                                ->class('form-control inline_update')
                                            }}
                                        </div>

                                        <div class="col-sm-2">
                                            <label for="transparent" class="form-label">{{__('messages.borderColor')}}</label>
                                        </div>

                                        <div class="col-sm-4">
                                            {{ html()
                                                ->input('color', 'border_color')
                                                ->class('form-control inline_update')
                                            }}
                                        </div>
                                    </div>

                                    <div class="form-group row mg-top">
                                        <div class="col-sm-2">
                                            <label for="layout_type_id" class="form-label">{{__('messages.borderRadius')}}</label>
                                        </div>

                                        <div class="col-sm-4">
                                            {{ html()
                                                ->number('border_radius')
                                                ->class('form-control')
                                                ->attribute('step','any')
                                                ->placeholder(__('messages.borderRadius'))
                                            }}
                                        </div>

                                        <div class="col-sm-2">
                                            <label for="transparent" class="form-label">{{__('messages.componentLimit')}}</label>
                                        </div>

                                        <div class="col-sm-4">
                                            {{ html()
                                                ->number('component_limit')
                                                ->class('form-control')
                                                ->placeholder(__('messages.componentLimit'))
                                            }}
                                        </div>
                                    </div>

                                    <div class="form-group row mg-top">
                                        <div class="col-sm-2">
                                            <label for="transparent" class="form-label">{{__('messages.persistent_footer_buttons')}}</label>
                                        </div>

                                        <div class="col-sm-4">
                                            {{ html()->checkbox('persistent_footer_buttons')}}
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
